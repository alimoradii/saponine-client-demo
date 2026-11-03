'use strict';
(()=>{
  const $=(s,r=document)=>r.querySelector(s), $$=(s,r=document)=>[...r.querySelectorAll(s)];
  const P=window.SAPONINE_PRODUCTS;
  const icon=n=>`<span class="icon icon-${n}" aria-hidden="true"></span>`;
  const panelIds=['home','features','brands','about','products','pharmacies','contact'];
  const panels=$$('.page-panel'), desktop=matchMedia('(min-width:1024px)'), reduced=matchMedia('(prefers-reduced-motion:reduce)');
  const descriptions=[
    'شامپو ساپو‌زینک ۲٪ از خانواده محصولات بهداشتی ساپونین است. این محصول در بطری ۱۴۰ میلی‌لیتری همراه با جعبه عرضه می‌شود. برای آشنایی با مشخصات کامل و اطلاعات مصرف، صفحه رسمی محصول را ببینید.',
    'ژل بی‌باکتر ۷۰٪ از محصولات بهداشتی ساپونین است که در تیوب ۶۰ میلی‌لیتری معرفی شده است. مشخصات تکمیلی و اطلاعات محصول از طریق صفحه رسمی شرکت در دسترس است.',
    'محلول موضعی پوویدون آیوداین ۱۰٪ یکی از محصولات دارویی ساپونین است. این محصول در بطری ۲۵۰ میلی‌لیتری معرفی شده است. اطلاعات مصرف را از برچسب، بروشور و صفحه رسمی محصول دریافت کنید.',
    'شامپو پرمترین ۱٪ در خانواده محصولات بهداشتی ساپونین قرار دارد و در بسته‌بندی ۶۰ میلی‌لیتری عرضه می‌شود. برای شناخت کاربرد و اطلاعات مصرف، به اطلاعات رسمی محصول مراجعه کنید.',
    'قرص ویتامین ب۱ ۳۰۰ میلی‌گرم از محصولات ساپونین است که در بسته‌بندی‌های ۳۰ و ۱۰۰ عددی معرفی شده است. مشخصات کامل محصول در وب‌سایت رسمی شرکت قابل مشاهده است.'
  ];
  let section=0, product=0, slide=0, wheelSum=0, lastWheel=0, lockedUntil=0, paused=false, hover=false, timer, previousFocus, galleryTimer;
  let reduce=reduced.matches;
  try{reduce=reduce||localStorage.getItem('saponine-reduced-motion')==='true';}catch{}
  const dialog=$('#dialog'), body=$('#dialogBody');
  function closeMenus(){ $('#megaMenu').hidden=true;$('#megaToggle').setAttribute('aria-expanded','false');$('#mobileNav').hidden=true;$('#menuToggle').setAttribute('aria-expanded','false');$('#menuToggle').setAttribute('aria-label','باز کردن منو'); }
  function goSection(n,{url=true,focus=false}={}){
    n=Math.max(0,Math.min(panels.length-1,n));section=n;
    panels.forEach((p,i)=>{p.classList.toggle('is-active',i===n);p.classList.toggle('is-before',i<n);p.inert=desktop.matches&&i!==n;p.setAttribute('aria-hidden',desktop.matches?String(i!==n):'false');});
    $$('.rail-link').forEach((a,i)=>{a.classList.toggle('active',i===n);if(i===n)a.setAttribute('aria-current','step');else a.removeAttribute('aria-current');});
    document.body.dataset.section=panelIds[n];
    if(url)history.replaceState(null,'','#'+panelIds[n]);
    if(!desktop.matches)panels[n].scrollIntoView({behavior:reduce?'instant':'smooth',block:'start'});
    if(n===3){panels[n].classList.remove('story-intro');requestAnimationFrame(()=>panels[n].classList.add('story-intro'));}
    if(focus){const h=$('h2,h1',panels[n]);if(h){h.tabIndex=-1;h.focus({preventScroll:true});}}
    closeMenus();
  }
  function setSlide(n,manual=false){
    slide=(n+P.length)%P.length;
    $$('.hero-slide').forEach((el,i)=>{el.classList.toggle('is-current',i===slide);el.inert=i!==slide;el.setAttribute('aria-hidden',String(i!==slide));});
    $$('.hero-dot').forEach((el,i)=>el.setAttribute('aria-current',String(i===slide)));
    $('#home').dataset.activeSlide=String(slide);
    if(manual){paused=true;$('#heroStatus').textContent=`اسلاید ${slide+1} از ۵؛ ${P[slide].name}`;updatePlay();}
  }
  function updatePlay(){const stopped=paused||reduce;$('#heroPlay').innerHTML=icon(stopped?'play':'pause');$('#heroPlay').setAttribute('aria-label',stopped?'پخش خودکار اسلایدها':'توقف پخش خودکار');$('#heroPlay').setAttribute('aria-pressed',String(stopped));$('#home').dataset.autoplay=String(!stopped);}
  function motion(){document.documentElement.classList.toggle('reduce-motion',reduce);$('#motionToggle').setAttribute('aria-pressed',String(reduce));$('#motionToggle').setAttribute('aria-label',reduce?'فعال کردن حرکت':'کاهش حرکت');$('#motionToggle').innerHTML=icon(reduce?'play':'pause');updatePlay();}
  function positionOrbit(){
    const radius=$('#productOrbit').clientWidth/2;
    $$('.orbit-item').forEach((el,i)=>{const visible=(i-product+P.length)%P.length;const angle=(180+visible*90)*Math.PI/180;el.style.setProperty('--ox',Math.cos(angle)*radius+'px');el.style.setProperty('--oy',Math.sin(angle)*radius+'px');el.classList.toggle('active',i===product);el.setAttribute('aria-pressed',String(i===product));el.style.opacity=visible===4?'0':'1';el.inert=visible===4||visible===2;el.setAttribute('aria-hidden',String(visible===4||visible===2));el.style.zIndex=i===product?'3':'2';});
  }
  function selectProduct(n,navigate=false){
    product=(n+P.length)%P.length;const p=P[product];
    $('#productName').textContent=p.name;$('#productEnglish').textContent=p.en;$('#productDescription').textContent=descriptions[product];$('#orbitLabel').innerHTML=p.title;$('#products').dataset.product=p.slug;
    $('#productFacts').innerHTML=[['pills','شکل محصول',p.category],['file','بسته‌بندی',p.form],['shield','گروه محصول',p.group],['arrow','شناخت بیشتر','اطلاعات رسمی ساپونین']].map(([ic,title,value])=>`<div class="product-fact"><span class="fact-symbol">${icon(ic)}</span><div><strong>${title}</strong><small>${value}</small></div></div>`).join('');
    positionOrbit();if(navigate)goSection(4);
  }
  function openDialog(html){previousFocus=document.activeElement;body.innerHTML=html;closeMenus();if(!dialog.open)dialog.showModal();dialog.scrollTop=0;}
  function showProduct(i){
    const p=P[i];openDialog(`<div class="detail-layout"><img src="${p.image}" alt="${p.name}" class="${i===4?'wide':''}"><div><h2 id="dialogTitle">${p.name}</h2><p class="product-en">${p.en}</p><p>${descriptions[i]}</p><dl><div><dt>شکل محصول</dt><dd>${p.category}</dd></div><div><dt>بسته‌بندی</dt><dd>${p.form}</dd></div><div><dt>گروه</dt><dd>${p.group}</dd></div></dl><a class="btn" href="${p.source}" target="_blank" rel="noopener noreferrer">صفحه رسمی محصول ${icon('arrow')}</a></div></div>`);
  }
  const normalize=s=>s.toLowerCase().replace(/[يى]/g,'ی').replace(/ك/g,'ک').replace(/[\u200c\s–-]/g,'');
  function catalog(search=false){
    openDialog(`<h2 id="dialogTitle">${search?'جستجوی محصولات':'محصولات ساپونین'}</h2><label class="search-field">${icon('search')}<input id="catalogSearch" type="search" autocomplete="off" placeholder="جستجو کنید ..." aria-label="جستجو در محصولات"></label><div class="filter-bar" aria-label="گروه محصولات">${[['all','همه'],['shampoo','شامپو'],['gel','ژل'],['solution','محلول'],['tablet','قرص']].map(([key,title])=>`<button class="filter-chip" data-filter="${key}" aria-pressed="${key==='all'}">${title}</button>`).join('')}</div><div id="catalogResults" class="result-grid"></div><div class="search-banner" id="searchBanner" ${search?'':'hidden'}><div><strong>ساپونین</strong><br><span>خانواده‌ای از محصولات، برای مراقبت</span></div><img src="${P[0].image}" alt="شامپو ساپو‌زینک"></div><p class="sr-only" id="searchStatus" role="status"></p>`);
    let filter='all';
    function render(){const q=normalize($('#catalogSearch').value);const items=P.map((p,i)=>({p,i})).filter(({p})=>(filter==='all'||p.key===filter)&&normalize(p.name+' '+p.en+' '+p.short).includes(q));const blankSearch=search&&!q&&filter==='all';$('#searchBanner').hidden=!blankSearch;$('#catalogResults').hidden=blankSearch;$('#catalogResults').innerHTML=items.length?items.map(({p,i})=>`<button class="result-card" data-detail="${i}"><img src="${p.image}" alt="${p.name}" class="${i===4?'wide':''}"><strong>${p.name}</strong><small>${p.en}</small></button>`).join(''):'<p class="empty-state">نتیجه‌ای یافت نشد. نام دیگری را جستجو کنید.</p>';$('#searchStatus').textContent=`${items.length} محصول`;}
    $('#catalogSearch').addEventListener('input',render);$$('[data-filter]',body).forEach(el=>el.addEventListener('click',()=>{filter=el.dataset.filter;$$('[data-filter]',body).forEach(b=>b.setAttribute('aria-pressed',String(b===el)));render();}));render();if(search)$('#catalogSearch').focus({preventScroll:true});
  }
  function requestForm(){openDialog(`<h2 id="dialogTitle">ارتباط و همکاری با ساپونین</h2><p class="form-notice">متن درخواست شما برای کپی آماده می‌شود. این فرم پیامی ارسال نمی‌کند.</p><form class="request-form" id="requestForm"><div class="form-pair"><label>نام و نام خانوادگی<input name="name" required maxlength="100" autocomplete="name"></label><label>شرکت یا حوزه فعالیت<input name="company" maxlength="140" autocomplete="organization"></label></div><label>شماره تماس<input type="tel" name="phone" maxlength="30" autocomplete="tel" dir="ltr"></label><label>موضوع<select name="subject"><option>پیشنهاد همکاری</option><option>اطلاعات محصولات</option><option>درخواست موجودی و توزیع</option></select></label><label>متن درخواست<textarea name="message" required minlength="5" maxlength="2000"></textarea></label><button class="btn" type="submit">آماده‌سازی درخواست ${icon('arrow')}</button></form>`);$('#requestForm').addEventListener('submit',e=>{e.preventDefault();const f=new FormData(e.currentTarget);const message=`مدیریت محترم شرکت ساپونین ایران\nبا سلام و احترام،\nاینجانب ${String(f.get('name')).trim()}${f.get('company')?' از '+String(f.get('company')).trim():''}، در خصوص ${f.get('subject')} به استحضار می‌رسانم:\n${String(f.get('message')).trim()}${f.get('phone')?'\nشماره تماس: '+String(f.get('phone')).trim():''}\nبا سپاس`;body.innerHTML='<h2 id="dialogTitle">متن درخواست آماده است</h2><p>متن زیر را کپی و از مسیر ارتباطی موردنظرتان ارسال کنید.</p><div class="request-output" id="requestOutput" tabindex="0"></div><button class="btn" id="copyRequest">کپی متن درخواست</button>';$('#requestOutput').textContent=message;$('#copyRequest').addEventListener('click',async()=>{try{await navigator.clipboard.writeText(message);toast('متن درخواست کپی شد.');}catch{const r=document.createRange();r.selectNodeContents($('#requestOutput'));const s=getSelection();s.removeAllRanges();s.addRange(r);toast('متن انتخاب شد؛ آن را کپی کنید.');}});});}
  function faq(){openDialog(`<h2 id="dialogTitle">سوالات متداول</h2>${[
    ['اطلاعات کامل هر محصول را از کجا دریافت کنیم؟','در بخش محصولات، گزینه جزئیات محصول را باز کنید. لینک صفحه رسمی همان محصول در دسترس است.'],
    ['برای خرید و استعلام موجودی با کجا تماس بگیریم؟','برای پیگیری موجودی و اطلاعات توزیع، با دفتر مرکزی ساپونین به شماره ۰۲۱–۸۶۰۳۰۰۹۴ تماس بگیرید.'],
    ['چطور پیشنهاد همکاری خود را مطرح کنیم؟','از بخش فرم همکاری، متن درخواست را آماده کنید و از طریق راه‌های ارتباطی رسمی شرکت ارسال کنید.'],
    ['آیا سفارش آنلاین در این صفحه ثبت می‌شود؟','این صفحه برای معرفی شرکت و محصولات است و سبد خرید یا پرداخت آنلاین ندارد.']
  ].map(([q,a])=>`<details class="faq-item"><summary>${q}</summary><p>${a}</p></details>`).join('')}`);}
  function pharmacy(){openDialog(`<h2 id="dialogTitle">ارتباط با واحد فروش</h2><p>برای پیگیری موجودی محصولات و دریافت اطلاعات توزیع با دفتر شرکت تماس بگیرید.</p><div class="filter-bar"><a class="btn" href="tel:+982186030094">تماس با ۰۲۱–۸۶۰۳۰۰۹۴ ${icon('phone')}</a><a class="btn outline" href="mailto:info@saponine.com">ایمیل به شرکت ${icon('mail')}</a></div><p class="form-notice">فهرست تاییدشده داروخانه‌های طرف همکاری هنوز در این دمو درج نشده است.</p>`);}
  function aboutMore(){openDialog('<h2 id="dialogTitle">درباره ساپونین ایران</h2><p>ساپونین ایران در سال ۱۳۶۸ ثبت شد و فعالیت خود را با تولید صابون‌های طبی آغاز کرد. شامپوهای طبی، فرآورده‌های مایع، قرص‌ها و مکمل‌های رژیمی و غذایی در ادامه به مجموعه محصولات اضافه شدند.</p><p>برای مطالعه اطلاعات کامل شرکت، به وب‌سایت رسمی ساپونین مراجعه کنید.</p><a class="btn" href="https://saponineco.com/" target="_blank" rel="noopener noreferrer">وب‌سایت رسمی ساپونین '+icon('arrow')+'</a>');}
  function gallery(){
    const images=['official-company.jpg','slider-hair.webp','slider-hands.webp','slider-laboratory.webp','slider-lifestyle.webp'];
    const captions=['تصویر شرکت از وب‌سایت رسمی ساپونین','عکس آرشیوی مراقبت از مو · Nyana Stoica / Unsplash','عکس آرشیوی شست‌وشوی دست · Claudio Schwarz / Unsplash','عکس آرشیوی آزمایشگاه · NIAID / Unsplash','عکس آرشیوی پیاده‌روی · Vitaly Gariev / Unsplash'];
    openDialog('<h2 id="dialogTitle">معرفی تصویری</h2><img class="gallery-large" id="galleryLarge" src="assets/official-company.jpg" alt="شرکت ساپونین"><p class="gallery-caption" id="galleryCaption"></p><div class="gallery-thumbs">'+images.map((src,i)=>`<button data-gallery="${i}" aria-label="تصویر ${i+1}" aria-current="${i===0}"><img src="assets/${src}" alt=""></button>`).join('')+'</div>');
    let index=0;const show=i=>{index=i;$('#galleryLarge').src='assets/'+images[i];$('#galleryLarge').alt=captions[i];$('#galleryCaption').textContent=captions[i];$$('[data-gallery]').forEach(b=>b.setAttribute('aria-current',String(+b.dataset.gallery===i)));};show(0);$$('[data-gallery]').forEach(b=>b.addEventListener('click',()=>{clearInterval(galleryTimer);show(+b.dataset.gallery);}));if(!reduce)galleryTimer=setInterval(()=>{if(dialog.open)show((index+1)%images.length);},4500);
  }
  function toast(text){$('#toast').textContent=text;$('#toast').hidden=false;setTimeout(()=>$('#toast').hidden=true,3000);}
  document.addEventListener('click',e=>{
    const el=e.target.closest('a,button');if(!el)return;
    if(el.hasAttribute('data-detail')){e.preventDefault();showProduct(+el.dataset.detail);return;}
    if(el.hasAttribute('data-select-product')){e.preventDefault();selectProduct(+el.dataset.selectProduct,true);return;}
    if(el.hasAttribute('data-catalog')){e.preventDefault();catalog();return;}
    if(el.hasAttribute('data-search')){e.preventDefault();catalog(true);return;}
    if(el.hasAttribute('data-contact')){e.preventDefault();requestForm();return;}
    if(el.hasAttribute('data-faq')){e.preventDefault();faq();return;}
    if(el.hasAttribute('data-pharmacy')){e.preventDefault();pharmacy();return;}
    const href=el.getAttribute('href');if(href&&href.startsWith('#')&&panelIds.includes(href.slice(1))){e.preventDefault();goSection(panelIds.indexOf(href.slice(1)));}
  });
  $('#megaToggle').addEventListener('click',()=>{const open=$('#megaMenu').hidden;$('#megaMenu').hidden=!open;$('#megaToggle').setAttribute('aria-expanded',String(open));});
  $('#megaToggle').addEventListener('pointerenter',()=>{if(desktop.matches){$('#megaMenu').hidden=false;$('#megaToggle').setAttribute('aria-expanded','true');}});
  $('.site-header').addEventListener('pointerleave',()=>{if(desktop.matches){$('#megaMenu').hidden=true;$('#megaToggle').setAttribute('aria-expanded','false');}});
  $('#menuToggle').addEventListener('click',()=>{const open=$('#mobileNav').hidden;$('#mobileNav').hidden=!open;$('#menuToggle').setAttribute('aria-expanded',String(open));$('#menuToggle').setAttribute('aria-label',open?'بستن منو':'باز کردن منو');});
  $('#searchOpen').addEventListener('click',()=>catalog(true));$('#productDetails').addEventListener('click',()=>showProduct(product));$('#productNext').addEventListener('click',()=>selectProduct(product+1));$('#productPrevious').addEventListener('click',()=>selectProduct(product-1));$$('[data-orbit]').forEach(b=>b.addEventListener('click',()=>selectProduct(+b.dataset.orbit)));$$('[data-slide]').forEach(b=>b.addEventListener('click',()=>setSlide(+b.dataset.slide,true)));$('[data-next-section]').addEventListener('click',()=>goSection(1));
  $('#heroPlay').addEventListener('click',()=>{if(paused||reduce){reduce=false;paused=false;}else paused=true;motion();});
  $('#motionToggle').addEventListener('click',()=>{reduce=!reduce;try{localStorage.setItem('saponine-reduced-motion',String(reduce));}catch{}motion();});
  $('#aboutMore').addEventListener('click',aboutMore);$('#storyOpen').addEventListener('click',gallery);$('#dialogClose').addEventListener('click',()=>dialog.close());
  dialog.addEventListener('click',e=>{if(e.target===dialog){const r=dialog.getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom)dialog.close();}});
  dialog.addEventListener('close',()=>{clearInterval(galleryTimer);if(previousFocus?.isConnected&&!previousFocus.closest('[inert]'))previousFocus.focus({preventScroll:true});});
  document.addEventListener('keydown',e=>{
    if(e.key==='Escape'){closeMenus();return;}
    if(dialog.open||/INPUT|TEXTAREA|SELECT/.test(e.target.tagName)||e.altKey||e.ctrlKey||e.metaKey)return;
    if(['ArrowLeft','ArrowRight'].includes(e.key)&&[0,4].includes(section)){e.preventDefault();const d=e.key==='ArrowLeft'?1:-1;if(section===0)setSlide(slide+d,true);else selectProduct(product+d);return;}
    if(!desktop.matches)return;
    const map={ArrowDown:section+1,PageDown:section+1,ArrowUp:section-1,PageUp:section-1,Home:0,End:6};if(e.key in map){e.preventDefault();goSection(map[e.key]);}
  });
  document.addEventListener('wheel',e=>{if(!desktop.matches||dialog.open||!$('#megaMenu').hidden||!$('#mobileNav').hidden||e.ctrlKey||Math.abs(e.deltaX)>Math.abs(e.deltaY))return;e.preventDefault();const now=performance.now();if(now<lockedUntil)return;if(now-lastWheel>180)wheelSum=0;wheelSum+=e.deltaY;lastWheel=now;if(Math.abs(wheelSum)>45){goSection(section+(wheelSum>0?1:-1));wheelSum=0;lockedUntil=now+(reduce?250:1150);}},{passive:false});
  let swipe=null;$('#home').addEventListener('pointerdown',e=>{if(e.target.closest('a,button'))return;swipe={x:e.clientX,y:e.clientY};});$('#home').addEventListener('pointerup',e=>{if(!swipe)return;const dx=e.clientX-swipe.x,dy=e.clientY-swipe.y;swipe=null;if(Math.abs(dx)>55&&Math.abs(dx)>Math.abs(dy)*1.4)setSlide(slide+(dx>0?1:-1),true);});$('#home').addEventListener('pointercancel',()=>swipe=null);$('#home').addEventListener('pointerenter',()=>hover=true);$('#home').addEventListener('pointerleave',()=>hover=false);
  timer=setInterval(()=>{if(!paused&&!reduce&&!document.hidden&&!dialog.open&&section===0&&!hover)setSlide(slide+1);},7000);
  const mobileObserver=new IntersectionObserver(entries=>{if(desktop.matches)return;const current=entries.filter(e=>e.isIntersecting).sort((a,b)=>b.intersectionRatio-a.intersectionRatio)[0];if(current)section=panelIds.indexOf(current.target.id);},{threshold:[.1,.35,.6]});panels.forEach(p=>mobileObserver.observe(p));
  window.addEventListener('hashchange',()=>{const i=panelIds.indexOf(location.hash.slice(1));if(i>=0)goSection(i,{url:false});});
  desktop.addEventListener('change',()=>goSection(section,{url:false}));reduced.addEventListener('change',e=>{reduce=e.matches;motion();});window.addEventListener('resize',positionOrbit);
  motion();selectProduct(0);setSlide(0);const initial=panelIds.indexOf(location.hash.slice(1));goSection(initial<0?0:initial,{url:false});
})();
