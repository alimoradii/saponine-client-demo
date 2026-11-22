/* Keep the first slide below the actual editable fixed header, including its
 * wrapped mobile rows. This is independent of the section-scroll preference. */
(() => {
  'use strict';
  const start = () => {
    if (document.body.classList.contains('elementor-editor-active') || new URLSearchParams(location.search).has('elementor-preview')) return;
    const headers = [...document.querySelectorAll('.sn-header-bar')];
    if (!headers.length) return;
    const root = document.documentElement;
    const toolbar = document.getElementById('wpadminbar');
    if (headers.some(header => header.classList.contains('sn-header-compact'))) document.body.classList.add('sn-has-compact-header');
    let frame = 0;
    let previous = '';
    const measure = () => {
      frame = 0;
      let bottom = 0;
      const toolbarBottom = toolbar ? Math.max(0, toolbar.getBoundingClientRect().bottom) : 0;
      for (const header of headers) {
        if (header.classList.contains('sn-header-compact')) header.style.setProperty('--sn-toolbar-clearance', `${Math.ceil(toolbarBottom)}px`);
        const rect = header.getBoundingClientRect();
        const css = getComputedStyle(header);
        if (rect.height > 0 && css.display !== 'none' && ['fixed', 'sticky'].includes(css.position)) bottom = Math.max(bottom, rect.bottom);
      }
      const value = `${Math.max(0, Math.ceil(bottom + 12))}px`;
      if (value !== previous) { root.style.setProperty('--sn-header-clearance', value); previous = value; }
    };
    const schedule = () => { if (!frame) frame = requestAnimationFrame(measure); };
    if ('ResizeObserver' in window) {
      const observer = new ResizeObserver(schedule);
      headers.forEach(header => observer.observe(header));
    }
    window.addEventListener('resize', schedule, {passive:true});
    if (toolbar) window.addEventListener('scroll', schedule, {passive:true});
    window.addEventListener('orientationchange', schedule, {passive:true});
    window.addEventListener('load', schedule, {once:true});
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(schedule).catch(() => {});
    measure();
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, {once:true});
  else start();
})();
