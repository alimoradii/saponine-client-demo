# Opening slider QA · 2026-11-03

final result: passed

Scope: add a five-slide photographic opening carousel to the three existing Saponine demos, inspired by the live Adrian Salamat opening slider. The requested Saponine identity, actual IRANSans, official product cutouts, lower page sections and three distinct light/dark/gallery directions remain the baseline. This is a requested component adaptation, not a pixel-for-pixel copy of Adrian's advertising artwork or logos.

New browser screenshots and visual comparisons are local QA evidence and are excluded from this public source update. Evidence paths below refer to the verified local checkout. Public machine-readable check results remain in qa/.

## Source and comparison evidence

Source visual truth: https://adriansalamat.com/ and qa/slider-adrian-reference.webp. The source hero was opened, its five-slide navigation and image changes inspected, and the screenshot captured before implementation.

Rendered implementation: qa/slider-source-desktop.webp, qa/slider-motion-desktop.webp, qa/slider-reference-desktop.webp. Full equal-viewport pairs: qa/slider-source-comparison.jpg, qa/slider-motion-comparison.jpg, qa/slider-reference-comparison.jpg. Combined full-view input: qa/slider-full-comparison.jpg. Focused header, typography and control comparison: qa/slider-focused-comparison.jpg. Both source and implementation were opened and viewed together in these comparison inputs.

Desktop source and implementation: 1363×936 pixels/CSS viewport, devicePixelRatio 1. The app content width excludes Chromium's visible vertical scrollbar. Screenshots represent the hero at scrollY 0 with no modal, the first Sapo-Zinc slide in light/gallery and Bibacter in dark. Motion is paused for stable comparison; the normal user load has motion and autoplay enabled unless reduced motion is requested. Expected differences are Saponine's supplied logo/purple identity, Persian type/content, real Saponine packaging, real stock photography, and preservation of the three previously selected art directions. The light/gallery use a quiet copy column; the dark version is full-bleed photography.

Responsive evidence: qa/slider-{source,motion,reference}-mobile.webp and qa/slider-{source,motion,reference}-tablet.webp. Mobile content viewport 390×844 CSS px, iframe scale 1, screenshot content crop 391×844 including the rounded pixel edge. Tablet 834×1194 CSS px, scale .73, normalized visible crop 610×872 pixels. All three demos passed with local fonts loaded and no document-level horizontal overflow. Tests use Chromium responsive frames, not physical iOS/Android or Safari.

## Required fidelity surfaces

- Fonts and typography: original local IRANSans Regular/Bold preserved and loaded. Persian RTL headings, consistent two-line wrapping, readable CTA/counter, and Latin product metadata checked. All five slide copies fit before the mobile photograph; minimum measured gap is positive. Type is actual editable HTML, not a flattened banner.
- Spacing/layout rhythm: floating brand header, full-height opening scene, product focus, product dock and section navigation retained. New controls are separate from the global motion switch. Tablet package planes fit the actual opaque packaging bounds and leave the copy column clear. Mobile gallery edge peeks are deliberate carousel cues.
- Colors/tokens: supplied purple #7d4697, blush #e5afd1 and charcoal #3c3c3a retained. Dark photographic veil gives ivory text a quiet background. Mobile count/pagination have dark contrast backplates; focus outlines and selected indicators remain visible.
- Images/assets: five real photographs (hair care, hand washing, microscope research, water, outdoor walking), optimized local WebP, with author/source credit and a source manifest. No generated product or stock-photo imagery was introduced. The existing official product cutouts, logo and fonts were not modified. Stock photos are not described as the company's staff/facilities. All five images decoded successfully in the browser. Product-plane fit checked against actual packaging alpha bounds on all five tablet selections, including landscape Vitamin B1.
- Copy/content: each image, product selection, title, description, Latin name, indicator and product-detail dialog stay synchronized. No clinical, dosage, certification, award or sales claims were added. Existing official product links and demo-form notices remain intact.

## Functional verification

- All five slide selectors in all three demos: exactly one current picture and indicator; correct product selection; manual interaction stops rotation.
- Previous/next wrap from slide five to one and back; keyboard Home/End; arrow-key navigation on desktop and mobile viewport.
- Product CTA opens the matching Vitamin B1 dialog after selecting the fifth slide; dialog close restores normal page interaction.
- Product dock/gallery retain their existing product-selection handlers and synchronize the hero carousel.
- Mobile horizontal drag advanced from Sapo-Zinc to Bibacter through the same gesture handler used for touch swipe; mobile next/keyboard and menu open/close passed. Native physical touch scrolling was not separately emulated.
- Normal page load autoplay was observed advancing from index 0 to 4 while data-rotating remained true. Explicit reduction of motion, manual slide selection and header-home navigation were then checked in sequence: body motion paused, rotation paused, slide 0. A later read after more than one interval remained paused at slide 0.
- Rotation suspends while the hero is out of view, the page is hidden or the mouse is over the carousel; code includes these guards and respect for prefers-reduced-motion. No browser-level reduced-motion setting or physical touch device was used in this run.
- No app-origin console errors in the three demos. Chrome extension metadata errors were identified and excluded. Evidence: qa/slider-console.json.
- Detailed browser state evidence: qa/slider-browser-checks.json, qa/slider-mobile-content.json, qa/slider-responsive.json and qa/slider-tablet-fit.json.

## Comparison and fix history

1. P2, initial 390×844 layout: global motion toggle overlapped the carousel previous control. Moved the global control below the header at the opposite edge, added pagination/count contrast backplates and wider dot targets. Re-captured all three mobile views and checked geometry: controlOverlap false, overflow false. The combined mobile view was opened and inspected.
2. P2, initial portrait tablet: a tall narrow product plane cropped the Sapo-Zinc carton; the gallery side pack approached the text column. Tied plane height to viewport width, widened the center plane and moved the side planes inward. Re-captured the 834×1194 views; actual alpha-bound package fitting passed all five selections in all three demos. The revised gallery was opened and inspected with no text collision.
3. Stable capture readiness: font/dock readiness and paused visual state were used; source/implementation viewport and scroll state were normalized. Screenshot focus/scroll transitions were allowed to settle before the final captures.

No actionable P0/P1/P2 issue remains. The full-view and focused comparisons show the requested photographic slider behavior and the retained Saponine branding.

## Limits and follow-up polish

P3: mobile gallery side-product edge peeks are intentional; minor antialiasing differences from source advertising artwork. Figma retains the previously selected design baseline; this slider update is implemented in the code/live demos. No backend, form delivery, site access change, model scan, or new route was added.

## Implementation checklist

- [x] Five real-photo slides in each existing demo.
- [x] Product, text, photo, CTA and navigation synchronized.
- [x] Automatic/manual playback and keyboard/drag interactions checked.
- [x] Original logo, local IRANSans and official product cutouts preserved.
- [x] Desktop/mobile/tablet comparison complete; P2 findings fixed and re-captured.
- [x] No app-origin browser errors.

final result: passed
