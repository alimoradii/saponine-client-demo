(() => {
  'use strict';

  function buildFooter() {
    const footer = document.querySelector('#sec-6.sn-footer');
    const columns = footer?.querySelector('.sn-footer-columns');
    const template = document.getElementById('sn-mobile-footer-template');
    if (!columns || !template || footer.classList.contains('sn-mobile-footer-ready')) return;

    const content = template.content.cloneNode(true);
    const quick = content.querySelector('[data-sn-footer-links]');
    const nav = quick.querySelector('nav');
    for (const column of columns.querySelectorAll('.sn-footer-column:not(.sn-footer-intro)')) {
      const links = column.querySelectorAll('a[href]');
      if (!links.length) continue;
      const group = document.createElement('div');
      const heading = column.querySelector('.elementor-heading-title');
      if (heading && !heading.querySelector('a')) {
        const label = document.createElement('strong');
        label.textContent = heading.textContent.trim();
        group.append(label);
      }
      for (const link of links) {
        const clone = link.cloneNode(true);
        clone.removeAttribute('id');
        clone.querySelectorAll('[id]').forEach(el => el.removeAttribute('id'));
        group.append(clone);
      }
      nav.append(group);
      column.classList.add('sn-footer-folded-column');
    }
    if (!nav.childElementCount) quick.remove();
    columns.after(content);
    footer.classList.add('sn-mobile-footer-ready');
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', buildFooter, { once: true });
  else buildFooter();
})();
