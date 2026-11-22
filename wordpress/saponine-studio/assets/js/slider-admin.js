(() => {
  'use strict';
  const form = document.getElementById('sn-slider-form');
  if (!form) return;
  const list = form.querySelector('[data-sn-slides]');
  const slideRows = () => Array.from(list.children);
  const fieldInputs = field => Array.from(field.querySelectorAll('input,textarea,select')).filter(el => !el.hasAttribute('data-sn-color'));
  const serial = new Intl.NumberFormat('fa');
  let added = 0;
  let orderChanged = false;

  function fieldValue(field) {
    const inputs = fieldInputs(field);
    const read = el => el.type === 'checkbox' ? (el.checked ? el.value : '') : el.value;
    if (!inputs.some(el => el.dataset.snPart)) return read(inputs[0]);
    return Object.fromEntries(inputs.map(el => [el.dataset.snPart, read(el)]));
  }

  function fillSummaries() {
    const rows = slideRows();
    rows.forEach((row, i) => {
      row.querySelector('[data-sn-count]').textContent = 'اسلاید ' + serial.format(i + 1);
      row.querySelector('[data-sn-title]').textContent = ['title', 'highlight'].map(key => {
        const field = row.querySelector('[data-sn-field="' + key + '"]');
        return field ? fieldValue(field).trim() : '';
      }).filter(Boolean).join(' ');
      row.querySelector('[data-sn-move="up"]').disabled = i === 0;
      row.querySelector('[data-sn-move="down"]').disabled = i === rows.length - 1;
    });
  }

  function preview(field, url) {
    const image = field.querySelector('img');
    image.hidden = !url;
    if (url) image.src = url;
    else image.removeAttribute('src');
  }

  function changed(event) {
    const control = event.target;
    const field = control.closest('[data-sn-field]');
    if (!field) return;
    field.dataset.snDirty = '1';
    if (control.hasAttribute('data-sn-color')) fieldInputs(field)[0].value = control.value;
    if (control.dataset.snPart === 'url' && field.dataset.snKind === 'media') {
      field.querySelector('[data-sn-part="id"]').value = '0';
      preview(field, control.value);
    }
    if (field.dataset.snField === 'title' || field.dataset.snField === 'highlight') fillSummaries();
  }
  form.addEventListener('input', changed);
  form.addEventListener('change', changed);
  form.addEventListener('click', event => {
    const button = event.target.closest('button');
    if (!button || button.type !== 'button') return;
    if (button.hasAttribute('data-sn-add')) {
      const row = form.querySelector('[data-sn-template]').content.firstElementChild.cloneNode(true);
      added++;
      row.querySelectorAll('label[for]').forEach(label => {
        const input = row.querySelector('#' + label.htmlFor);
        if (input) { input.id += '-' + added; label.htmlFor = input.id; }
      });
      list.append(row);
      row.open = true;
      orderChanged = true;
      fillSummaries();
      row.querySelector('[data-sn-field="title"] textarea').focus();
      return;
    }
    const row = button.closest('[data-sn-slide]');
    if (button.hasAttribute('data-sn-remove')) {
      const destination = row.nextElementSibling || row.previousElementSibling;
      row.remove();
      orderChanged = true;
      fillSummaries();
      (destination?.querySelector('summary') || form.querySelector('[data-sn-add]')).focus();
      return;
    }
    if (button.dataset.snMove) {
      if (button.dataset.snMove === 'up' && row.previousElementSibling) list.insertBefore(row, row.previousElementSibling);
      if (button.dataset.snMove === 'down' && row.nextElementSibling) list.insertBefore(row.nextElementSibling, row);
      orderChanged = true;
      fillSummaries();
      row.querySelector('summary').focus();
      return;
    }
    const field = button.closest('[data-sn-field]');
    if (!field) return;
    function imageValue(id, url) {
      field.querySelector('[data-sn-part="id"]').value = id;
      field.querySelector('[data-sn-part="url"]').value = url;
      preview(field, url);
      field.dataset.snDirty = '1';
    }
    if (button.hasAttribute('data-sn-clear')) imageValue(0, '');
    if (button.hasAttribute('data-sn-pick') && window.wp?.media) {
      const picker = wp.media({title:'انتخاب تصویر اسلاید', button:{text:'استفاده از تصویر'}, library:{type:'image'}, multiple:false});
      picker.on('select', () => {
        const image = picker.state().get('selection').first().toJSON();
        imageValue(image.id, image.url);
      });
      picker.open();
    }
  });
  form.addEventListener('submit', event => {
    const invalidNumber = form.querySelector('[data-sn-dirty] input[type="number"]:invalid');
    if (invalidNumber) {
      event.preventDefault();
      const panel = invalidNumber.closest('details');
      if (panel) panel.open = true;
      invalidNumber.reportValidity();
      return;
    }
    const values = fields => Object.fromEntries(Array.from(fields, field => [field.dataset.snField, fieldValue(field)]));
    const payload = {settings:values(form.querySelectorAll('[data-sn-group] [data-sn-dirty]'))};
    if (orderChanged || list.querySelector('[data-sn-dirty]')) {
      payload.slides = slideRows().map(row => {
        const index = row.dataset.snIndex === '' ? null : Number(row.dataset.snIndex);
        return {index, set:values(row.querySelectorAll(index === null ? '[data-sn-field]' : '[data-sn-dirty]'))};
      });
    }
    form.elements.sn_payload.value = JSON.stringify(payload);
  });
  fillSummaries();
  document.addEventListener('click', event => {
    const button = event.target.closest('[data-sn-companion-media]');
    if (!button || !window.wp?.media) return;
    const field = button.closest('.sn-companion-field').querySelector('input');
    const picker = wp.media({title:'انتخاب تصویر دکمه', button:{text:'استفاده از تصویر'}, library:{type:'image'}, multiple:false});
    picker.on('select', () => { field.value = picker.state().get('selection').first().get('url'); });
    picker.open();
  });
})();
