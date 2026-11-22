(() => {
  'use strict';
  const norm = value => String(value || '').trim().toLowerCase().replace(/ي/g, 'ی').replace(/ك/g, 'ک').replace(/[\s\u200c\u0640]+/g, ' ');
  const matches = (text, query) => norm(text).includes(norm(query));

  function directory(root) {
    const search = root.querySelector('[data-sns-search="pharmacy"]');
    const buttons = [...root.querySelectorAll('[data-sns-province]')];
    const paths = [...root.querySelectorAll('[data-sns-region]')];
    const places = [...root.querySelectorAll('[data-sns-place]')];
    const clear = root.querySelector('[data-sns-clear]');
    const mapLabel = root.querySelector('[data-sns-map-label]');
    const map = mapLabel.parentElement;
    const svg = map.querySelector('svg');
    const byName = new Map(paths.map(path => [path.dataset.snsRegion, {path}]));
    buttons.forEach(button => { byName.get(button.dataset.snsProvince).button = button; });
    let selected = '';
    let hovered = '', focused = '', searchMatch = '', highlighted = '';
    let cursor = null;

    function placeLabel() {
      if (!highlighted) return;
      const bounds = map.getBoundingClientRect();
      let x, y;
      if (cursor) {
        x = cursor.x + 8;
        y = cursor.y + 9;
      } else {
        const path = byName.get(highlighted).path;
        const local = svg.createSVGPoint();
        local.x = Number(path.dataset.labelX);
        local.y = Number(path.dataset.labelY);
        const matrix = path.getScreenCTM();
        if (!matrix) return;
        const point = local.matrixTransform(matrix);
        x = point.x - bounds.left - mapLabel.offsetWidth / 2;
        y = point.y - bounds.top - mapLabel.offsetHeight / 2;
      }
      mapLabel.style.left = Math.max(0, Math.min(x, bounds.width - mapLabel.offsetWidth)) + 'px';
      mapLabel.style.top = Math.max(0, Math.min(y, bounds.height - mapLabel.offsetHeight)) + 'px';
    }

    function highlight() {
      const name = hovered || focused || selected || searchMatch;
      if (highlighted !== name) {
        for (const [key, pair] of byName) {
          pair.path.classList.toggle('is-lit', key === name);
          pair.button.classList.toggle('is-lit', key === name);
        }
        highlighted = name;
        mapLabel.textContent = name;
        mapLabel.hidden = !name;
      }
      placeLabel();
    }

    function followPointer(event) {
      const name = event.target.dataset.snsRegion;
      if (!name || event.pointerType === 'touch') return;
      const box = map.getBoundingClientRect();
      hovered = name;
      cursor = {x: event.clientX - box.left, y: event.clientY - box.top};
      highlight();
    }

    function repaint() {
      const query = search.value.trim();
      const visible = places.filter(card => {
        const show = (!selected || selected === card.dataset.snsPlace) && matches(card.textContent, query);
        card.hidden = !show;
        return show;
      });
      buttons.forEach(button => {
        const name = button.dataset.snsProvince;
        button.hidden = !!query && !matches(name, query) && !places.some(card => card.dataset.snsPlace === name && matches(card.textContent, query));
        button.setAttribute('aria-pressed', String(selected === name));
      });
      paths.forEach(path => path.setAttribute('aria-pressed', String(selected === path.dataset.snsRegion)));
      const candidates = query ? buttons.filter(button => !button.hidden) : [];
      searchMatch = candidates.length === 1 ? candidates[0].dataset.snsProvince : '';
      clear.hidden = !selected && !query;
      root.querySelector('[data-sns-empty]').hidden = visible.length > 0;
      root.querySelector('[data-sns-count]').textContent = (selected ? selected + ' · ' : '') + visible.length.toLocaleString('fa-IR') + ' داروخانه';
      highlight();
    }
    function choose(name) {
      selected = selected === name ? '' : name;
      search.value = '';
      repaint();
    }
    for (const [name, {path, button}] of byName) {
      for (const control of [path, button]) {
        control.addEventListener('click', () => choose(name));
        control.addEventListener('pointerenter', event => {
          if (event.pointerType === 'touch') return;
          if (control === path) {
            followPointer(event);
          } else {
            hovered = name; cursor = null; highlight();
          }
        });
        control.addEventListener('pointerleave', () => { hovered = ''; cursor = null; highlight(); });
        control.addEventListener('focus', () => {
          if (control.matches(':focus-visible')) {
            focused = name; hovered = ''; cursor = null; highlight();
          }
        });
        control.addEventListener('blur', () => { focused = ''; highlight(); });
      }
      path.addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          if (!event.repeat) choose(name);
        }
      });
    }
    svg.addEventListener('pointermove', followPointer);
    if ('ResizeObserver' in window) new ResizeObserver(placeLabel).observe(map);
    search.addEventListener('input', () => { hovered = ''; cursor = null; selected = ''; repaint(); });
    clear.addEventListener('click', () => { selected = ''; search.value = ''; repaint(); });
    repaint();
  }

  function faq(root) {
    const search = root.querySelector('[data-sns-search="faq"]');
    const topic = root.querySelector('[data-sns-topic-filter]');
    const questions = [...root.querySelectorAll('.sns-question')];
    function filter() {
      let visible = 0;
      questions.forEach(question => {
        question.hidden = (topic.value && topic.value !== question.dataset.snsTopic) || !matches(question.textContent, search.value);
        if (!question.hidden) visible++;
      });
      root.querySelector('[data-sns-empty]').hidden = !!visible;
    }
    search.addEventListener('input', filter);
    topic.addEventListener('change', filter);
  }

  function careers(root) {
    const form = root.querySelector('[data-sns-apply]');
    const picker = form.elements.cv;
    picker.addEventListener('change', () => {
      const file = picker.files[0];
      const invalid = file && (file.size > 2097152 || !/\.pdf$/i.test(file.name));
      picker.setCustomValidity(invalid ? 'یک فایل PDF تا دو مگابایت انتخاب کنید.' : '');
      if (invalid) picker.reportValidity();
    });
    if (location.protocol === 'file:' || document.querySelector('[data-sns-offline]')) {
      const note = root.querySelector('[data-sns-form-note]');
      note.textContent = 'در این پیش‌نمایش، اطلاعات واردشده ارسال یا ذخیره نمی‌شود.';
      form.addEventListener('submit', event => { event.preventDefault(); note.scrollIntoView({block:'center'}); });
    }
    const row = root.querySelector('[data-sns-process]');
    const controls = root.querySelector('.sns-process-nav');
    const previous = controls.querySelector('[data-sns-step="-1"]');
    const next = controls.querySelector('[data-sns-step="1"]');
    function state() {
      const items = [...row.children];
      controls.hidden = row.scrollWidth <= row.clientWidth + 1;
      if (!items.length) return;
      const range = row.getBoundingClientRect();
      previous.disabled = items[0].getBoundingClientRect().right <= range.right + 2;
      next.disabled = items[items.length - 1].getBoundingClientRect().left >= range.left - 2;
    }
    controls.addEventListener('click', event => {
      const button = event.target.closest('[data-sns-step]');
      if (!button || !row.firstElementChild) return;
      const step = row.firstElementChild.getBoundingClientRect().width + parseFloat(getComputedStyle(row).gap);
      row.scrollBy({left:-Number(button.dataset.snsStep) * step, behavior:matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth'});
    });
    row.addEventListener('scroll', state, {passive:true});
    if ('ResizeObserver' in window) new ResizeObserver(state).observe(row);
    state();
  }
  function boot() {
    document.querySelectorAll('[data-sns-page]').forEach(root => {
      if (root.dataset.snsReady) return;
      root.dataset.snsReady = '1';
      if (root.dataset.snsPage === 'pharmacies') directory(root);
      if (root.dataset.snsPage === 'faq') faq(root);
      if (root.dataset.snsPage === 'careers') careers(root);
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, {once:true});
  else boot();
})();
