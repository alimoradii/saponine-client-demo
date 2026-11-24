/** Saponine Studio widgets. Vanilla browser APIs; Elementor Free is optional at runtime. */
(() => {
  'use strict';
  const instances = new Map();
  const selector = '[data-sp-hero],[data-sp-nav],[data-sp-marquee],[data-sp-rail]';
  const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const editorMode = () => Boolean(window.elementorFrontend && window.elementorFrontend.isEditMode && window.elementorFrontend.isEditMode());
  const listen = (el, name, fn, removers, opts) => {
    el.addEventListener(name, fn, opts);
    removers.push(() => el.removeEventListener(name, fn, opts));
  };
  function milliseconds(value, fallback) {
    if (typeof value !== 'number' && !(typeof value === 'string' && value.trim())) return fallback;
    const number = Number(value);
    return Number.isFinite(number) && number >= 0 ? number : fallback;
  }

  function hero(root) {
    const removers = [];
    let config = {};
    try { config = JSON.parse(root.dataset.spHero || '{}'); } catch (_) { /* Use safe defaults. */ }
    const labels = config.labels || {};
    const slides = Array.from(root.querySelectorAll('.sp-hero__slide'));
    const dots = Array.from(root.querySelectorAll('[data-sp-slide]'));
    const pause = root.querySelector('[data-sp-pause]');
    const live = root.querySelector('.sp-hero__slides');
    const interval = milliseconds(config.interval, 6500);
    const duration = milliseconds(config.duration, 1200);
    let index = Math.max(0, slides.findIndex(slide => slide.classList.contains('is-active')));
    let timer = 0;
    let disposed = false;
    const keyboardFocusInside = () => root.contains(document.activeElement) && document.activeElement.matches(':focus-visible');
    let focusPaused = keyboardFocusInside();
    let hoverPaused = false;
    let ignoreHoverUntilLeave = false;
    let userPaused = false;
    let inView = true;
    let touchStart = null;
    const editor = editorMode();
    root.classList.toggle('is-editor', editor);
    root.style.setProperty('--sp-hero-duration', `${duration}ms`);
    root.classList.toggle('sp-hero--instant', duration === 0);
    const canPlay = () => config.autoplay && interval > 0 && slides.length > 1 && !userPaused && !focusPaused && !hoverPaused && !motion.matches && !document.hidden && inView && !editor;

    function schedule() {
      if (disposed) return;
      window.clearTimeout(timer);
      const playing = canPlay();
      if (live) live.setAttribute('aria-live', playing ? 'off' : 'polite');
      if (!playing) return;
      // Each browser timer can wait at most 2^31 - 1 ms. Keep one cancellable timer for longer waits.
      const maxTimeout = 2147483647;
      const deadline = performance.now() + interval;
      function advance() {
        const remaining = deadline - performance.now();
        if (remaining > 0) timer = window.setTimeout(advance, Math.min(Math.ceil(remaining), maxTimeout));
        else show(index + 1, false);
      }
      timer = window.setTimeout(advance, Math.min(Math.ceil(interval), maxTimeout));
    }

    function show(next, manual) {
      if (!slides.length) return;
      index = ((Number(next) % slides.length) + slides.length) % slides.length;
      slides.forEach((slide, i) => {
        const active = i === index;
        slide.classList.toggle('is-active', active);
        slide.setAttribute('aria-hidden', active ? 'false' : 'true');
        slide.inert = !active;
      });
      dots.forEach((dot, i) => dot.setAttribute('aria-pressed', i === index ? 'true' : 'false'));
      if (manual && live) live.setAttribute('aria-live', 'polite');
      schedule();
    }

    function paintPause() {
      if (!pause) return;
      const stopped = userPaused || motion.matches || editor;
      pause.setAttribute('aria-pressed', stopped ? 'true' : 'false');
      pause.setAttribute('aria-label', stopped ? (labels.play || 'Play slideshow') : (labels.pause || 'Pause slideshow'));
      const icon = pause.querySelector('span');
      if (icon) icon.textContent = stopped ? '▷' : 'Ⅱ';
      pause.disabled = motion.matches || editor;
      if (motion.matches) pause.setAttribute('aria-label', labels.reduced || 'Autoplay disabled by reduced motion preference');
      if (editor) pause.setAttribute('aria-label', labels.editor || 'Autoplay paused in the editor; use the slide controls');
    }

    function pauseForFocus(value) {
      if (focusPaused === value) return;
      focusPaused = value;
      schedule();
    }

    function pauseForHover(value) {
      if (hoverPaused === value) return;
      hoverPaused = value;
      schedule();
    }

    dots.forEach(dot => listen(dot, 'click', () => show(Number(dot.dataset.spSlide), true), removers));
    const prev = root.querySelector('[data-sp-prev]');
    const next = root.querySelector('[data-sp-next]');
    if (prev) listen(prev, 'click', () => show(index - 1, true), removers);
    if (next) listen(next, 'click', () => show(index + 1, true), removers);
    if (pause) listen(pause, 'click', () => {
      userPaused = !userPaused;
      if (!userPaused) {
        // Play takes effect while the activating button still has focus or hover.
        ignoreHoverUntilLeave = hoverPaused;
        hoverPaused = false;
        focusPaused = false;
      }
      paintPause();
      schedule();
    }, removers);
    listen(root, 'focusin', event => pauseForFocus(event.target.matches(':focus-visible')), removers);
    listen(root, 'focusout', event => { if (!root.contains(event.relatedTarget)) pauseForFocus(false); }, removers);
    if (config.pauseHover) {
      // Scrolling a full-screen slide underneath the cursor fires enter events.
      // Only a moving mouse (or a mouse click below) expresses hover intent.
      listen(root, 'pointermove', event => {
        if (event.pointerType === 'mouse' && !ignoreHoverUntilLeave && (event.movementX || event.movementY)) pauseForHover(true);
      }, removers, { passive: true });
      listen(root, 'pointerleave', event => {
        if (event.pointerType !== 'mouse') return;
        ignoreHoverUntilLeave = false;
        pauseForHover(false);
      }, removers);
    }
    listen(root, 'keydown', event => {
      pauseForFocus(true);
      if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
      if (event.target.matches('input,textarea,select,[contenteditable]')) return;
      event.preventDefault();
      show(index + (event.key === 'ArrowRight' ? 1 : -1), true);
    }, removers);
    listen(root, 'pointerdown', event => {
      pauseForFocus(false);
      if (config.pauseHover && event.pointerType === 'mouse' && !ignoreHoverUntilLeave) pauseForHover(true);
      if (event.pointerType === 'touch') {
        pauseForHover(false);
        touchStart = event.isPrimary !== false && !event.target.closest('a,button')
          ? { id: event.pointerId, x: event.clientX, y: event.clientY } : null;
      }
    }, removers, { passive: true });
    listen(root, 'pointerup', event => {
      if (!touchStart || event.pointerId !== touchStart.id) return;
      const dx = event.clientX - touchStart.x;
      const dy = event.clientY - touchStart.y;
      touchStart = null;
      if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5) show(index + (dx < 0 ? 1 : -1), true);
    }, removers, { passive: true });
    listen(root, 'pointercancel', () => { touchStart = null; }, removers, { passive: true });
    function resumeWhenVisible() {
      // Hover can end without a leave event while the page is suspended.
      hoverPaused = false;
      ignoreHoverUntilLeave = false;
      schedule();
    }
    listen(document, 'visibilitychange', resumeWhenVisible, removers);
    listen(window, 'pageshow', event => { if (event.persisted) resumeWhenVisible(); }, removers);
    listen(motion, 'change', () => { paintPause(); schedule(); }, removers);
    let observer;
    if ('IntersectionObserver' in window) {
      observer = new IntersectionObserver(entries => {
        const visible = entries[entries.length - 1].isIntersecting;
        if (inView === visible) return;
        inView = visible;
        if (!inView) {
          hoverPaused = false;
          ignoreHoverUntilLeave = false;
        }
        schedule();
      }, { threshold: 0.05 });
      observer.observe(root);
    }
    paintPause();
    show(index, false);
    return () => { disposed = true; window.clearTimeout(timer); if (observer) observer.disconnect(); removers.forEach(fn => fn()); };
  }

  function navigation(root) {
    const removers = [];
    const toggle = root.querySelector('.sp-nav__toggle');
    if (!toggle) return () => {};
    const editing = document.body.classList.contains('elementor-editor-active') || new URLSearchParams(location.search).has('elementor-preview');
    const header = editing ? null : root.closest('.sn-header-compact');
    const list = root.querySelector('.sp-nav__items');
    const language = header ? header.querySelector('.sn-header-actions .elementor-widget-saponine-language-switcher') : null;
    const origin = language ? document.createComment('saponine-languages') : null;
    const slot = language && list ? document.createElement('li') : null;
    const search = header ? header.querySelector('.sp-search') : null;
    let collapsed = false;
    if (origin) language.before(origin);
    if (slot) slot.className = 'sn-header-menu-actions';
    root.classList.add('is-ready');
    const setOpen = (open, focus) => {
      root.classList.toggle('is-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (focus) toggle.focus({ preventScroll: true });
    };
    const restoreLanguage = () => {
      if (language && origin && origin.parentNode) origin.parentNode.insertBefore(language, origin.nextSibling);
      if (slot) slot.remove();
    };
    const update = () => {
      if (header) {
        const narrow = window.matchMedia('(max-width:1024px)').matches;
        if (narrow !== collapsed) {
          const focusedLanguage = language && language.contains(document.activeElement) ? document.activeElement : null;
          if (narrow && slot) {
            slot.append(language);
            list.append(slot);
          } else if (!narrow) {
            restoreLanguage();
          }
          collapsed = narrow;
          setOpen(narrow && Boolean(focusedLanguage));
          if (focusedLanguage) focusedLanguage.focus({ preventScroll: true });
        }
        header.classList.add('sn-header-enhanced');
      } else if (window.getComputedStyle(toggle).display === 'none') {
        setOpen(false);
      }
    };
    listen(toggle, 'click', () => {
      if (search && search.open) search.open = false;
      setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    }, removers);
    listen(root, 'click', event => { if (event.target.closest(header ? 'a[href]' : '.sp-nav__link')) setOpen(false); }, removers);
    listen(root, 'keydown', event => {
      if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') { event.preventDefault(); setOpen(false, true); }
      if (event.key === 'ArrowDown' && event.target === toggle && (collapsed || !header)) {
        event.preventDefault();setOpen(true);
        const first = list && list.querySelector('a[href]');
        if (first) first.focus();
      }
    }, removers);
    listen(document, 'pointerdown', event => { if (!root.contains(event.target)) setOpen(false); }, removers);
    if (search) {
      listen(search, 'toggle', () => {
        if (!search.open) return;
        setOpen(false);
        search.querySelector('input[type="search"]')?.focus({ preventScroll: true });
      }, removers);
      listen(search, 'keydown', event => {
        if (event.key !== 'Escape' || !search.open) return;
        event.preventDefault();
        search.open = false;
        search.querySelector('summary')?.focus({ preventScroll: true });
      }, removers);
      listen(document, 'pointerdown', event => {
        if (search.open && !search.contains(event.target)) search.open = false;
      }, removers);
    }
    listen(root, 'focusout', event => { if (!root.contains(event.relatedTarget)) setOpen(false); }, removers);
    listen(window, 'resize', update, removers, { passive: true });
    update();
    return () => {
      removers.forEach(fn => fn());
      if (collapsed) restoreLanguage();
      if (origin) origin.remove();
      if (header) header.classList.remove('sn-header-enhanced');
      root.classList.remove('is-ready', 'is-open');toggle.setAttribute('aria-expanded', 'false');
    };
  }

  function marquee(root) {
    const removers = [];
    const button = root.querySelector('[data-sp-marquee-pause]');
    if (!button) return () => {};
    let paused = false;
    let labels = {};
    try { labels = JSON.parse(root.dataset.spLabels || '{}'); } catch (_) { /* Use safe fallback labels. */ }
    const circle = root.classList.contains('sp-marquee--circle');
    function paint() {
      const stopped = paused || motion.matches;
      root.classList.toggle('is-paused', stopped);
      button.setAttribute('aria-pressed', stopped ? 'true' : 'false');
      button.disabled = motion.matches;
      button.setAttribute('aria-label', motion.matches ? (labels.reduced || 'Animation disabled by reduced motion preference') : stopped ? (labels.play || 'Resume animation') : (labels.pause || 'Pause animation'));
      if (!circle) { const icon = button.querySelector('span'); if (icon) icon.textContent = stopped ? '▷' : 'Ⅱ'; }
    }
    listen(button, 'click', () => { paused = !paused; paint(); }, removers);
    listen(motion, 'change', paint, removers);
    paint();
    return () => { removers.forEach(fn => fn()); };
  }

  function rail(root) {
    const removers = [];
    const label = root.querySelector('.sp-rail__label');
    const initial = label ? label.textContent : '';
    const links = Array.from(root.querySelectorAll('a[href]'));
    let pairs = [];
    let frame = 0;
    let observer;
    function mark(active) {
      links.forEach(link => { if (link === active) link.setAttribute('aria-current', 'location'); else link.removeAttribute('aria-current'); });
      if (label && root.dataset.activeLabel === 'yes') {
        const text = active ? active.getAttribute('aria-label') : initial;
        if (label.textContent !== text) label.textContent = text;
      }
    }
    function update() {
      frame = 0;
      const center = window.innerHeight * 0.43;
      let best = null;
      let distance = Infinity;
      pairs.forEach(pair => {
        const rect = pair.section.getBoundingClientRect();
        const d = rect.top <= center && rect.bottom >= center ? 0 : Math.min(Math.abs(rect.top - center), Math.abs(rect.bottom - center));
        if (rect.height > 0 && d < distance) { best = pair.link; distance = d; }
      });
      mark(best);
    }
    function requestUpdate() { if (!frame) frame = window.requestAnimationFrame(update); }
    function findSections() {
      if (observer) observer.disconnect();
      pairs = links.map(link => {
        let destination;
        try { destination = new URL(link.getAttribute('href'), window.location.href); } catch (_) { return null; }
        // Cross-language or other-page links must navigate normally, never mark local sections.
        if (destination.origin !== window.location.origin || destination.pathname !== window.location.pathname || destination.search !== window.location.search || !destination.hash) return null;
        let anchor;
        try { anchor = decodeURIComponent(destination.hash.slice(1)); } catch (_) { return null; }
        return { link, section: document.getElementById(anchor) };
      }).filter(pair => pair && pair.section);
      if ('IntersectionObserver' in window) {
        observer = new IntersectionObserver(requestUpdate, { rootMargin: '-25% 0px -25% 0px', threshold: [0, .2, .5, 1] });
        pairs.forEach(pair => observer.observe(pair.section));
      }
      update();
    }
    // A lightweight rAF update also covers long sections and exact viewport centers.
    listen(window, 'scroll', requestUpdate, removers, { passive: true });
    listen(window, 'resize', requestUpdate, removers, { passive: true });
    listen(window, 'load', findSections, removers);
    listen(root, 'click', event => { const link = event.target.closest('a'); if (link && root.contains(link)) mark(link); }, removers);
    findSections();
    return () => { if (observer) observer.disconnect(); window.cancelAnimationFrame(frame); removers.forEach(fn => fn()); };
  }

  function init(root, force) {
    if (!root || !root.isConnected) return;
    if (instances.has(root)) { if (!force) return; instances.get(root)(); instances.delete(root); }
    let destroy;
    if (root.hasAttribute('data-sp-hero')) destroy = hero(root);
    else if (root.hasAttribute('data-sp-nav')) destroy = navigation(root);
    else if (root.hasAttribute('data-sp-marquee')) destroy = marquee(root);
    else if (root.hasAttribute('data-sp-rail')) destroy = rail(root);
    if (destroy) instances.set(root, destroy);
  }
  function scan(scope, force = false) {
    if (!scope || !scope.querySelectorAll) return;
    if (scope.matches && scope.matches(selector)) init(scope, force);
    scope.querySelectorAll(selector).forEach(el => init(el, force));
  }

  let hooked = false;
  function elementorHooks() {
    if (hooked || !window.elementorFrontend || !window.elementorFrontend.hooks) return;
    hooked = true;
    ['saponine-hero-slider', 'saponine-navigation', 'saponine-marquee', 'saponine-section-rail'].forEach(name => {
      window.elementorFrontend.hooks.addAction(`frontend/element_ready/${name}.default`, scope => scan(scope[0] || scope, true));
    });
  }
  function boot() {
    scan(document);
    elementorHooks();
    const observer = new MutationObserver(mutations => {
      for (const [el, destroy] of instances) if (!el.isConnected) { destroy(); instances.delete(el); }
      mutations.forEach(mutation => mutation.addedNodes.forEach(node => { if (node.nodeType === 1) scan(node); }));
    });
    observer.observe(document.body, { childList: true, subtree: true });
    window.addEventListener('pagehide', event => {
      if (event.persisted) return;
      observer.disconnect();
      instances.forEach(destroy => destroy());
      instances.clear();
    }, { once: true });
  }
  if (window.jQuery) window.jQuery(window).on('elementor/frontend/init.saponineSupport', elementorHooks);
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
  else boot();
})();
