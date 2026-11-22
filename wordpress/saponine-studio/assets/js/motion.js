/* Saponine Studio motion: local, dependency-free effects with editor lifecycle cleanup. */
(function () {
  'use strict';
  const instances = new WeakMap();
  let elementorHooked = false;
  const reduce = matchMedia('(prefers-reduced-motion: reduce)');
  const mobile = matchMedia('(max-width: 767px)');
  const clamp = (n, min, max, fallback) => Math.max(min, Math.min(max, Number.isFinite(Number(n)) ? Number(n) : fallback));
  const listenMedia = (query, fn) => {
    if (query.addEventListener) { query.addEventListener('change', fn); return () => query.removeEventListener('change', fn); }
    query.addListener(fn); return () => query.removeListener(fn);
  };

  function normalize(raw) {
    const effects = ['none', 'reveal', 'float', 'rotate', 'tilt', 'parallax'];
    return {
      effect: effects.includes(raw.effect) ? raw.effect : 'none',
      duration: clamp(raw.duration, 100, 60000, 1200), delay: clamp(raw.delay, 0, 10000, 0),
      distance: clamp(raw.distance, -300, 300, 24), rotation: clamp(raw.rotation, 0, 45, 6),
      trigger: ['load', 'hover', 'viewport'].includes(raw.trigger) ? raw.trigger : 'viewport',
      easing: ['ease', 'ease-in-out', 'linear', 'cubic-bezier(.22,.8,.18,1)'].includes(raw.easing) ? raw.easing : 'ease-in-out',
      repeat: raw.repeat === true || raw.repeat === 'yes', mobile: raw.mobile !== false && raw.mobile !== ''
    };
  }

  function read(element) {
    try { return JSON.parse(element.getAttribute('data-sn-motion') || '{}'); } catch (_) { return {}; }
  }

  function create(element, raw) {
    const previous = instances.get(element);
    if (previous) previous.destroy();
    const settings = normalize(raw);
    if (settings.effect === 'none') {
      const empty = { destroy() { if (instances.get(element) === empty) instances.delete(element); } };
      instances.set(element, empty);
      return empty;
    }
    const target = element.querySelector(':scope > .elementor-widget-container') || element;
    let animation = null, observer = null, raf = 0, visible = true, seen = false, running = false;
    const disposers = [];
    const on = (node, event, fn, options) => {
      node.addEventListener(event, fn, options);
      disposers.push(() => node.removeEventListener(event, fn, options));
    };
    const allowed = () => !reduce.matches && (settings.mobile || !mobile.matches) && settings.effect !== 'none' && typeof target.animate === 'function';
    const stop = () => { if (animation) { animation.cancel(); animation = null; } running = false; cancelAnimationFrame(raf); };
    const play = () => {
      if (!allowed() || running) return;
      if (settings.effect === 'reveal' && seen && !settings.repeat) return;
      stop();
      let frames, iterations = 1;
      if (settings.effect === 'reveal') frames = [{ opacity: 0, translate: `0 ${settings.distance}px` }, { opacity: 1, translate: '0 0' }];
      if (settings.effect === 'float') { frames = [{ translate: '0 0' }, { translate: `0 ${-settings.distance}px` }, { translate: '0 0' }]; iterations = Infinity; }
      if (settings.effect === 'rotate') { frames = [{ rotate: `${-settings.rotation}deg` }, { rotate: `${settings.rotation}deg` }, { rotate: `${-settings.rotation}deg` }]; iterations = Infinity; }
      if (!frames) return;
      running = true; seen = true;
      const active = target.animate(frames, { duration: settings.duration, delay: settings.delay, easing: settings.easing, iterations, fill: 'both' });
      animation = active;
      active.onfinish = () => { if (animation === active) { active.cancel(); animation = null; running = false; } };
    };
    const refresh = () => { stop(); if (allowed() && visible && settings.trigger !== 'hover') { seen = false; play(); } };

    if (settings.effect === 'tilt') {
      on(element, 'pointermove', event => {
        if (!allowed() || event.pointerType === 'touch') return;
        cancelAnimationFrame(raf);
        raf = requestAnimationFrame(() => {
          const rect = element.getBoundingClientRect();
          const x = ((event.clientX - rect.left) / Math.max(1, rect.width) - 0.5) * settings.rotation * 2;
          const y = ((event.clientY - rect.top) / Math.max(1, rect.height) - 0.5) * settings.rotation * -2;
          if (animation) animation.cancel();
          const frame = { transform: `perspective(1000px) rotateX(${y}deg) rotateY(${x}deg)` };
          animation = target.animate([frame, frame], { duration: 1, fill: 'forwards', composite: 'add' });
        });
      }, { passive: true });
      on(element, 'pointerleave', stop, { passive: true });
      on(element, 'blur', stop, true);
    } else if (settings.effect === 'parallax') {
      const update = () => {
        if (!allowed() || !visible) return;
        cancelAnimationFrame(raf);
        raf = requestAnimationFrame(() => {
          const rect = element.getBoundingClientRect();
          const progress = Math.max(-1, Math.min(1, (rect.top + rect.height / 2 - innerHeight / 2) / Math.max(1, innerHeight)));
          if (animation) animation.cancel();
          const frame = { translate: `0 ${progress * settings.distance}px` };
          animation = target.animate([frame, frame], { duration: 1, fill: 'forwards' });
        });
      };
      on(window, 'scroll', update, { passive: true });
      on(window, 'resize', update, { passive: true });
      if ('IntersectionObserver' in window) {
        observer = new IntersectionObserver(entries => { visible = entries[0].isIntersecting; if (visible) update(); }, { rootMargin: '100px' });
        observer.observe(element);
      }
      update();
    } else if (settings.trigger === 'hover') {
      on(element, 'pointerenter', play, { passive: true });
      on(element, 'pointerleave', () => { stop(); seen = false; }, { passive: true });
      on(element, 'focusin', play);
      on(element, 'focusout', event => { if (!element.contains(event.relatedTarget)) { stop(); seen = false; } });
    } else if (settings.trigger === 'viewport' && 'IntersectionObserver' in window) {
      observer = new IntersectionObserver(entries => {
        visible = entries[0].isIntersecting;
        if (visible) play();
        else if (settings.effect !== 'reveal' || settings.repeat) { stop(); }
      }, { threshold: 0.08 });
      observer.observe(element);
    } else { play(); }

    disposers.push(listenMedia(reduce, refresh), listenMedia(mobile, refresh));
    on(document, 'visibilitychange', () => {
      if (!animation) return;
      if (document.hidden) animation.pause(); else animation.play();
    });
    const instance = { destroy() { stop(); if (observer) observer.disconnect(); disposers.forEach(dispose => dispose()); if (instances.get(element) === instance) instances.delete(element); } };
    instances.set(element, instance);
    return instance;
  }

  function scan(root) {
    if (root.matches && root.matches('[data-sn-motion]')) create(root, read(root));
    root.querySelectorAll('[data-sn-motion]').forEach(element => create(element, read(element)));
  }

  function bootElementor() {
    if (elementorHooked || !window.elementorFrontend || !window.elementorModules) return;
    elementorHooked = true;
    class MotionHandler extends window.elementorModules.frontend.handlers.Base {
      onInit() { super.onInit(); this.refreshMotion(); }
      refreshMotion() {
        const element = this.$element[0];
        let raw = read(element);
        const all = this.getElementSettings();
        if (all && Object.prototype.hasOwnProperty.call(all, 'sn_motion_effect')) {
          raw = {};
          ['effect', 'duration', 'delay', 'distance', 'rotation', 'trigger', 'repeat', 'mobile', 'easing'].forEach(key => { raw[key] = all['sn_motion_' + key]; });
        }
        this.motion = create(element, raw);
      }
      onElementChange(name) { if (name.indexOf('sn_motion_') === 0) this.refreshMotion(); }
      onDestroy() { if (this.motion) this.motion.destroy(); super.onDestroy(); }
    }
    window.elementorFrontend.hooks.addAction('frontend/element_ready/global', $scope => {
      window.elementorFrontend.elementsHandler.addHandler(MotionHandler, { $element: $scope });
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => scan(document), { once: true }); else scan(document);
  if (window.jQuery) window.jQuery(window).on('elementor/frontend/init', bootElementor);
  if (window.elementorFrontend && window.elementorFrontend.elementsHandler) bootElementor();
  window.SaponineMotion = { init: scan, destroy: element => { const instance = instances.get(element); if (instance) instance.destroy(); } };
})();
