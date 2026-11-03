# Adrian structure adaptation QA · 2026-11-03

final result: passed

Scope: the latest request replaces the earlier three visual directions with one shared Adrian Salamat homepage structure, retaining supplied Saponine identity, IRANSans and genuine products. This reviews that brand adaptation, not identical campaign raster artwork or Adrian company claims.

## Findings and iteration history

1. P1, fixed — clipped desktop panels after focus. Initial feature capture showed content 400px too high because focus scrolled the overflow-hidden transformed main element. Desktop main and hero use overflow:clip. Subsequent navigation, cycling, keyboard and wheel report main.scrollTop=0 and active-panel y=0. Evidence: saponine-exact-features-v1.jpg before, saponine-exact-features-final.jpg after.
2. P2, fixed — feature alignment. Matched source icon/row order, flex content and inward text alignment; corrected mobile stacking and top spacing. Final full and focused comparisons show the four-icon arrangement and source-sized circles. Evidence: exact-compare-features.jpg, exact-compare-focused.jpg.
3. P2, fixed — vertical rhythm. Raised general panel content by 16px, aligned product text/facts, preserved description space, and moved footer down 30px. Source and implementation use 90% content width, five cards with 20px gaps, right-hand 510px product ring and two pill photos. Evidence: final brands/products/pharmacy/footer comparisons.
4. P2, fixed — mobile decorative text overflow. Long background text caused 405px document width in a 375px content viewport. Reduced its responsive size and clipped its own decorative container. Final clientWidth and scrollWidth both 375px, tablet both 819px. Evidence: exact-browser-checks.json and mobile-home comparison.
5. P2, fixed — product wrap and landscape thumbnails. Normalized all five ring slots, made offscreen controls inert, used contain for Vitamin B1. A full next cycle and mobile/tablet selections passed.
6. P2, fixed — explicit playback under reduced motion. Play now explicitly re-enables the selected animation mode before rotation. Play/pause state and matching slide were verified.

No actionable P0/P1/P2 issue remains within the stated Saponine adaptation scope.

## Visual truth and evidence

Reference: https://adriansalamat.com/, opened and interacted with in Chromium. Implementation: http://terminal.local:4173/source/ and identical emitted motion/reference folders. All screenshot and comparison paths below are relative to /workspace/scratch/. These local QA images are excluded from the public source update.

Desktop source/implementation viewport and pixel dimensions: 1363×936, devicePixelRatio 1. No original-density resampling. Full comparisons concatenate equal-size captures with a 30px label band. Selection is held for readable captures. Source campaign content and Saponine content intentionally differ.

| Surface | Source image | Implementation image | Combined input |
| --- | --- | --- | --- |
| Hero | adrian-exact-home-normalized.jpg | saponine-exact-home-final.jpg | exact-compare-home.jpg |
| Features | adrian-exact-features-fixed.jpg | saponine-exact-features-final.jpg | exact-compare-features.jpg |
| Brands | adrian-exact-brands.jpg | saponine-exact-brands-final.jpg | exact-compare-brands.jpg |
| Products | adrian-exact-products.jpg | saponine-exact-products-final.jpg | exact-compare-products.jpg |
| Pharmacy | adrian-exact-pharmacy.jpg | saponine-exact-pharmacy-final.jpg | exact-compare-pharmacy.jpg |
| Footer | adrian-exact-footer.jpg | saponine-exact-footer-final.jpg | exact-compare-footer.jpg |

All six combined inputs were opened and reviewed. exact-compare-focused.jpg compares original-resolution header and feature text/icons for font weight, alignment and control spacing.

The source desktop about capture remains at its photo-ribbon stage; exact-compare-about.jpg compares that stage with settled implementation content and is not evidence of pixel-identical settled states. Source mobile exposes the actual text layout. exact-compare-mobile-about.jpg compares that content at explicitly different scroll positions. The implementation retains a photo-strip entrance before the introduction/company image; its gallery was exercised.

Mobile frames are 390×844 CSS pixels, scale 1; equal content-only crops use those bounds. Implementation has a 15px desktop scrollbar, leaving 375px content; source scrollbar treatment differs. exact-compare-mobile-home.jpg compares the top layout; exact-compare-mobile-footer.jpg compares the final footer with differing preceding content at each bottom position, so no pixel-exact vertical alignment is inferred. Tablet viewport is 834×1194 at .72 preview scale. saponine-exact-tablet-home-final.jpg and saponine-exact-tablet-products-final.jpg were opened and reviewed. Tests use Chromium responsive frames, not physical iOS/Safari.

## Required fidelity surfaces

- Fonts/typography: genuine supplied IRANSans Regular/Bold load locally. Source Latin Baskervville and icon font are local. Source Persian font differs intentionally per user requirement. Headings, weight, RTL wrapping, metadata and mobile text reviewed in full and focused comparisons.
- Spacing/layout: floating 90% header at top 24px, height 60px; full-screen panels, left rail, bottom dock, central feature product, cards, product ring, pill images and rounded footer. Mobile preserves the section stack without horizontal overflow.
- Colors/tokens: #7d4697, #e5afd1, #3c3c3a, white and pale lilac replace source blue/green/yellow. Logo bytes remain intact; CSS trims outer whitespace. Packaging retains original colors.
- Images/assets: actual source feature SVGs, ring SVGs, arrows, medallion and decorative CSS are recorded in the reference manifest. Existing Saponine cutouts, company image, logo and real photographic backgrounds remain unchanged. No generated products or invented brand logos. Campaign images use Saponine content. Five hero photographs and selected products decoded; landscape B1 fits its bounds.
- Copy/content: history and contact facts derive from https://saponineco.com/. No Adrian company medical claims, staff identities or pharmacy addresses relabelled as Saponine. Sales dialog discloses missing verified directory. Introduction is a photo gallery; the request form clearly prepares text without sending or storing it.

## Functional verification

- Five hero selectors: matching title, photograph, current dot and product; manual selection pauses rotation. Autoplay advance and explicit play/pause observed. Keyboard and horizontal pointer gesture supported.
- Seven section rail links, Home key and wheel transition; no focus-induced main scroll. Hash navigation and reduced-motion support retained.
- Complete five-product next cycle wraps with one selected item; matching official detail link. Mobile and tablet next-product/detail actions passed.
- Search Sapo-Zinc, nonexistent-query empty state and shampoo filter with two results passed.
- Mobile menu opens and closes on navigation; persistent header and no horizontal overflow.
- Gallery opens, selects a loaded photo and closes; FAQ expands; sales dialog has verified telephone link. Cooperation form exercised using synthetic test-only text without transmission.
- Zero app-origin browser console errors. Chrome extension metadata errors were excluded.
- Syntax, local references, duplicate IDs, shared emitted files and original logo/font/product hashes checked before publication.

Observations: qa/exact-browser-checks.json. Earlier slider reports are historical.

## Intentional differences and remaining limits

Brand, content, fonts and genuine product-photo substitutions are expected. Hero campaign artwork is not identical. Photos float in CSS perspective; no scanned 3D model exists. No supplied Saponine video, pharmacy database or backend form delivery; UI accurately exposes gallery/contact/draft functionality. Figma still contains previous three directions and was not updated. Physical touch devices and Safari remain untested. Minor anti-aliasing and supplied-font metrics are acceptable P3 differences.

## Implementation checklist

- [x] Shared reference structure on all three existing demos.
- [x] Supplied logo, IRANSans and real product photography retained.
- [x] Full/focused comparisons opened; P1/P2 findings fixed and recaptured.
- [x] Desktop/mobile/tablet layout and primary controls checked.
- [x] Accurate content boundaries and asset sources documented.

final result: passed
