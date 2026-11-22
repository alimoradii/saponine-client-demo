/* Saponine product controls. Local, framework-independent, safe in Elementor preview. */
(() => {
  'use strict';

  const instances = new WeakMap();
  const activeInstances = new Set();
  const openDialogs = new Set();
  const motionKeys = new Set([
    'float_enabled', 'tilt_enabled', 'ring_spin', 'float_amplitude',
    'float_duration', 'tilt_amount', 'ring_duration', 'transition_type',
    'transition_duration', 'modal_animation', 'modal_duration', 'motion_easing', 'motion_mobile'
  ]);
  const easeValues = {
    smooth: 'cubic-bezier(.22,.8,.18,1)',
    'ease-in-out': 'ease-in-out',
    'ease-out': 'ease-out',
    linear: 'linear'
  };
  let originalOverflow = '';
  let observer;
  let elementorRegistered = false;

  const bounded = (value, fallback, min, max) => {
    const number = Number.parseFloat(value);
    return Number.isFinite(number) ? Math.min(max, Math.max(min, number)) : fallback;
  };
  const enabled = value => value === 'yes' || value === true;

  function lockScroll(dialog) {
    if (!openDialogs.size) {
      originalOverflow = document.documentElement.style.overflow;
      document.documentElement.style.overflow = 'hidden';
    }
    openDialogs.add(dialog);
  }

  function unlockScroll(dialog) {
    openDialogs.delete(dialog);
    if (!openDialogs.size) document.documentElement.style.overflow = originalOverflow;
  }

  class ProductShowcase {
    constructor(root) {
      this.root = root;
      this.panels = Array.from(root.querySelectorAll('[data-sps-panel]'));
      this.tabs = Array.from(root.querySelectorAll('[data-sps-select]'));
      this.dialog = root.querySelector('[data-sps-dialog]');
      this.status = root.querySelector('[data-sps-status]');
      this.selected = 0;
      this.animations = new Set();
      this.listeners = [];
      this.frame = 0;
      this.modalFrame = 0;
      this.modalVersion = 0;
      this.closing = false;
      this.destroyed = false;
      this.reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
      this.mobile = window.matchMedia('(max-width: 767px)');
      try { this.raw = JSON.parse(root.dataset.spsConfig || '{}'); } catch (_) { this.raw = {}; }
      this.update();
      this.bind();
      this.panels.forEach((panel, index) => {
        if (this.tabs[index]) {
          panel.setAttribute('role', 'tabpanel');
          panel.setAttribute('aria-labelledby', this.tabs[index].id);
        }
      });
      if ('IntersectionObserver' in window) {
        this.visibility = new IntersectionObserver(entries => {
          entries.forEach(entry => { this.root.dataset.spsInview = String(entry.isIntersecting); });
        }, { threshold: 0 });
        this.visibility.observe(root);
      }
      activeInstances.add(this);
    }

    listen(target, name, handler, options) {
      target.addEventListener(name, handler, options);
      this.listeners.push(() => target.removeEventListener(name, handler, options));
    }

    update(values = {}) {
      Object.assign(this.raw, values);
      const raw = this.raw;
      this.motionAllowed = !this.reduced.matches && (enabled(raw.motion_mobile) || !this.mobile.matches);
      this.config = {
        float: enabled(raw.float_enabled),
        tilt: enabled(raw.tilt_enabled),
        spin: enabled(raw.ring_spin),
        amplitude: bounded(raw.float_amplitude, 13, 0, 80),
        floatDuration: bounded(raw.float_duration, 6.5, .5, 60),
        tiltAmount: bounded(raw.tilt_amount, 11, 0, 40),
        ringDuration: bounded(raw.ring_duration, 34, 2, 180),
        transition: ['none', 'fade', 'slide', 'scale', 'rotate'].includes(raw.transition_type) ? raw.transition_type : 'rotate',
        transitionDuration: bounded(raw.transition_duration, 720, 0, 3000),
        modalAnimation: ['none', 'fade', 'slide', 'scale', 'flip'].includes(raw.modal_animation) ? raw.modal_animation : 'flip',
        modalDuration: bounded(raw.modal_duration, 850, 0, 3000),
        easing: easeValues[raw.motion_easing] || easeValues.smooth
      };
      this.root.style.setProperty('--sps-float-amplitude', `${this.config.amplitude}px`);
      this.root.style.setProperty('--sps-float-duration', `${this.config.floatDuration}s`);
      this.root.style.setProperty('--sps-ring-duration', `${this.config.ringDuration}s`);
      this.root.style.setProperty('--sps-easing', this.config.easing);
      this.root.classList.toggle('sps-motion-float', this.motionAllowed && this.config.float);
      this.root.classList.toggle('sps-motion-ring', this.motionAllowed && this.config.spin);
      if (!this.motionAllowed || !this.config.tilt) {
        this.root.querySelectorAll('.sps-stage').forEach(stage => this.resetTilt(stage));
      }
      if (!this.motionAllowed) this.cancelAnimations();
    }

    bind() {
      const destinations = new Map(Array.from(this.root.querySelectorAll('[data-sps-jump]'),
        anchor => ['#' + anchor.id, Number(anchor.dataset.spsJump)]));
      const selectFromLocation = () => {
        if (destinations.has(location.hash)) this.select(destinations.get(location.hash));
      };
      this.listen(window, 'hashchange', selectFromLocation);
      this.listen(window, 'popstate', selectFromLocation);
      this.listen(document, 'click', event => {
        if (event.button !== 0 || event.ctrlKey || event.shiftKey || event.altKey || event.metaKey) return;
        const link = event.target.closest?.('a[href]');
        if (link && (!link.target || link.target === '_self') && link.origin === location.origin &&
            link.pathname === location.pathname && link.search === location.search && destinations.has(link.hash)) {
          this.select(destinations.get(link.hash));
        }
      });
      selectFromLocation();
      this.listen(this.root, 'click', event => {
        const target = event.target instanceof Element ? event.target : null;
        if (!target) return;
        const select = target.closest('[data-sps-select]');
        const step = target.closest('[data-sps-step]');
        const open = target.closest('[data-sps-open]');
        if (select && this.root.contains(select)) {
          this.select(Number(select.dataset.spsSelect));
        } else if (step && this.root.contains(step)) {
          const offset = Number(step.dataset.spsStep);
          this.select(this.selected + offset);
          this.panels[this.selected].querySelector(`[data-sps-step="${offset}"]`)?.focus({ preventScroll: true });
        } else if (open && this.root.contains(open)) {
          event.preventDefault();
          this.openModal();
        }
      });
      this.listen(this.root, 'keydown', event => {
        const tab = event.target.closest?.('[data-sps-select]');
        if (!tab || !this.tabs.includes(tab)) return;
        const index = this.tabs.indexOf(tab);
        const tabDirection = window.getComputedStyle(tab.closest('[role="tablist"]')).direction === 'rtl' ? -1 : 1;
        let next;
        if (event.key === 'ArrowRight') next = index + tabDirection;
        if (event.key === 'ArrowLeft') next = index - tabDirection;
        if (event.key === 'Home') next = 0;
        if (event.key === 'End') next = this.panels.length - 1;
        if (next === undefined) return;
        event.preventDefault();
        this.select(next, true);
      });
      this.root.querySelectorAll('.sps-stage').forEach(stage => {
        this.listen(stage, 'pointermove', event => {
          if (!this.motionAllowed || !this.config.tilt || event.pointerType === 'touch') return;
          const x = event.clientX, y = event.clientY;
          cancelAnimationFrame(this.frame);
          this.frame = requestAnimationFrame(() => {
            const rect = stage.getBoundingClientRect();
            const rotateY = ((x - rect.left) / Math.max(1, rect.width) - .5) * this.config.tiltAmount;
            const rotateX = -((y - rect.top) / Math.max(1, rect.height) - .5) * this.config.tiltAmount * .64;
            stage.style.setProperty('--sps-tilt-x', `${rotateX.toFixed(2)}deg`);
            stage.style.setProperty('--sps-tilt-y', `${rotateY.toFixed(2)}deg`);
          });
        }, { passive: true });
        this.listen(stage, 'pointerleave', () => { cancelAnimationFrame(this.frame); this.resetTilt(stage); }, { passive: true });
      });
      const onPreferenceChange = () => this.update();
      [this.reduced, this.mobile].forEach(query => {
        if (query.addEventListener) this.listen(query, 'change', onPreferenceChange);
        else {
          query.addListener(onPreferenceChange);
          this.listeners.push(() => query.removeListener(onPreferenceChange));
        }
      });
      if (!this.dialog) return;
      this.listen(this.dialog, 'click', event => {
        if (event.target === this.dialog || event.target.closest?.('[data-sps-close]')) this.closeModal();
      });
      this.listen(this.dialog, 'cancel', event => { event.preventDefault(); this.closeModal(); });
      this.listen(this.dialog, 'keydown', event => {
        if (event.key === 'Escape') { event.preventDefault(); this.closeModal(); }
        if (event.key !== 'Tab') return;
        const focusable = Array.from(this.dialog.querySelectorAll('button:not([disabled]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'))
          .filter(element => element.getClientRects().length && !element.hidden);
        if (!focusable.length) { event.preventDefault(); this.dialog.focus(); return; }
        const first = focusable[0], last = focusable[focusable.length - 1];
        if (event.shiftKey && (document.activeElement === first || document.activeElement === this.dialog)) {
          event.preventDefault(); last.focus();
        } else if (!event.shiftKey && (document.activeElement === last || document.activeElement === this.dialog)) {
          event.preventDefault(); first.focus();
        }
      });
      // Retain keyboard containment if an older browser lacks native dialog support.
      this.listen(document, 'focusin', event => {
        if (this.dialog.classList.contains('sps-modal-fallback') && this.dialog.hasAttribute('open') && !this.dialog.contains(event.target)) {
          this.dialog.querySelector('[data-sps-close]')?.focus({ preventScroll: true });
        }
      });
    }

    resetTilt(stage) {
      stage.style.removeProperty('--sps-tilt-x');
      stage.style.removeProperty('--sps-tilt-y');
    }

    animate(element, frames, duration) {
      if (!element || !this.motionAllowed || duration <= 0 || !element.animate) return Promise.resolve();
      const animation = element.animate(frames, { duration, easing: this.config.easing, fill: 'none' });
      this.animations.add(animation);
      return animation.finished.catch(() => {}).finally(() => this.animations.delete(animation));
    }

    cancelAnimations() {
      this.animations.forEach(animation => animation.cancel());
      this.animations.clear();
      this.dialog?.querySelectorAll('.sps-flip').forEach(ghost => ghost.remove());
      const image = this.dialog?.querySelector('.sps-modal-image');
      if (image) image.style.opacity = '';
    }

    select(index, focus = false) {
      if (!Number.isFinite(index) || !this.panels.length || this.dialog?.hasAttribute('open')) return;
      const next = ((index % this.panels.length) + this.panels.length) % this.panels.length;
      if (next === this.selected) { if (focus) this.tabs[next]?.focus(); return; }
      this.cancelAnimations();
      this.selected = next;
      this.panels.forEach((panel, i) => { panel.hidden = i !== next; });
      this.tabs.forEach((tab, i) => {
        tab.setAttribute('aria-selected', String(i === next));
        tab.tabIndex = i === next ? 0 : -1;
      });
      if (focus) this.tabs[next]?.focus({ preventScroll: true });
      const panel = this.panels[next];
      if (this.status) this.status.textContent = panel.getAttribute('aria-label') || '';
      const { transition, transitionDuration } = this.config;
      if (transition === 'none') return;
      const starts = {
        fade: { opacity: 0 },
        slide: { opacity: 0, transform: 'translate3d(25%,0,0)' },
        scale: { opacity: 0, transform: 'scale(.76)' },
        rotate: { opacity: 0, transform: 'perspective(1400px) rotateY(-58deg) translate3d(25%,5%,-90px) scale(.68)' }
      };
      this.animate(panel.querySelector('.sps-image-button'), [starts[transition], { opacity: 1, transform: 'none' }], transitionDuration);
      this.animate(panel.querySelector('.sps-copy'), [{ opacity: 0, transform: 'translateY(12px)' }, { opacity: 1, transform: 'none' }], transitionDuration * .75);
    }

    async flip(from, to, source, duration) {
      if (!from || !to || !source || from.width <= 0 || to.width <= 0 || !this.motionAllowed || duration <= 0) return;
      const ghost = document.createElement('img');
      ghost.src = source.currentSrc || source.src;
      ghost.alt = '';
      ghost.setAttribute('aria-hidden', 'true');
      ghost.className = 'sps-flip';
      Object.assign(ghost.style, { left: `${to.left}px`, top: `${to.top}px`, width: `${to.width}px`, height: `${to.height}px` });
      this.dialog.appendChild(ghost);
      const dx = from.left + from.width / 2 - to.left - to.width / 2;
      const dy = from.top + from.height / 2 - to.top - to.height / 2;
      await this.animate(ghost, [
        { transform: `translate(${dx}px,${dy}px) scale(${from.width / to.width},${from.height / to.height})`, opacity: 1 },
        { transform: 'translate(0,0) scale(1)', opacity: 1 }
      ], duration);
      ghost.remove();
    }

    openModal() {
      if (!this.dialog || this.dialog.hasAttribute('open') || this.destroyed) return;
      const panel = this.panels[this.selected];
      const template = panel?.querySelector('[data-sps-modal-template]');
      if (!template) return;
      this.cancelAnimations();
      this.sourceImage = panel.querySelector('.sps-product-image');
      const sourceRect = this.sourceImage?.getBoundingClientRect();
      this.previousFocus = document.activeElement;
      this.dialog.replaceChildren(template.content.cloneNode(true));
      const heading = this.dialog.querySelector('[data-sps-modal-title]');
      if (heading) heading.id = this.dialog.dataset.titleId;
      this.closing = false;
      const version = ++this.modalVersion;
      this.root.dataset.spsModalOpen = 'true';
      if (typeof this.dialog.showModal === 'function') this.dialog.showModal();
      else { this.dialog.setAttribute('open', ''); this.dialog.classList.add('sps-modal-fallback'); }
      lockScroll(this.dialog);
      this.dialog.querySelector('[data-sps-close]')?.focus({ preventScroll: true });
      const shell = this.dialog.querySelector('.sps-modal-shell');
      const target = this.dialog.querySelector('.sps-modal-image');
      const animation = this.config.modalAnimation;
      const duration = this.config.modalDuration;
      if (!this.motionAllowed || animation === 'none') return;
      const frames = {
        fade: [{ opacity: 0 }, { opacity: 1 }],
        slide: [{ opacity: 0, transform: 'translateY(40px)' }, { opacity: 1, transform: 'none' }],
        scale: [{ opacity: 0, transform: 'scale(.92)' }, { opacity: 1, transform: 'none' }],
        flip: [{ opacity: 0 }, { opacity: 1 }]
      };
      this.animate(shell, frames[animation], duration * .7);
      if (animation === 'flip' && target && sourceRect) {
        target.style.opacity = '0';
        this.modalFrame = requestAnimationFrame(async () => {
          if (version !== this.modalVersion || this.destroyed) return;
          await this.flip(sourceRect, target.getBoundingClientRect(), this.sourceImage, duration);
          if (version === this.modalVersion && !this.destroyed) target.style.opacity = '';
        });
      }
    }

    async closeModal(immediate = false, restoreFocus = true) {
      if (!this.dialog?.hasAttribute('open') || (this.closing && !immediate)) return;
      this.closing = true;
      const version = ++this.modalVersion;
      cancelAnimationFrame(this.modalFrame);
      this.cancelAnimations();
      const target = this.dialog.querySelector('.sps-modal-image');
      const shell = this.dialog.querySelector('.sps-modal-shell');
      const animation = this.config.modalAnimation;
      const duration = immediate ? 0 : this.config.modalDuration * .65;
      const jobs = [];
      if (this.motionAllowed && duration > 0 && animation !== 'none') {
        if (animation === 'flip' && target && this.sourceImage?.isConnected) {
          const from = target.getBoundingClientRect(), to = this.sourceImage.getBoundingClientRect();
          target.style.opacity = '0';
          jobs.push(this.flip(from, to, target, duration));
        }
        jobs.push(this.animate(shell, [{ opacity: 1, transform: 'none' }, { opacity: 0, transform: animation === 'slide' ? 'translateY(30px)' : animation === 'scale' ? 'scale(.96)' : 'none' }], duration));
      }
      await Promise.all(jobs);
      if (version !== this.modalVersion) return;
      if (typeof this.dialog.close === 'function') this.dialog.close();
      else this.dialog.removeAttribute('open');
      this.dialog.replaceChildren();
      this.root.dataset.spsModalOpen = 'false';
      unlockScroll(this.dialog);
      this.closing = false;
      if (restoreFocus && this.previousFocus?.isConnected) this.previousFocus.focus({ preventScroll: true });
    }

    destroy() {
      if (this.destroyed) return;
      this.destroyed = true;
      ++this.modalVersion;
      cancelAnimationFrame(this.frame);
      cancelAnimationFrame(this.modalFrame);
      this.cancelAnimations();
      if (this.dialog?.hasAttribute('open')) {
        if (typeof this.dialog.close === 'function') this.dialog.close();
        else this.dialog.removeAttribute('open');
        unlockScroll(this.dialog);
        if (this.previousFocus?.isConnected) this.previousFocus.focus({ preventScroll: true });
      }
      this.visibility?.disconnect();
      this.listeners.forEach(unlisten => unlisten());
      this.listeners.length = 0;
      this.root.classList.remove('sps-motion-float', 'sps-motion-ring');
      this.root.querySelectorAll('.sps-stage').forEach(stage => this.resetTilt(stage));
      instances.delete(this.root);
      activeInstances.delete(this);
    }
  }

  function initialize(scope) {
    if (!(scope instanceof Element || scope instanceof Document)) return;
    const roots = scope instanceof Element && scope.matches('.sps-root') ? [scope] : Array.from(scope.querySelectorAll('.sps-root'));
    roots.forEach(root => { if (!instances.has(root)) instances.set(root, new ProductShowcase(root)); });
  }

  function registerElementor() {
    if (elementorRegistered || !window.elementorFrontend?.hooks) return;
    elementorRegistered = true;
    const Base = window.elementorModules?.frontend?.handlers?.Base;
    if (Base) {
      class ElementorProductHandler extends Base {
        onInit() {
          super.onInit();
          initialize(this.$element[0]);
        }
        onElementChange(propertyName) {
          if (!motionKeys.has(propertyName)) return;
          initialize(this.$element[0]);
          const root = this.$element[0].querySelector('.sps-root');
          instances.get(root)?.update({ [propertyName]: this.getElementSettings(propertyName) });
        }
        onDestroy() {
          const root = this.$element[0].querySelector('.sps-root');
          instances.get(root)?.destroy();
          super.onDestroy();
        }
      }
      window.elementorFrontend.hooks.addAction('frontend/element_ready/saponine-product-showcase.default', $element => {
        window.elementorFrontend.elementsHandler.addHandler(ElementorProductHandler, { $element });
      });
    } else {
      window.elementorFrontend.hooks.addAction('frontend/element_ready/saponine-product-showcase.default', $element => initialize($element[0]));
    }
  }

  function boot() {
    initialize(document);
    registerElementor();
    if (!observer) {
      observer = new MutationObserver(records => {
        records.forEach(record => record.addedNodes.forEach(node => { if (node instanceof Element) initialize(node); }));
        activeInstances.forEach(instance => { if (!instance.root.isConnected) instance.destroy(); });
      });
      observer.observe(document.documentElement, { childList: true, subtree: true });
    }
  }

  window.addEventListener('elementor/frontend/init', registerElementor);
  if (window.jQuery) window.jQuery(window).on('elementor/frontend/init.saponineProduct', registerElementor);
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
  else boot();
  window.addEventListener('pageshow', boot);
})();
