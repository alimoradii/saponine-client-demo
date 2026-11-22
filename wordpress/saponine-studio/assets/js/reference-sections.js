/* Reuse the current Elementor photography for the gallery and pharmacy artwork. */
(() => {
  'use strict';

  function decorativePhoto(source) {
    const image = new Image();
    image.alt = '';
    image.loading = 'lazy';
    image.decoding = 'async';
    if (source.srcset) image.srcset = source.srcset;
    image.sizes = '(max-width: 767px) 42vw, 18vw';
    image.src = source.currentSrc || source.src;
    return image;
  }

  function addGallery(media, products) {
    const image = media?.querySelector('.sn-about-image img');
    if (!image || !products.length || media.querySelector('.sn-gallery-track')) return;
    const widget = image.closest('.sn-about-image');
    const host = widget.parentElement;
    const sources = [image, ...products];
    const track = document.createElement('div');
    track.className = 'sn-gallery-track';
    const first = document.createElement('div');
    first.className = 'sn-gallery-group';
    const repeat = document.createElement('div');
    repeat.className = first.className;
    repeat.setAttribute('aria-hidden', 'true');
    host.insertBefore(track, widget);
    first.append(widget);
    for (let index = 0; index < Math.max(8, sources.length); index++) {
      const source = sources[index % sources.length];
      if (index) first.append(decorativePhoto(source));
      repeat.append(decorativePhoto(source));
    }
    track.append(first, repeat);
    media.classList.add('sn-gallery-ready');
    if (widget.querySelector('a')) media.classList.add('sn-gallery-interactive');
  }

  function addPharmacyPhotos(card, products) {
    if (!card || products.length < 2 || card.querySelector('.sn-pharmacy-pictures')) return;
    const art = document.createElement('div');
    art.className = 'sn-pharmacy-pictures';
    art.setAttribute('aria-hidden', 'true');
    products.slice(0, 2).forEach(source => {
      const column = document.createElement('span');
      column.append(decorativePhoto(source));
      art.append(column);
    });
    card.append(art);
    card.classList.add('sn-has-pharmacy-pictures');
  }

  function boot() {
    if (document.body.classList.contains('elementor-editor-active') ||
        window.elementorFrontend?.isEditMode?.() ||
        new URLSearchParams(window.location.search).has('elementor-preview')) return;
    const products = Array.from(document.querySelectorAll('#sec-4 .sps-panel'), panel => {
      const photo = panel.querySelector('template[data-sps-original-photo]')?.content.firstElementChild;
      return photo ? document.importNode(photo, false) : panel.querySelector('.sps-product-image');
    }).filter(Boolean);
    const media = document.querySelector('#sec-3 .sn-about-media');
    const pharmacy = document.querySelector('#sec-5 .sn-pharmacy-card');
    addGallery(media, products);
    addPharmacyPhotos(pharmacy, products);

    if (!('IntersectionObserver' in window)) return;
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => entry.target.classList.toggle('sn-section-in-view', entry.isIntersecting));
    }, { threshold: 0.25 });
    document.querySelectorAll('#sec-1 .sn-bottle-scene, #sec-2 .sn-brand-grid, #sec-3 .sn-about-media')
      .forEach(node => observer.observe(node));
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
  else boot();
})();
