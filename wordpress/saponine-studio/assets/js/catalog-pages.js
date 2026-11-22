(() => {
  'use strict';

  function archive(root) {
    const filters = [...root.querySelectorAll('[data-spc-filter]')];
    const cards = [...root.querySelectorAll('.spc-card')];
    const empty = root.querySelector('[data-spc-empty]');
    const resultCount = root.querySelector('[data-spc-count]');
    const reset = root.querySelector('[data-spc-reset]');
    const queryName = select => 'catalog_' + select.dataset.spcFilter;
    const formatCount = new Intl.NumberFormat(document.documentElement.lang || 'fa');

    function showResults(announce) {
      let total = 0;
      for (const card of cards) {
        card.hidden = !filters.every(select => !select.value || select.value === card.dataset[select.dataset.spcFilter]);
        if (!card.hidden) total++;
      }
      empty.hidden = total > 0;
      if (announce) resultCount.textContent = resultCount.dataset.label + ' ' + formatCount.format(total);
    }

    function readQuery() {
      const url = new URL(location.href);
      for (const select of filters) {
        const value = url.searchParams.get(queryName(select)) || '';
        select.value = [...select.options].some(option => option.value === value) ? value : '';
      }
      showResults(false);
    }

    function saveSelection() {
      showResults(true);
      const url = new URL(location.href);
      for (const select of filters) {
        if (select.value) url.searchParams.set(queryName(select), select.value);
        else url.searchParams.delete(queryName(select));
      }
      // Local file previews can filter even when the browser forbids URL changes.
      try { history.replaceState(history.state, '', url); } catch (_) {}
    }

    filters.forEach(select => select.addEventListener('change', saveSelection));
    if (reset) reset.addEventListener('click', () => {
      filters.forEach(select => { select.value = ''; });
      saveSelection();
      filters[0].focus();
    });
    window.addEventListener('popstate', readQuery);
    readQuery();
    root.querySelector('[data-spc-filters]').hidden = false;
  }

  function tabs(root) {
    const list = root.querySelector('.spc-tablist');
    const links = [...list.querySelectorAll('a')];
    const panels = links.map(link => document.getElementById(link.hash.slice(1)));
    if (!panels.length || panels.some(panel => !panel)) return;
    list.setAttribute('role', 'tablist');
    function select(index) {
      links.forEach((link, i) => {
        const current = i === index;
        link.setAttribute('aria-selected', String(current));
        link.tabIndex = current ? 0 : -1;
        panels[i].hidden = !current;
      });
    }
    function readHash() {
      const index = links.findIndex(link => link.hash === location.hash);
      select(index < 0 ? 0 : index);
    }
    links.forEach((link, i) => {
      link.setAttribute('role', 'tab');
      link.setAttribute('aria-controls', panels[i].id);
      panels[i].setAttribute('role', 'tabpanel');
      panels[i].setAttribute('aria-labelledby', link.id);
      panels[i].tabIndex = 0;
      link.addEventListener('click', event => { event.preventDefault(); select(i); });
      link.addEventListener('keydown', event => {
        const rtl = getComputedStyle(list).direction === 'rtl';
        const offsets = {ArrowRight: rtl ? -1 : 1, ArrowLeft: rtl ? 1 : -1};
        let next = i;
        if (event.key in offsets) next = (i + offsets[event.key] + links.length) % links.length;
        else if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = links.length - 1;
        else if (event.key !== ' ') return;
        event.preventDefault();
        select(next);
        links[next].focus();
      });
    });
    readHash();
    window.addEventListener('hashchange', readHash);
  }

  function init() {
    document.querySelectorAll('[data-spc-archive]').forEach(archive);
    document.querySelectorAll('[data-spc-tabs]').forEach(tabs);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, {once: true});
  else init();
})();
