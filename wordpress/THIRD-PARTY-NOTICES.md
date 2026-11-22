# Sources and licenses

## Project code

Saponine Flex and Saponine Studio are provided as readable PHP, CSS, JavaScript and JSON under GPL-2.0-or-later. The GPL text is in LICENSE.txt. No activation key or encoded PHP is required.

## Design and supplied images

The conversion uses the user's Saponine design and images from the public repository:

- https://github.com/alimoradii/saponine-client-demo
- Saved design commit: `2bb7531bdc5321434c88c354abc2aa146d52341b`
- Product motion reference: `render-lovable-adrian-product/render-proxy/adrian.css` and `adrian.js`
- Original published reference: https://saponin-motion-showcase.lovable.app/

Original assets were copied locally: logo, hero photograph, three product photographs and favicon. The existing demonstration product descriptions were preserved. No live Lovable proxy, tracking scripts, promotional badge, or original React bundle is included.

The original image files remain subject to their respective owners' rights. The code license does not grant additional rights to third-party branding or photographs.

## Fonts

- Vazirmatn: https://github.com/rastikerdar/vazirmatn — SIL Open Font License. See `saponine-studio/assets/fonts/Vazirmatn-OFL.txt`.
- Outfit: https://github.com/google/fonts/tree/main/ofl/outfit — SIL Open Font License. See `saponine-studio/assets/fonts/Outfit-OFL.txt`.

Both font files are bundled locally. Online Google Fonts requests are disabled by default for Saponine Flex; the Studio settings contain an explicit opt-in.

## Runtime dependencies

WordPress and Elementor Free are installed separately from their official distributions. They are not redistributed in this package.

- https://wordpress.org/
- https://wordpress.org/plugins/elementor/

The design uses classic Elementor containers and widgets, with Elementor's documented custom-widget extension API. It does not require Elementor Pro.

The multilingual integration uses **Polylang Free**, installed separately from https://wordpress.org/plugins/polylang/. Polylang is not redistributed in this archive. Its APIs manage language routing, translation relationships, and language links. Manual multilingual editing does not require Polylang Pro or translation-service credentials.

Version 1.2.0 adds an optional administrator-started integration with **Google Cloud Translation — Basic v2**. It requires an API key for a Google Cloud project with Cloud Translation API and billing enabled. Usage is subject to that project's service pricing and quotas. The key is distinct from any theme or plugin license: no Elementor Pro or Polylang Pro license is required. No Google API key is bundled, and a live translation call on the user's account has not been verified in this delivery.

Only supported content text is sent to the translation endpoint. The implementation retains the original markup, URLs, images, layout and animation settings on the WordPress server. Translated content is stored in editable Elementor documents; normal visits to already translated pages do not call Google for each visitor.

## Footer credit

The user's requested source-language credit is an editable native Elementor heading:

`طراحی،توسعه و پشتیبانی با هلدینگ کلاسیکا`

Its complete text links to https://alimoradicoach.ir/. A matching fallback appears only when an Elementor footer is not being rendered. The optional `footer-credit.json` block is included for insertion into previously saved footers.

## Developer references

- Elementor data structure: https://developers.elementor.com/docs/data-structure/general-structure/
- Elementor widget API: https://developers.elementor.com/docs/widgets/widget-structure/
- Elementor hooks: https://developers.elementor.com/docs/hooks/
- WordPress plugin handbook: https://developer.wordpress.org/plugins/
- Polylang public APIs: https://polylang.pro/documentation/support/developers/function-reference/
- Polylang filters and custom post types: https://polylang.pro/documentation/support/developers/filter-reference/
- Google Cloud Translation setup and billing requirements: https://docs.cloud.google.com/translate/docs/setup
- Google Cloud Translation pricing and quotas: https://cloud.google.com/translate/pricing
- Google Cloud Translation Basic API: https://docs.cloud.google.com/translate/docs/reference/rest/v2/translate
- Wheel event behavior: https://developer.mozilla.org/en-US/docs/Web/API/Element/wheel_event

## Province map and editorial photos in 1.4

Iranian province outlines are derived from Natural Earth Admin 1 (public domain). Source: https://www.naturalearthdata.com/downloads/10m-cultural-vectors/10m-admin-1-states-provinces/ . The example street illustration is schematic. The about and pharmacy editorial photographs were generated for this design. Product photographs, logos and local typefaces are from the supplied Saponine package.
