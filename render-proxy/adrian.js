(() => {
  'use strict';

  const LABELS = ['saponine balance','botanical serum','effervescent sachet','saponine radiance','محصولات ساپونین','محصول منتخب','جزئیات محصول','جزییات محصول'];
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const state = { section:null, stage:null, image:null, tabs:[], observer:null, sourceObserver:null, modal:null, focus:null, timer:0, raf:0 };
  const norm = value => String(value || '').replace(/[\u200c\u200f\ufeff]/g,' ').replace(/\s+/g,' ').trim().toLowerCase();
  const esc = value => String(value || '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
  const isVisible = el => {
    if (!(el instanceof Element)) return false;
    const s = getComputedStyle(el), r = el.getBoundingClientRect();
    return s.display !== 'none' && s.visibility !== 'hidden' && Number(s.opacity) !== 0 && r.width > 30 && r.height > 24;
  };

  function ring(className='adrian-ring') {
    const id = `ar-${Math.random().toString(36).slice(2,8)}`;
    const el = document.createElement('div');
    el.className = className;
    el.setAttribute('aria-hidden','true');
    el.innerHTML = `<svg viewBox="0 0 300 300" focusable="false"><defs><path id="${id}" d="M150,150 m-118,0 a118,118 0 1,1 236,0 a118,118 0 1,1 -236,0"/></defs><circle cx="150" cy="150" r="118"/><circle cx="150" cy="150" r="104" opacity=".42"/><text><textPath href="#${id}" startOffset="1%">SAPONINE • PHARMA • SCIENTIFIC WELLNESS • SAPONINE • PHARMA • SCIENTIFIC WELLNESS • </textPath></text></svg>`;
    return el;
  }
  const arrow = next => `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="${next?'M8 4l8 8-8 8':'M16 4l-8 8 8 8'}"/></svg>`;
  const zoom = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="M16 16l4 4M11 8v6M8 11h6"/></svg>';
  const closeIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>';

  function sectionScore(el) {
    const text = norm(el.textContent);
    if (!text) return -99;
    let score = LABELS.reduce((n,x) => n + (text.includes(x) ? 8 : 0), 0);
    if (/product|محصول|serum|sachet|balance/.test(text)) score += 4;
    score += Math.min(8, el.querySelectorAll('img').length * 2);
    score += Math.min(5, el.querySelectorAll('button,[role=tab]').length);
    const r = el.getBoundingClientRect();
    if (r.height > 300) score += 2;
    if (r.height > innerHeight * 2.4) score -= 9;
    return score;
  }

  function locateSection() {
    return [...document.querySelectorAll('main section,section,main>div')]
      .filter(isVisible)
      .map(el => ({el, score:sectionScore(el)}))
      .filter(x => x.score >= 14)
      .sort((a,b) => b.score-a.score)[0]?.el || null;
  }

  function imageScore(img, section) {
    if (!(img instanceof HTMLImageElement) || !isVisible(img)) return -99;
    const r = img.getBoundingClientRect(), sr = section.getBoundingClientRect();
    const d = norm(`${img.alt} ${img.currentSrc} ${img.src}`);
    let score = Math.sqrt(r.width*r.height)/42;
    if (/product|bottle|saponin|balance|serum|sachet|محصول|بطری|pack/.test(d)) score += 14;
    if (/logo|icon|avatar|arrow|chevron/.test(d)) score -= 22;
    if (r.width > sr.width*.8) score -= 8;
    if (r.width < 80 || r.height < 100) score -= 12;
    return score;
  }

  function findImage(section) {
    return [...section.querySelectorAll('img')]
      .map(img => ({img,score:imageScore(img,section)}))
      .sort((a,b) => b.score-a.score)[0]?.img || null;
  }

  function findStage(img, section) {
    const sr = section.getBoundingClientRect(), ir = img.getBoundingClientRect();
    let best = img.parentElement, p = img.parentElement;
    while (p && p !== section) {
      const r = p.getBoundingClientRect();
      if (r.width >= ir.width && r.height >= ir.height && r.width <= sr.width*.7 && r.height <= Math.max(sr.height*.85,760) && r.width > 180 && r.height > 180) best = p;
      if (r.width > sr.width*.74) break;
      p = p.parentElement;
    }
    return best || img.parentElement;
  }

  function findTabs(section) {
    return [...new Set([...section.querySelectorAll('button,[role=tab],a[href]')].filter(el => {
      if (!isVisible(el)) return false;
      const text = norm(`${el.textContent} ${el.getAttribute('aria-label')}`);
      return /balance|serum|sachet|radiance|محصول|سرم|ساشه/.test(text) && text.length < 90;
    }))];
  }

  function detailButton(section) {
    return [...section.querySelectorAll('button,a')].find(el => isVisible(el) && /جزئیات محصول|جزییات محصول|مشاهده محصول|product details|view product/i.test(norm(el.textContent))) || null;
  }

  function activeTab() {
    const i = state.tabs.findIndex(el => el.getAttribute('aria-selected') === 'true' || el.getAttribute('aria-pressed') === 'true' || el.dataset.state === 'active' || el.classList.contains('active'));
    return i >= 0 ? i : 0;
  }

  function ghostOut() {
    if (reduced || !state.image || !state.stage || !isVisible(state.image)) return;
    const ir = state.image.getBoundingClientRect(), sr = state.stage.getBoundingClientRect();
    const g = state.image.cloneNode(false);
    g.removeAttribute('id'); g.className = 'adrian-ghost';
    Object.assign(g.style,{left:`${ir.left-sr.left}px`,top:`${ir.top-sr.top}px`,width:`${ir.width}px`,height:`${ir.height}px`});
    state.stage.appendChild(g);
    setTimeout(() => g.remove(),900);
  }

  function switchProduct(offset) {
    if (state.tabs.length < 2) return;
    const i = (activeTab()+offset+state.tabs.length)%state.tabs.length;
    ghostOut(); state.tabs[i].click();
    [50,220,560].forEach(ms => setTimeout(refreshImage,ms));
  }

  function decorateStage() {
    const s = state.stage;
    if (!s) return;
    s.classList.add('adrian-stage');
    if (!s.querySelector(':scope>.adrian-ring')) s.prepend(ring());
    if (state.tabs.length > 1 && !s.querySelector(':scope>.adrian-prev')) {
      const prev = document.createElement('button'); prev.type='button'; prev.className='adrian-arrow adrian-prev'; prev.setAttribute('aria-label','محصول قبلی'); prev.innerHTML=arrow(false); prev.onclick=e=>{e.preventDefault();e.stopPropagation();switchProduct(-1)}; s.appendChild(prev);
      const next = document.createElement('button'); next.type='button'; next.className='adrian-arrow adrian-next'; next.setAttribute('aria-label','محصول بعدی'); next.innerHTML=arrow(true); next.onclick=e=>{e.preventDefault();e.stopPropagation();switchProduct(1)}; s.appendChild(next);
    }
    if (!s.querySelector(':scope>.adrian-hint')) { const h=document.createElement('span'); h.className='adrian-hint'; h.innerHTML=`${zoom} مشاهده محصول`; s.appendChild(h); }
  }

  function bindImage(img) {
    if (state.image && state.image !== img) state.image.classList.remove('adrian-product-image');
    state.image = img;
    img.classList.add('adrian-product-image'); img.tabIndex=0; img.setAttribute('role','button'); img.setAttribute('aria-label',`${img.alt||'محصول ساپونین'}؛ مشاهده جزئیات`);
  }

  function refreshImage() {
    if (!state.section) return;
    const next = findImage(state.section); if (!next) return;
    const changed = next !== state.image || next.currentSrc !== state.image?.currentSrc;
    const nextStage = findStage(next,state.section);
    if (nextStage !== state.stage) { state.stage?.classList.remove('adrian-stage'); state.stage=nextStage; decorateStage(); bindTilt(); }
    bindImage(next);
    if (changed) { next.classList.remove('adrian-enter'); void next.offsetWidth; next.classList.add('adrian-enter'); setTimeout(()=>next.classList.remove('adrian-enter'),1000); }
  }

  function bindTabs() {
    state.tabs = findTabs(state.section);
    state.tabs.forEach(tab => {
      if (tab.dataset.adrianBound) return;
      tab.dataset.adrianBound='1';
      tab.addEventListener('pointerdown',ghostOut,{passive:true});
      tab.addEventListener('click',()=>[40,220,560].forEach(ms=>setTimeout(refreshImage,ms)));
    });
  }

  function bindTilt() {
    const s=state.stage; if (!s || s.dataset.adrianTilt) return; s.dataset.adrianTilt='1';
    s.addEventListener('pointermove',e=>{if(reduced||e.pointerType==='touch')return;cancelAnimationFrame(state.raf);state.raf=requestAnimationFrame(()=>{const r=s.getBoundingClientRect(),x=(e.clientX-r.left)/r.width-.5,y=(e.clientY-r.top)/r.height-.5;s.style.setProperty('--aty',`${(x*11).toFixed(2)}deg`);s.style.setProperty('--atx',`${(-y*7).toFixed(2)}deg`)})},{passive:true});
    s.addEventListener('pointerleave',()=>{s.style.setProperty('--aty','0deg');s.style.setProperty('--atx','0deg')},{passive:true});
  }

  function snapshot() {
    const headings=[...state.section.querySelectorAll('h1,h2,h3,h4')].filter(isVisible).map(x=>x.textContent.trim()).filter(Boolean);
    const title=headings.find(x=>/[\u0600-\u06ff]/.test(x)&&!/محصولات/.test(x))||state.image?.alt||'محصول ساپونین';
    const tab=state.tabs[activeTab()];
    const en=tab?.textContent?.trim()||headings.find(x=>/[A-Za-z]/.test(x))||'Saponine Product';
    const paragraphs=[...state.section.querySelectorAll('p')].filter(isVisible).map(x=>x.textContent.trim()).filter(x=>x.length>=35&&x.length<=650).sort((a,b)=>b.length-a.length);
    return {title,en,description:paragraphs[0]||'محصولی از خانواده ساپونین با تمرکز بر کیفیت، سلامت و تجربه مصرف روزانه.'};
  }

  function makeModal() {
    const c=snapshot(), m=document.createElement('div'); m.className='adrian-modal'; m.setAttribute('role','dialog'); m.setAttribute('aria-modal','true'); m.setAttribute('aria-labelledby','adrianTitle');
    m.innerHTML=`<div class="adrian-backdrop" data-close></div><div class="adrian-shell" role="document"><button class="adrian-close" type="button" aria-label="بستن">${closeIcon}</button><div class="adrian-media">${ring('adrian-modal-ring').outerHTML}<img class="adrian-modal-image" src="${esc(state.image.currentSrc||state.image.src)}" alt="${esc(state.image.alt||c.title)}"></div><div class="adrian-copy"><span class="adrian-kicker">SAPONINE PRODUCT</span><h2 class="adrian-title" id="adrianTitle">${esc(c.title)}</h2><p class="adrian-en">${esc(c.en)}</p><p class="adrian-description">${esc(c.description)}</p><span class="adrian-divider" aria-hidden="true"></span><p class="adrian-note">برای مشخصات و شیوه مصرف، اطلاعات رسمی درج‌شده روی بسته‌بندی محصول را بررسی کنید.</p></div></div>`;
    return m;
  }

  function animateFlip(from,to,img,reverse=false) {
    if (reduced || !from || !to || from.width<1 || to.width<1) return Promise.resolve();
    const g=img.cloneNode(false); g.removeAttribute('id'); g.className='adrian-flip'; Object.assign(g.style,{left:`${from.left}px`,top:`${from.top}px`,width:`${from.width}px`,height:`${from.height}px`}); document.body.appendChild(g);
    const animation=g.animate([{left:`${from.left}px`,top:`${from.top}px`,width:`${from.width}px`,height:`${from.height}px`,transform:'perspective(1400px) rotateY(0deg)',opacity:1},{left:`${to.left}px`,top:`${to.top}px`,width:`${to.width}px`,height:`${to.height}px`,transform:`perspective(1400px) rotateY(${reverse?0:-10}deg)`,opacity:1}],{duration:reverse?650:850,easing:'cubic-bezier(.22,.8,.18,1)',fill:'forwards'});
    return animation.finished.catch(()=>{}).finally(()=>g.remove());
  }

  async function openModal() {
    if (!state.image || state.modal || !isVisible(state.image)) return;
    state.focus=document.activeElement; const source=state.image.getBoundingClientRect(), m=makeModal(); state.modal=m; document.body.appendChild(m); document.documentElement.classList.add('adrian-modal-open');document.body.classList.add('adrian-modal-open');
    m.querySelectorAll('[data-close],.adrian-close').forEach(x=>x.addEventListener('click',closeModal));
    m.addEventListener('keydown',e=>{if(e.key==='Escape'){e.preventDefault();closeModal()}});
    requestAnimationFrame(()=>{m.classList.add('is-open');requestAnimationFrame(async()=>{const target=m.querySelector('.adrian-modal-image');await animateFlip(source,target.getBoundingClientRect(),state.image);if(state.modal===m)target.classList.add('ready')})});
    setTimeout(()=>m.querySelector('.adrian-close')?.focus({preventScroll:true}),100);
  }

  async function closeModal() {
    const m=state.modal;if(!m)return;state.modal=null;const img=m.querySelector('.adrian-modal-image'),from=img?.getBoundingClientRect(),to=state.image&&isVisible(state.image)?state.image.getBoundingClientRect():null;img?.classList.remove('ready');m.classList.remove('is-open');if(from&&to)await animateFlip(from,to,state.image,true);else await new Promise(r=>setTimeout(r,350));m.remove();document.documentElement.classList.remove('adrian-modal-open');document.body.classList.remove('adrian-modal-open');state.focus?.focus?.({preventScroll:true});
  }

  function intercept(e) {
    if (!state.section || !state.section.contains(e.target)) return;
    const image=e.target.closest?.('.adrian-product-image');
    const detail=e.target.closest?.('button,a');
    if (image || (detail && /جزئیات محصول|جزییات محصول|مشاهده محصول|product details|view product/i.test(norm(detail.textContent)))) { e.preventDefault();e.stopPropagation();e.stopImmediatePropagation();openModal(); }
  }

  function enhance(section) {
    const image=findImage(section); if (!image) return false;
    state.section=section; section.setAttribute('data-adrian-product','true'); state.stage=findStage(image,section); bindTabs(); decorateStage(); bindImage(image); bindTilt();
    const d=detailButton(section); if(d)d.classList.add('adrian-detail');
    section.addEventListener('click',intercept,true);
    section.addEventListener('keydown',e=>{if((e.key==='Enter'||e.key===' ')&&e.target===state.image){e.preventDefault();openModal()}},true);
    state.observer=new IntersectionObserver(entries=>entries.forEach(x=>{if(x.isIntersecting)section.classList.add('adrian-ready')}),{threshold:.18});state.observer.observe(section);
    state.sourceObserver=new MutationObserver(()=>{clearTimeout(state.timer);state.timer=setTimeout(()=>{bindTabs();refreshImage();const b=detailButton(section);if(b)b.classList.add('adrian-detail')},45)});state.sourceObserver.observe(section,{subtree:true,childList:true,attributes:true,attributeFilter:['src','srcset','alt','class','aria-selected','aria-pressed','data-state']});
    requestAnimationFrame(()=>{const r=section.getBoundingClientRect();if(r.top<innerHeight*.9&&r.bottom>innerHeight*.1)section.classList.add('adrian-ready')});
    return true;
  }

  function boot() {
    if (state.section?.isConnected) return;
    const found=locateSection(); if(found&&enhance(found))return;
    const watcher=new MutationObserver(()=>{const next=locateSection();if(next&&enhance(next))watcher.disconnect()});watcher.observe(document.documentElement,{subtree:true,childList:true});setTimeout(()=>watcher.disconnect(),20000);
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot,{once:true});else boot();
  addEventListener('pageshow',boot);
})();
