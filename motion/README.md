# Saponine — Motion concept 02

Independent Persian RTL concept demo. Serve this directory as a static website.
Main scene uses native WebGL and locally generated 3D bottle/carton geometry.
Textures are created from the supplied Saponine logo. No external CDN dependency.

- Four scroll chapters move and rotate the scene, with a separated cap detail.
- Drag or use arrow keys on the product canvas to rotate the model.
- Three packaging palettes; collection dialogs; contact request copy tool.
- Reduced-motion support, local font files, mobile-specific composition.
- A Canvas 2D geometry renderer supports browsers without WebGL; a local poster is the final fallback.
- Static concept content. Product names and packaging are illustrative.
- No contact messages are transmitted or stored.

Files: index.html, style.css, scene.js, app.js and assets/.
Render Static Site: build command `true`, publish path `motion`.
This folder is a standalone HTML demo, not an installable WordPress theme.

Validation: JavaScript syntax and local asset/anchor checks passed. Desktop and a 384-pixel responsive iframe were inspected in the available browser; the Canvas 2D geometry renderer was exercised because WebGL was unavailable there. Native WebGL rendering and performance on physical phones were not benchmarked. Product dialogs, palette selection, mobile menu and contact request preparation were checked.
