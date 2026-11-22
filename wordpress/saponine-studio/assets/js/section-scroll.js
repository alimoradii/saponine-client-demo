/**
 * Saponine section navigation. One wheel gesture advances one section.
 * Uses the ordinary document scroll position: no transformed page wrapper,
 * no clipped vertical content, and no global interception inside the editor.
 */
(() => {
  'use strict';
  if (window.SaponineSectionScroll && window.SaponineSectionScroll.destroy) window.SaponineSectionScroll.destroy();
  const root = document.documentElement;
  const selector = '[id^="sec-"],.sn-panel,.sn-hero-section,.sn-footer';
  const clamp = (n, min, max, fallback) => Number.isFinite(Number(n)) ? Math.min(max, Math.max(min, Number(n))) : fallback;
  const supplied = window.SaponineSectionScrollConfig || {};
  const config = {
    enabled: supplied.enabled !== false,
    duration: clamp(supplied.duration, 0, 1500, 650),
    threshold: clamp(supplied.threshold, 8, 160, 36),
    mobile: supplied.mobile === 'sections' ? 'sections' : 'native'
  };
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  const smallScreen = window.matchMedia('(max-width: 767px)');
  const removers = [];
  const EPSILON = 4;
  const GESTURE_IDLE = 220;
  let panels = [];
  let offset = 0;
  let headerBottom = 0;
  let animation = null;
  let animationFrame = 0;
  let refreshFrame = 0;
  let touchTimer = 0;
  let lastWritten = 0;
  let settled = null;
  let gesture = { last: -Infinity, direction: 0, total: 0, used: false };
  let touch = null;
  let touchDestination = null;
  let destroyed = false;
  let resizeObserver;
  let mutationObserver;

  const listen = (node, type, callback, options) => {
    node.addEventListener(type, callback, options);
    removers.push(() => node.removeEventListener(type, callback, options));
  };
  const y = () => window.scrollY || window.pageYOffset || 0;
  const viewport = () => Math.max(1, window.innerHeight || root.clientHeight);
  const maximum = () => Math.max(0, Math.max(root.scrollHeight, document.body ? document.body.scrollHeight : 0) - viewport());
  const editing = () => Boolean(
    (document.body && document.body.classList.contains('elementor-editor-active')) ||
    (window.elementorFrontend && window.elementorFrontend.isEditMode && window.elementorFrontend.isEditMode()) ||
    new URLSearchParams(window.location.search).has('elementor-preview')
  );
  const ready = () => !destroyed && config.enabled && panels.length > 1 && !editing();
  const sectionMode = () => ready() && (!smallScreen.matches || config.mobile === 'sections');
  const visible = node => {
    if (!node || !node.isConnected) return false;
    const rect = node.getBoundingClientRect();
    const style = window.getComputedStyle(node);
    return rect.height > 1 && rect.width > 1 && style.display !== 'none' && style.visibility !== 'hidden';
  };

  function measure() {
    const admin = document.getElementById('wpadminbar');
    offset = 0;
    if (visible(admin)) {
      const rect = admin.getBoundingClientRect();
      const position = window.getComputedStyle(admin).position;
      if (position === 'fixed' || position === 'sticky') offset = Math.max(0, Math.min(viewport() / 3, rect.bottom));
    }
    headerBottom = offset;
    document.querySelectorAll('.sn-header-bar').forEach(header => {
      if (!visible(header)) return;
      const position = window.getComputedStyle(header).position;
      if (position !== 'fixed' && position !== 'sticky') return;
      const rect = header.getBoundingClientRect();
      if (rect.top < viewport() / 3) headerBottom = Math.max(headerBottom, Math.min(viewport() / 2, rect.bottom));
    });
    const scroll = y();
    const max = maximum();
    panels.forEach(panel => {
      const rect = panel.node.getBoundingClientRect();
      panel.top = rect.top + scroll;
      panel.bottom = rect.bottom + scroll;
      // A larger editable header may cover a section's title even when its
      // native top padding is present. Expose only the missing clearance.
      const paddingTop = parseFloat(window.getComputedStyle(panel.node).paddingTop) || 0;
      const clearance = Math.max(offset, headerBottom > offset ? headerBottom + 12 - paddingTop : offset);
      panel.start = Math.min(max, Math.max(0, panel.top - clearance));
      panel.end = Math.min(max, Math.max(panel.start, panel.bottom - viewport()));
      // Ordinary 100vh panels should still step with a WordPress admin bar.
      // Taller content receives internal document scrolling before a transition.
      panel.tall = panel.bottom - panel.top > viewport() + EPSILON && panel.end - panel.start > EPSILON;
    });
    panels.sort((a, b) => a.top - b.top);
    root.style.setProperty('--sn-scroll-admin-offset', `${offset}px`);
  }

  function currentIndex(scroll = y()) {
    let index = -1;
    panels.forEach((panel, i) => { if (panel.start <= scroll + EPSILON) index = i; });
    return index;
  }

  function stopAnimation() {
    if (animationFrame) window.cancelAnimationFrame(animationFrame);
    animationFrame = 0;
    animation = null;
    root.classList.remove('sn-section-scroll-running');
  }

  function writeScroll(top) {
    lastWritten = Math.max(0, Math.min(maximum(), top));
    // 'instant' ignores the theme's CSS smooth behavior. The running class also
    // provides a fallback for engines using the older numeric scrollTo form.
    try { window.scrollTo({ top: lastWritten, left: window.scrollX || 0, behavior: 'instant' }); }
    catch (_) { window.scrollTo(window.scrollX || 0, lastWritten); }
  }

  function finishMove(target, options) {
    stopAnimation();
    settled = target ? { node: target.node, align: options.align || 'start' } : null;
    if (options.focus && target && target.node.isConnected) {
      const hadTabindex = target.node.hasAttribute('tabindex');
      if (!hadTabindex) target.node.setAttribute('tabindex', '-1');
      target.node.focus({ preventScroll: true });
      if (!hadTabindex) target.node.addEventListener('blur', () => target.node.removeAttribute('tabindex'), { once: true });
    }
    if (target) document.dispatchEvent(new CustomEvent('saponine:sectionchange', { detail: { id: target.node.id, index: panels.indexOf(target), source: options.source || 'api' } }));
  }

  function moveTo(top, options = {}) {
    stopAnimation();
    const from = y();
    const to = Math.max(0, Math.min(maximum(), top));
    const duration = reduced.matches || options.instant ? 0 : (options.duration === undefined ? config.duration : options.duration);
    const target = options.target || null;
    root.classList.add('sn-section-scroll-running');
    if (!duration || Math.abs(to - from) < 1) {
      writeScroll(to);
      finishMove(target, options);
      return;
    }
    const started = performance.now();
    lastWritten = from;
    animation = { direction: Math.sign(to - from), target, options, to };
    function tick(now) {
      if (!animation || destroyed) return;
      const progress = Math.min(1, Math.max(0, (now - started) / duration));
      const eased = progress < 0.5 ? 4 * progress * progress * progress : 1 - Math.pow(-2 * progress + 2, 3) / 2;
      writeScroll(from + (to - from) * eased);
      if (progress < 1) animationFrame = window.requestAnimationFrame(tick);
      else finishMove(target, options);
    }
    animationFrame = window.requestAnimationFrame(tick);
  }

  function refresh() {
    refreshFrame = 0;
    if (destroyed) return;
    const before = settled && panels.find(panel => panel.node === settled.node);
    const aligned = before && Math.abs(y() - (settled.align === 'end' ? before.end : before.start)) < EPSILON;
    const oldNodes = panels.map(panel => panel.node);
    const candidates = Array.from(document.querySelectorAll(selector)).filter(node =>
      visible(node) && !node.closest('[data-sn-scroll-ignore],dialog,.elementor-popup-modal') &&
      (/^sec-\d+$/.test(node.id) || node.matches('.sn-panel,.sn-hero-section,.sn-footer'))
    );
    panels = candidates.filter(node => !candidates.some(other => other !== node && other.contains(node))).map(node => ({ node }));
    measure();
    root.classList.toggle('sn-section-scroll-ready', ready());
    if (!ready()) stopAnimation();
    if (resizeObserver && (oldNodes.length !== panels.length || panels.some((panel, i) => oldNodes[i] !== panel.node))) {
      resizeObserver.disconnect();
      panels.forEach(panel => resizeObserver.observe(panel.node));
      const admin = document.getElementById('wpadminbar');
      if (admin) resizeObserver.observe(admin);
      document.querySelectorAll('.sn-header-bar').forEach(header => resizeObserver.observe(header));
    }
    if (animation) {
      const ongoing = animation;
      const target = ongoing.target && panels.find(panel => panel.node === ongoing.target.node);
      stopAnimation();
      if (target && ready()) moveTo(ongoing.options.align === 'end' ? target.end : target.start, { ...ongoing.options, target, duration: Math.min(config.duration, 300) });
    } else if (aligned && ready()) {
      const target = panels.find(panel => panel.node === settled.node);
      if (target) moveTo(settled.align === 'end' ? target.end : target.start, { target, align: settled.align, instant: true, source: 'resize' });
    }
  }

  function requestRefresh() {
    if (!destroyed && !refreshFrame) refreshFrame = window.requestAnimationFrame(refresh);
  }

  function goTo(destination, options = {}) {
    if (!ready()) return false;
    measure();
    let target;
    if (typeof destination === 'number') target = panels[destination];
    else if (typeof destination === 'string') target = panels.find(panel => panel.node.id === destination.replace(/^#/, ''));
    else target = panels.find(panel => panel.node === destination);
    if (!target) return false;
    moveTo(options.align === 'end' ? target.end : target.start, { ...options, target });
    return true;
  }

  function overlayOpen() {
    return Array.from(document.querySelectorAll('dialog[open],[aria-modal="true"]:not([hidden]),.elementor-popup-modal,.sp-nav.is-open')).some(visible);
  }

  function blocksNavigation(target, direction, keyboard = false) {
    if (!(target instanceof Element) || overlayOpen()) return true;
    const controls = keyboard ? 'input,textarea,select,button,a,[contenteditable]:not([contenteditable="false"]),video,audio,iframe' : 'input,textarea,select,[contenteditable]:not([contenteditable="false"]),video,audio,iframe';
    if (target.closest(controls + ',[data-sn-scroll-native],.sp-search[open]')) return true;
    for (let node = target; node && node !== document.body && node !== root; node = node.parentElement) {
      const style = window.getComputedStyle(node);
      if (/(auto|scroll|overlay)/.test(style.overflowY) && node.scrollHeight > node.clientHeight + 2) {
        if (direction > 0 && node.scrollTop < node.scrollHeight - node.clientHeight - 2) return true;
        if (direction < 0 && node.scrollTop > 2) return true;
      }
    }
    return false;
  }

  function planStep(direction) {
    measure();
    const scroll = y();
    const index = currentIndex(scroll);
    if (index < 0) return direction > 0 ? { target: panels[0], align: 'start' } : null;
    const panel = panels[index];
    if (panel.tall && ((direction > 0 && scroll < panel.end - EPSILON) || (direction < 0 && scroll > panel.start + EPSILON))) return { inside: panel };
    if (direction < 0 && scroll > panel.start + EPSILON) return { target: panel, align: 'start' };
    const target = panels[index + direction];
    if (!target) return null;
    return { target, align: direction < 0 && target.tall ? 'end' : 'start' };
  }

  function wheel(event) {
    if (!sectionMode() || !event.cancelable || event.ctrlKey || event.metaKey || event.shiftKey) return;
    const unit = event.deltaMode === 1 ? 16 : event.deltaMode === 2 ? viewport() : 1;
    const delta = event.deltaY * unit;
    if (!delta || Math.abs(event.deltaX) > Math.abs(event.deltaY) * 0.85) return;
    const direction = Math.sign(delta);
    if (blocksNavigation(event.target, direction)) return;
    touchDestination = null;
    window.clearTimeout(touchTimer);
    const now = performance.now();
    const reverse = gesture.direction && direction !== gesture.direction;
    if (now - gesture.last > GESTURE_IDLE || reverse) gesture = { last: now, direction, total: 0, used: false };
    gesture.last = now;
    gesture.direction = direction;
    if (reverse && animation && animation.direction !== direction) stopAnimation();
    if (animation || gesture.used) { event.preventDefault(); return; }
    const step = planStep(direction);
    if (!step) return;
    event.preventDefault();
    if (step.inside) {
      const panel = step.inside;
      const next = Math.max(panel.start, Math.min(panel.end, y() + delta));
      root.classList.add('sn-section-scroll-running');
      writeScroll(next);
      root.classList.remove('sn-section-scroll-running');
      settled = null;
      if (Math.abs(next - (direction > 0 ? panel.end : panel.start)) <= EPSILON) gesture.used = true;
      return;
    }
    gesture.total += Math.abs(delta);
    if (gesture.total < config.threshold) return;
    gesture.used = true;
    goTo(step.target.node, { align: step.align, source: 'wheel' });
  }

  function keyboard(event) {
    if (!sectionMode() || event.defaultPrevented || event.ctrlKey || event.metaKey || event.altKey) return;
    const key = event.key;
    if (!['PageDown', 'PageUp', ' ', 'Spacebar', 'Home', 'End'].includes(key)) return;
    const direction = key === 'PageUp' || ((key === ' ' || key === 'Spacebar') && event.shiftKey) || key === 'Home' ? -1 : 1;
    if (blocksNavigation(event.target, direction, true)) return;
    if (event.repeat) { event.preventDefault(); return; }
    touchDestination = null;
    stopAnimation();
    if (key === 'Home' || key === 'End') {
      event.preventDefault();
      goTo(key === 'Home' ? 0 : panels.length - 1, { align: key === 'End' ? 'end' : 'start', source: 'keyboard' });
      return;
    }
    const step = planStep(direction);
    if (!step) return;
    event.preventDefault();
    if (step.inside) {
      const distance = Math.max(120, (viewport() - headerBottom) * 0.9);
      moveTo(Math.max(step.inside.start, Math.min(step.inside.end, y() + distance * direction)), { source: 'keyboard', duration: Math.min(config.duration, 300) });
    } else goTo(step.target.node, { align: step.align, source: 'keyboard' });
  }

  function samePageHash(href) {
    let url;
    try { url = new URL(href, window.location.href); } catch (_) { return null; }
    if (url.origin !== window.location.origin || url.pathname !== window.location.pathname || url.search !== window.location.search || !url.hash) return null;
    let id;
    try { id = decodeURIComponent(url.hash.slice(1)); } catch (_) { return null; }
    const anchor = document.getElementById(id);
    if (anchor && anchor.matches('[data-sps-jump],.sn-brand-card')) {
      id = anchor.closest('.sn-panel')?.id || id;
    }
    return panels.some(panel => panel.node.id === id) ? { id, hash: url.hash } : null;
  }

  function click(event) {
    if (!ready() || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
    if (!link || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;
    const destination = samePageHash(link.href);
    if (!destination) return;
    event.preventDefault();
    touchDestination = null;
    if (window.location.hash !== destination.hash) window.history.pushState(null, '', destination.hash);
    goTo(destination.id, { source: 'anchor', focus: true });
  }

  function touchSettle() {
    window.clearTimeout(touchTimer);
    if (!touchDestination || touch || !sectionMode()) return;
    touchTimer = window.setTimeout(() => {
      if (!touchDestination || touch || !sectionMode() || overlayOpen()) return;
      const destination = touchDestination;
      touchDestination = null;
      goTo(destination.node, { align: destination.align, source: 'touch' });
    }, 160);
  }

  function pointerDown(event) {
    stopAnimation();
    gesture = { last: -Infinity, direction: 0, total: 0, used: false };
    touchDestination = null;
    window.clearTimeout(touchTimer);
    if (event.pointerType === 'touch' && event.isPrimary === false) { touch = null; return; }
    if (event.pointerType !== 'touch' || config.mobile !== 'sections' || !sectionMode() || blocksNavigation(event.target, 1)) return;
    measure();
    touch = { x: event.clientX, y: event.clientY, scroll: y(), index: currentIndex(), target: event.target };
  }

  function pointerUp(event) {
    if (!touch) return;
    const start = touch;
    touch = null;
    const dy = event.clientY - start.y;
    const dx = event.clientX - start.x;
    if (Math.abs(dy) < 48 || Math.abs(dx) > Math.abs(dy) * 0.75) return;
    const direction = dy < 0 ? 1 : -1;
    if (blocksNavigation(start.target, direction)) return;
    const panel = panels[start.index];
    if (!panel) return;
    // Let tall content scroll naturally. A new swipe at its boundary can move on.
    if (panel.tall && ((direction > 0 && start.scroll < panel.end - EPSILON) || (direction < 0 && start.scroll > panel.start + EPSILON))) return;
    const target = panels[start.index + direction];
    if (!target) return;
    touchDestination = { node: target.node, align: direction < 0 && target.tall ? 'end' : 'start' };
    touchSettle();
  }

  function scroll() {
    if (animation && Math.abs(y() - lastWritten) > 5) stopAnimation();
    if (touchDestination) touchSettle();
  }

  function hashChanged() {
    if (!ready()) return;
    const destination = samePageHash(window.location.href);
    if (destination) goTo(destination.id, { source: 'history', instant: true });
  }

  function destroy() {
    destroyed = true;
    stopAnimation();
    if (refreshFrame) window.cancelAnimationFrame(refreshFrame);
    window.clearTimeout(touchTimer);
    if (resizeObserver) resizeObserver.disconnect();
    if (mutationObserver) mutationObserver.disconnect();
    removers.forEach(remove => remove());
    root.classList.remove('sn-section-scroll-ready');
    root.style.removeProperty('--sn-scroll-admin-offset');
    panels = [];
  }

  window.SaponineSectionScroll = {
    refresh,
    goTo,
    destroy,
    getState: () => ({ enabled: ready(), sectionMode: sectionMode(), count: panels.length, current: currentIndex(), moving: Boolean(animation), offset, mobile: config.mobile, sections: panels.map(panel => ({ id: panel.node.id, start: panel.start, end: panel.end, tall: panel.tall })) })
  };

  function boot() {
    if (destroyed) return;
    if ('ResizeObserver' in window) resizeObserver = new ResizeObserver(requestRefresh);
    refresh();
    listen(window, 'wheel', wheel, { passive: false });
    listen(document, 'keydown', keyboard);
    listen(document, 'click', click);
    listen(document, 'pointerdown', pointerDown, { passive: true });
    listen(document, 'pointerup', pointerUp, { passive: true });
    listen(document, 'pointercancel', () => { touch = null; touchDestination = null; window.clearTimeout(touchTimer); }, { passive: true });
    listen(window, 'scroll', scroll, { passive: true });
    listen(window, 'resize', requestRefresh, { passive: true });
    listen(window, 'orientationchange', requestRefresh, { passive: true });
    listen(window, 'pageshow', requestRefresh);
    listen(window, 'pagehide', stopAnimation);
    listen(window, 'hashchange', hashChanged);
    listen(window, 'popstate', hashChanged);
    listen(window, 'load', requestRefresh);
    listen(document, 'load', event => { if (event.target instanceof HTMLImageElement) requestRefresh(); }, true);
    listen(reduced, 'change', () => {
      if (animation && reduced.matches) {
        const ongoing = animation;
        moveTo(ongoing.to, { ...ongoing.options, target: ongoing.target, instant: true });
      }
    });
    listen(smallScreen, 'change', requestRefresh);
    if (window.visualViewport) listen(window.visualViewport, 'resize', requestRefresh, { passive: true });
    if ('MutationObserver' in window && document.body) {
      mutationObserver = new MutationObserver(records => {
        const changed = records.some(record => record.type === 'attributes' || [...record.addedNodes, ...record.removedNodes].some(node => node instanceof Element && (node.matches(selector) || node.querySelector(selector))));
        if (changed) requestRefresh();
      });
      mutationObserver.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['id'] });
    }
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(requestRefresh).catch(() => {});
    if (window.jQuery) {
      window.jQuery(window).on('elementor/frontend/init.saponineSectionScroll', requestRefresh);
      removers.push(() => window.jQuery(window).off('elementor/frontend/init.saponineSectionScroll', requestRefresh));
    }
    hashChanged();
  }
  if (document.readyState === 'loading') listen(document, 'DOMContentLoaded', boot, { once: true });
  else boot();
})();
