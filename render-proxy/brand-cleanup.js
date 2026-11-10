(() => {
  'use strict';

  const LOVABLE_TEXT = /(?:edit\s+with\s+lovable|lovable)/i;
  const LOVABLE_URL = /(?:lovable\.app|lovable\.dev|gpteng\.co)/i;
  const observedRoots = new WeakSet();

  function markAndRemove(element) {
    if (!(element instanceof Element)) return false;

    const label = [
      element.getAttribute('aria-label'),
      element.getAttribute('title'),
      element.getAttribute('data-testid'),
      element.id,
      element.className,
    ].filter(Boolean).join(' ');

    const href = element instanceof HTMLAnchorElement ? element.href : '';
    const src = element instanceof HTMLIFrameElement ? element.src : '';
    const text = (element.textContent || '').trim().slice(0, 160);
    const shadowText = element.shadowRoot ? (element.shadowRoot.textContent || '').trim().slice(0, 160) : '';

    if (LOVABLE_TEXT.test(label) || LOVABLE_URL.test(href) || LOVABLE_URL.test(src) || LOVABLE_TEXT.test(text) || LOVABLE_TEXT.test(shadowText)) {
      let target = element;
      const fixedParent = element.closest('[style*="position: fixed" i], [style*="position:fixed" i], [style*="position: sticky" i], [style*="position:sticky" i]');
      if (fixedParent) target = fixedParent;
      target.setAttribute('data-saponine-remove-lovable', 'true');
      target.remove();
      return true;
    }

    return false;
  }

  function scan(root = document) {
    if (!root || !root.querySelectorAll) return;

    const selectors = [
      '#lovable-badge',
      '[data-lovable-badge]',
      '[data-testid*="lovable" i]',
      '[id*="lovable" i]',
      '[class*="lovable" i]',
      '[aria-label*="lovable" i]',
      '[title*="lovable" i]',
      'a[href*="lovable.app" i]',
      'a[href*="lovable.dev" i]',
      'iframe[src*="lovable" i]',
      'iframe[src*="gpteng" i]',
    ].join(',');

    root.querySelectorAll(selectors).forEach(markAndRemove);

    root.querySelectorAll('a,button,div,span,iframe,*').forEach((element) => {
      if (element.shadowRoot) {
        if (LOVABLE_TEXT.test(element.shadowRoot.textContent || '')) {
          markAndRemove(element);
          return;
        }
        observe(element.shadowRoot);
      }
    });
  }

  function observe(root) {
    if (!root || observedRoots.has(root)) return;
    observedRoots.add(root);
    scan(root);
    const observer = new MutationObserver(() => scan(root));
    observer.observe(root, { childList: true, subtree: true, attributes: true, attributeFilter: ['href', 'src', 'style', 'class', 'id', 'title', 'aria-label'] });
  }

  const originalAttachShadow = Element.prototype.attachShadow;
  if (originalAttachShadow && !originalAttachShadow.__saponinePatched) {
    const patched = function(init) {
      const shadowRoot = originalAttachShadow.call(this, init);
      queueMicrotask(() => observe(shadowRoot));
      return shadowRoot;
    };
    patched.__saponinePatched = true;
    Element.prototype.attachShadow = patched;
  }

  function boot() {
    observe(document.documentElement);
    scan(document);
    [50, 250, 750, 1500, 3000, 6000].forEach((delay) => setTimeout(() => scan(document), delay));
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
  else boot();
})();
