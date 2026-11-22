(function () {
  'use strict';
  const root = document.getElementById('saponine-auto-translation');
  const config = window.SaponineAutoTranslate;
  if (!root || !config) return;
  const form = root.querySelector('[data-sn-auto-form]');
  if (!form) return;
  const status = root.querySelector('[data-sn-auto-status]');
  const progress = root.querySelector('[data-sn-auto-progress]');
  const links = root.querySelector('[data-sn-auto-links]');
  let current = null;
  let running = false;
  let stopRequested = false;
  async function request(operation, values) {
    const body = new URLSearchParams(Object.assign({action: 'saponine_auto_translate', nonce: config.nonce, operation}, values || {}));
    const response = await fetch(config.url, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}, body});
    const result = await response.json();
    if (!response.ok || !result.success) throw new Error(result.data && result.data.message || 'درخواست کامل نشد؛ صفحه را تازه کنید و ادامه را بزنید.');
    return result.data;
  }
  function render(job) {
    current = job;
    progress.value = job.progress || 0;
    status.textContent = job.message || '';
    if (job.total) status.textContent += ' ' + job.translated + ' / ' + job.total;
    if (job.preserved_fields) status.textContent += ' — ' + job.preserved_fields + ' متن ویرایش‌شدهٔ دستی حفظ شد.';
    links.replaceChildren();
    const names = {home: 'صفحهٔ اصلی', header: 'سربرگ', footer: 'پابرگ'};
    (job.documents || []).forEach(doc => {
      const li = document.createElement('li');
      const a = document.createElement('a');
      a.href = doc.url;
      a.textContent = 'ویرایش ' + (names[doc.role] || doc.role) + (doc.status === 'draft' ? ' — پیش‌نویس' : '');
      li.appendChild(a); links.appendChild(li);
    });
    (job.skipped || []).forEach(message => {
      const li = document.createElement('li'); li.textContent = message; links.appendChild(li);
    });
  }
  function busy(value) {
    running = value;
    form.querySelector('[type="submit"]').disabled = value;
    form.querySelector('[data-sn-auto-resume]').disabled = value;
    form.querySelectorAll('select').forEach(select => { select.disabled = value; });
  }
  async function work() {
    if (!current || !current.id || running) return;
    stopRequested = false; busy(true);
    try {
      while (!stopRequested && current && ['running', 'error'].includes(current.status)) {
        render(await request('step', {job_id: current.id}));
        if (current.status === 'running') await new Promise(resolve => setTimeout(resolve, 180));
      }
      if (stopRequested && current && current.id) render(await request('cancel', {job_id: current.id}));
    } catch (error) {
      status.textContent = error.message + ' پیشرفت قبلی ذخیره شده است.';
      try { const saved = await request('status'); current = saved; progress.value = saved.progress || 0; } catch (_) {}
    } finally { busy(false); }
  }
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (running) return;
    const data = new FormData(form);
    busy(true);
    try {
      render(await request('start', {source_language: data.get('source_language'), target_language: data.get('target_language')}));
      busy(false); await work();
    } catch (error) { status.textContent = error.message; busy(false); }
  });
  root.querySelector('[data-sn-auto-resume]').addEventListener('click', async () => {
    if (running) return;
    try { render(await request('status')); await work(); } catch (error) { status.textContent = error.message; }
  });
  root.querySelector('[data-sn-auto-cancel]').addEventListener('click', async () => {
    if (running) { stopRequested = true; status.textContent = 'پس از ذخیرهٔ مرحلهٔ جاری متوقف می‌شود…'; return; }
    if (!current || !current.id) return;
    try { render(await request('cancel', {job_id: current.id})); } catch (error) { status.textContent = error.message; }
  });
  request('status').then(render).catch(error => { status.textContent = error.message; });
})();
