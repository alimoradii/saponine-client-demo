/* Narrow-screen controls for the existing Elementor brand and About sections. */
(() => {
  'use strict';

  const mobile = matchMedia('(max-width: 767px)');
  const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');

  function button(className, label) {
    const node = document.createElement('button');
    node.type = 'button';
    node.className = className;
    node.setAttribute('aria-label', label);
    return node;
  }

  function brandCarousel(grid) {
    const cards = grid?.querySelectorAll('.sn-brand-card');
    if (!cards || cards.length < 2) return;
    const controls = document.createElement('div');
    controls.className = 'sn-story-brand-controls';
    const next = button('sn-story-brands-next', 'برند بعدی');
    const previous = button('sn-story-brands-prev', 'برند قبلی');
    controls.append(next, previous);
    grid.after(controls);
    function update() {
      const distance = Math.abs(grid.scrollLeft);
      const limit = grid.scrollWidth - grid.clientWidth;
      previous.disabled = distance <= 2;
      next.disabled = distance >= limit - 2;
    }
    function move(step) {
      const spacing = Math.abs(cards[1].offsetLeft - cards[0].offsetLeft);
      const sign = getComputedStyle(grid).direction === 'rtl' ? -1 : 1;
      grid.scrollBy({ left: step * sign * spacing, behavior: reducedMotion.matches ? 'instant' : 'smooth' });
    }
    next.addEventListener('click', () => move(1));
    previous.addEventListener('click', () => move(-1));
    grid.addEventListener('scroll', update, { passive: true });
    const observer = new ResizeObserver(update);
    observer.observe(grid);
    update();
    return () => {
      controls.remove();
      observer.disconnect();
      grid.removeEventListener('scroll', update);
    };
  }

  function aboutGallery(media) {
    const group = media?.querySelector('.sn-gallery-group');
    if (!group) return;
    const tiles = Array.from(group.children);
    const photos = new Set();
    const slides = tiles.filter(tile => {
      const image = tile.matches('img') ? tile : tile.querySelector('img');
      if (!image) return false;
      const key = image.srcset || image.src;
      if (photos.has(key)) return false;
      photos.add(key);
      return true;
    });
    if (slides.length < 2) return;
    const toggle = button('sn-story-gallery-toggle', 'پخش تصاویر محصولات');
    toggle.setAttribute('aria-pressed', 'false');
    toggle.innerHTML = '<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24"><path class="sn-story-play-icon" d="M7 4.8c0-1.1 1.2-1.8 2.1-1.2l12 7.2c.9.5.9 1.9 0 2.4l-12 7.2c-.9.6-2.1-.1-2.1-1.2Z"/><path class="sn-story-pause-icon" d="M5 4h5v16H5zm9 0h5v16h-5z"/></svg>';
    const originalProducts = new Map();
    document.querySelectorAll('#sec-4 .sps-panel').forEach(panel => {
      const photo = panel.querySelector('template[data-sps-original-photo]')?.content.firstElementChild || panel.querySelector('.sps-product-image');
      if (photo) originalProducts.set(photo.srcset || photo.src, photo.alt);
    });
    const originalAttributes = [];
    slides.forEach(tile => {
      if (!tile.matches('img')) return;
      originalAttributes.push([tile, tile.alt, tile.sizes]);
      tile.alt = originalProducts.get(tile.srcset || tile.src) || '';
      tile.sizes = '90vw';
    });
    let index = 0;
    let timer;
    function advance() {
      slides[index].classList.remove('sn-story-current');
      index = (index + 1) % slides.length;
      slides[index].classList.add('sn-story-current');
    }
    function pause() {
      clearInterval(timer);
      timer = undefined;
      toggle.setAttribute('aria-pressed', 'false');
      toggle.setAttribute('aria-label', 'پخش تصاویر محصولات');
    }
    toggle.addEventListener('click', () => {
      if (timer !== undefined) {
        pause();
        return;
      }
      advance();
      toggle.setAttribute('aria-pressed', 'true');
      toggle.setAttribute('aria-label', 'توقف پخش تصاویر محصولات');
      timer = setInterval(advance, 3500);
    });
    function onVisibility() { if (document.hidden) pause(); }
    document.addEventListener('visibilitychange', onVisibility);
    const observer = new IntersectionObserver(entries => {
      if (!entries[0].isIntersecting) pause();
    });
    observer.observe(media);
    slides[0].classList.add('sn-story-current');
    media.classList.add('sn-story-gallery');
    media.append(toggle);
    return () => {
      pause();
      observer.disconnect();
      document.removeEventListener('visibilitychange', onVisibility);
      slides[index].classList.remove('sn-story-current');
      originalAttributes.forEach(([image, alt, sizes]) => { image.alt = alt; image.sizes = sizes; });
      media.classList.remove('sn-story-gallery');
      toggle.remove();
    };
  }

  function aboutSummary(summary) {
    if (!summary) return;
    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'sn-story-read-more';
    const oldId = summary.id;
    if (!oldId) summary.id = 'sn-story-about-description';
    toggle.setAttribute('aria-controls', summary.id);
    toggle.setAttribute('aria-expanded', 'false');
    toggle.textContent = 'مشاهده بیشتر';
    summary.after(toggle);
    let expanded = false;
    function update() {
      if (expanded) return;
      const fontSize = parseFloat(getComputedStyle(summary).fontSize);
      const overflows = summary.scrollHeight > fontSize * 6 + 2;
      summary.classList.toggle('sn-story-summary--closed', overflows);
      toggle.hidden = !overflows;
    }
    toggle.addEventListener('click', () => {
      expanded = !expanded;
      summary.classList.toggle('sn-story-summary--closed', !expanded);
      toggle.setAttribute('aria-expanded', String(expanded));
      toggle.textContent = expanded ? 'مشاهده کمتر' : 'مشاهده بیشتر';
      if (!expanded) update();
    });
    const observer = new ResizeObserver(update);
    observer.observe(summary);
    document.fonts?.addEventListener('loadingdone', update);
    update();
    return () => {
      observer.disconnect();
      document.fonts?.removeEventListener('loadingdone', update);
      toggle.remove();
      if (!oldId) summary.removeAttribute('id');
      summary.classList.remove('sn-story-summary--closed');
    };
  }

  function boot() {
    if (!document.querySelector('#sec-1, #sec-2, #sec-3') ||
        document.body.classList.contains('elementor-editor-active') ||
        window.elementorFrontend?.isEditMode?.() ||
        new URLSearchParams(location.search).has('elementor-preview')) return;
    let cleanups = [];
    function refresh() {
      cleanups.forEach(clean => clean());
      cleanups = [];
      if (!mobile.matches) return;
      cleanups = [
        brandCarousel(document.querySelector('#sec-2 .sn-brand-grid')),
        aboutGallery(document.querySelector('#sec-3 .sn-about-media')),
        aboutSummary(document.querySelector('#sec-3 .sn-about-copy .elementor-widget-text-editor'))
      ].filter(Boolean);
    }
    mobile.addEventListener('change', refresh);
    refresh();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
  else boot();
})();
