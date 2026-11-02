/* Real company photographs in an animated 3D frame.
 * Product pixels, colors and labels are never regenerated or relabeled.
 * This is a photo presentation, not a 360-degree product scan.
 */
(() => {
 'use strict';
 const clamp=(v,a,b)=>Math.max(a,Math.min(b,v));
 window.createSaponinePhotoStage=async function(element,{layout='hero',ready=()=>{},fallback=()=>{}}={}) {
  const products=window.SAPONINE_PRODUCTS;
  if(!products?.length)return null;
  element.classList.add('official-photo-stage');
  element.dataset.renderer='official-photography';
  const frame=document.createElement('div');frame.className='official-photo-frame';
  const photo=document.createElement('img');photo.width=1920;photo.height=1280;photo.draggable=false;
  frame.append(photo);element.replaceChildren(frame);
  const state={edition:0,progress:0,angle:0,motion:true,visible:true};let selected=-1,loaded=false;
  const isMobile=()=>element.clientWidth<=760;
  function positionX(progress){if(layout!=='story'||isMobile())return isMobile()?50:27;const q=clamp(progress,0,1)*3,i=Math.min(2,Math.floor(q)),t=q-i,s=t*t*(3-2*t),poses=[27,73,27,73];return poses[i]+(poses[i+1]-poses[i])*s}
  function render(){
   const mobile=isMobile(),p=clamp(state.progress,0,1),moving=state.motion;
   const desired=clamp(Math.round(state.edition),0,products.length-1),product=products[desired];
   if(selected!==desired){selected=desired;photo.src=product.image;photo.alt='عکس '+product.name+' از وب‌سایت ساپونین ایران';element.setAttribute('aria-label',photo.alt+'؛ حرکت تصویر با کشیدن یا کلیدهای جهت');element.dataset.product=product.slug;}
   const story=layout==='story',features=layout==='features';
   const left=positionX(p),top=features?(mobile?51:60):(mobile?(story?74:71):53);
   frame.style.left=left+'%';frame.style.top=top+'%';
   frame.style.width=features?(mobile?'62%':'27%'):(mobile?(story?'78%':'84%'):'42%');
   frame.style.maxWidth=features?'380px':'740px';
   frame.style.aspectRatio=product.slug==='vitamin-b1'?'1.5':'.92';
   photo.style.objectFit=product.slug==='vitamin-b1'?'contain':'cover';
   const tilt=moving?clamp(Math.sin(state.angle)*19+(story?Math.sin(p*Math.PI*3)*10:-5),-24,24):0;
   const pitch=moving?(features?-3:Math.cos(p*Math.PI*3)*3):0;
   const roll=moving?(story?Math.sin(p*Math.PI*2)*2:-1.5):0;
   const zoom=moving?(features?1+clamp((p-.53)/.23,0,1)*.04:1+Math.sin(p*Math.PI)*.035):1;
   frame.style.transform='translate(-50%,-50%) perspective(1300px) rotateX('+pitch+'deg) rotateY('+tilt+'deg) rotateZ('+roll+'deg) scale('+zoom+')';
   element.dataset.sceneProgress=p.toFixed(3);
  }
  photo.addEventListener('load',()=>{loaded=true;frame.classList.add('loaded');ready();});
  photo.addEventListener('error',()=>{if(!loaded)fallback();});
  render();
  return {update(next){Object.assign(state,next);render();},resize:render,positionX};
 };
})();
