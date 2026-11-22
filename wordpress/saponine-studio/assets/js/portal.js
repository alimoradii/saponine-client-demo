/* Enhance the existing native search and navigation without requiring a network call. */
(() => {
  const normalize = value => value.toLowerCase().replace(/ي/g,'ی').replace(/ك/g,'ک').replace(/[\s\u200c]+/g,' ').trim();
  function boot() {
    const dialog = document.getElementById('sn-finder');
    if (dialog && typeof dialog.showModal === 'function') {
      const field = dialog.querySelector('input[type="search"]');
      const list = dialog.querySelector('.sn-finder-results');
      const links = [...list.querySelectorAll('a[data-keywords]')];
      const suggestions = dialog.querySelector('.sn-finder-suggestions');
      let opener;
      function filter() {
        const terms = normalize(field.value).split(' ').filter(Boolean);
        list.hidden = !terms.length;
        suggestions.hidden = !!terms.length;
        let matches = 0;
        links.forEach(link => {
          const haystack = normalize(link.dataset.keywords);
          link.hidden = !terms.every(term => haystack.includes(term));
          if (!link.hidden) matches++;
        });
        list.querySelector('.sn-finder-status').textContent = matches ? 'نتایج جستجو' : 'نتیجه‌ای پیدا نشد.';
      }
      document.addEventListener('click', event => {
        const toggle = event.target.closest('.sp-search__toggle,[data-sn-search-open]');
        if (!toggle) return;
        event.preventDefault(); opener = toggle;
        field.value = ''; filter();
        dialog.showModal(); field.focus();
      });
      dialog.querySelector('.sn-finder-close').addEventListener('click', () => dialog.close());
      dialog.addEventListener('click', event => {
        if (event.target === dialog) {
          const r = dialog.getBoundingClientRect();
          if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) dialog.close();
        }
        const choice = event.target.closest('.sn-finder-suggestions button');
        if (choice) { field.value = choice.textContent.trim(); filter(); field.focus(); }
      });
      dialog.addEventListener('close', () => {
        const restore = opener?.getClientRects().length ? opener : opener?.closest('.sp-nav')?.querySelector('.sp-nav__toggle');
        restore?.focus({preventScroll:true});
      });
      field.addEventListener('input', filter);
      dialog.querySelector('form').addEventListener('submit', e => {
        e.preventDefault(); filter();
        if (!list.hidden) list.querySelector('a:not([hidden])')?.click();
      });
    }
    const branches = [...document.querySelectorAll('.sn-nav-branch')];
    const set = (branch, open) => {
      branch.querySelector('.sn-branch-toggle').setAttribute('aria-expanded', String(open));
      branch.querySelector('.sn-mega').hidden = !open;
    };
    branches.forEach(branch => {
      const toggle = branch.querySelector('.sn-branch-toggle');
      toggle.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') !== 'true';
        branches.forEach(b => set(b, b === branch && open));
      });
      branch.addEventListener('pointerenter', e => { if (e.pointerType === 'mouse' && matchMedia('(min-width:1025px)').matches) branches.forEach(b => set(b,b===branch)); });
      branch.addEventListener('pointerleave', () => { if (!branch.contains(document.activeElement) && matchMedia('(min-width:1025px)').matches) set(branch,false); });
      branch.addEventListener('focusout', e => { if (!branch.contains(e.relatedTarget)) set(branch,false); });
      branch.addEventListener('keydown', e => { if(e.key==='Escape' && !branch.querySelector('.sn-mega').hidden) { e.stopPropagation(); set(branch,false); toggle.focus(); } });
    });
    document.addEventListener('pointerdown', e => branches.forEach(b => { if (!b.contains(e.target)) set(b,false); }));
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded',boot,{once:true}); else boot();
})();
