# Design QA · Saponine professional demos

final result: passed

Reviewed: 2026-11-02. Scope: the three runnable web demos. The later user-supplied logo and corporate palette intentionally supersede the original green identity in the selected concept images.

## Visual truth and rendered evidence

| Demo | Selected visual truth | Browser-rendered evidence | Comparison |
| --- | --- | --- | --- |
| source | /workspace/scratch/ea4fed638cd0/generated_images/exec-3744528b-a73e-4483-8c93-acfe644ba52d.png | qa/source-final-desktop.webp | qa/source-final-comparison.jpg |
| motion | /workspace/scratch/ea4fed638cd0/generated_images/exec-31539a34-4c24-4ffe-877b-031f8c01670b.png | qa/motion-final-desktop.webp | qa/motion-final-comparison.jpg |
| reference | /workspace/scratch/ea4fed638cd0/generated_images/exec-3a5bbe45-ab7f-4a26-b4b5-6aa428527f51.png | qa/reference-final-desktop.webp | qa/reference-final-comparison.jpg |

Source concepts are 1487×1058 pixels. Desktop web content was rendered at 1487×1058 CSS pixels, devicePixelRatio 1, in the cloud browser. A QA iframe was displayed at scale 0.85 inside the 1363×936 browser viewport; comparison crops are 1263×899 pixels. Source images were normalized to the same size before full-view and focused comparison. No browser chrome or surrounding QA canvas is part of the compared UI.

Responsive QA: 390×844 CSS pixels at scale 1; 834×1194 CSS pixels at scale 0.73. Browser screenshots and DOM measurements confirmed the intended viewport, loaded IRANSans and zero document-level horizontal overflow for all nine demo/viewport combinations. This tests responsive layout in Chromium, not physical mobile devices or Safari.

Default states: Sapo-Zinc selected in source and reference; Bibacter selected in motion; no dialog open. Ready-state captures wait for the product dock and font readiness.

Focused evidence: qa/source-typography-focus.jpg and qa/motion-typography-focus.jpg. The original logo file was inspected directly and its unchanged bytes verified by SHA-256. Product labels and photo subjects were checked against the existing official cutouts, which were preserved unchanged.

## Required fidelity surfaces

- Fonts/typography: local IRANSans Regular and Bold loaded in all three demos. Persian RTL, headings, wrapping, button labels, body sizes and keyboard focus were inspected. Arial is used only for the optional Latin watermark and metadata. Runtime typography intentionally uses the actual supplied font rather than approximating the image-generation lettering.
- Spacing/layout: floating header, full-bleed scene, product/copy hierarchy, compact floating product selector, section rail and scroll cue retained. Gallery CTA, product captions and dock have separate space. Portrait tablet photo widths were adjusted to keep the actual package visible.
- Colors/tokens: official supplied logo, charcoal #3c3c3a, purple #7d4697, blush #e5afd1, paper #fcf9fc and deep plum #2d2136. This is the explicit later user override. Checked contrast pairs: charcoal/paper 10.58:1; white/purple 6.57:1; body/paper 5.41:1; partner copy/surface 4.71:1; ivory/plum 14.64:1. Mobile motion background uses the quiet dark side of the scene behind text.
- Images/assets: real official product cutouts and original supplied logo; transparent product backgrounds; generated scene backgrounds are separate image assets. Product labels and packaging colors were not changed for the new corporate palette. Feather icons are official SVG assets with the MIT license retained. No CSS art or fabricated product packages are used in active pages.
- Copy/content: real product names, package forms and official source links retained. No invented certification, clinical claim, award or sales statistic. The cooperation form visibly prepares text without sending or storing information.

## Functional and accessibility checks

- Desktop and mobile navigation, menu open/close by pointer and Enter, and section links.
- Product category filters: shampoo produces two results; gel produces one.
- Search for Bibacter, opening its product dialog, packaging tab and official product link; zero-result query has a readable empty state.
- Gallery side-product selection and center-product details; mobile dock selection of Permethrin and matching dialog/source link.
- Previous/next spotlight product controls and persistent selected states.
- Cooperation form validation and preparation of a request using dummy data; no message sent or stored.
- Motion toggle changes aria-pressed, visible control label and image transform state. Reduced-motion media query and root scrolling behavior are implemented.
- Product-plane perspective responds to scroll/pointer; the motion demo has a sticky three-chapter sequence. These are photo planes, not claimed volumetric scans.
- App-origin browser console errors: none. Browser-extension metadata logs were identified separately and are not site errors.
- JavaScript syntax checks, unique HTML IDs, internal anchor targets, local asset references, original logo hash and unchanged product-photo bytes passed.

## Comparison and fix history

1. P2: gallery CTA and central pack/caption competed for space. Adjusted gallery bounds to leave CTA clearance and keep labels above the dock. Re-captured and compared.
2. P2: title/dock scale was too small relative to the selected hierarchy. Increased desktop heading size, widened the dock and improved action label sizes. Re-captured full and focused regions.
3. P1: negative-depth side products lost pointer hits to their stage. Set stage pointer-events to none and product buttons to auto. Confirmed selecting Bibacter changes the active product and matching spotlight content.
4. P1: tapping the mobile menu icon replaced the event target before the outside-click handler ran. Used the original composed event path and non-interactive decorative icons. Pointer and keyboard menu tests now pass; navigation closes the menu correctly.
5. P2: the mobile motion scene placed a bright ribbon behind ivory copy. Cropped to the quiet dark side on mobile; re-captured the readable state.
6. P2: portrait tablet photo containers clipped outer transparent/package bounds. Widened the source stage and gallery center item; inspected corrected tablet captures.
7. A capture taken before font/dock readiness showed transient empty chrome. The final evidence uses explicit ready-state waits. Runtime labels and all five selectors were confirmed in the DOM and on screen.

8. P2: the landscape Vitamin B1 package needs a different crop from portrait bottles. Added contain fitting for its catalog card and gallery image so the full original package remains visible.

No actionable P0/P1/P2 findings remain in the web demos.

## Figma companion

https://www.figma.com/design/XoysqwWyrB460D6e7KmjFg

Final screen nodes: 13:2, 13:61, 13:120. Each contains native text, separate photographs/logo, editable vector icons and a product-dock instance; no flattened full-UI raster is used. CSS-mask placeholders were replaced with Feather SVG vectors. Repeated-header rendering and RTL start-alignment capture issues were fixed and the resulting screenshots inspected.

Font family metadata remains IRANSans. The current Figma tool session cannot load that local font for text editing; installing/making the font available in Figma is required for subsequent text changes. This does not affect the three websites, where the actual WOFF files load and render.

## Follow-up polish and limits

P3: small rendering differences from generated concept lettering; deliberate mobile carousel edge peeks; no physical-device/Safari run. Full clinical content and production form delivery remain outside these frontend demos.

## Implementation checklist

- [x] Three distinct selected layouts implemented.
- [x] Supplied official logo and corporate colors applied to all three.
- [x] Actual product photos and IRANSans preserved.
- [x] Desktop/mobile/tablet layouts inspected.
- [x] Primary interaction paths tested and observed defects fixed.
- [x] Full-view and focused visual comparison completed.
- [x] Browser console and static checks completed.

final result: passed
