# Phase 3 — Design screens in HTML (DS-first)

Why HTML first: Elementor JSON is a terrible medium to *design* in (no visual feedback while
writing). Prototype in HTML/CSS using the same design system, lock the visual, then translate.
This is where the result stops looking like a generic template.

## Setup

- Work in a `prototype/` folder in the project (e.g. `~/Desktop/Projetos/Sites/prototype/`).
- Link the **active client's DS CSS** (`clients/<name>/<name>-ds.css`) and use its tokens/classes
  for everything — colors, type scale, spacing. Don't invent values; pull from the DS.
- Use real assets (logos, app/dashboard screenshots, photos) so spacing/scale is honest.
- Iterate fast in a **headless browser (Chrome via CDP)** — render, screenshot, adjust.
  (Note: `headless=new` can lie about some rendering; verify the layout that matters.)

## Design in Elementor's grain (so translation is clean)

HTML → Elementor is **not** an automatic 1:1 port. Styling in Elementor lives in *widget
settings*, not free CSS. So design with these constraints, or you'll be forced into
per-element custom CSS (Pro) and break the "non-technical editor" principle:

- **Each page section = one Elementor container.** Plan the page as a vertical stack of
  sections (hero, feature grid, image+text, FAQ, CTA…).
- **Each piece = a widget that actually exists:** heading, text-editor, image, icon, button,
  icon-list, accordion (FAQ), form (Pro), video, etc. If your design needs something with no
  widget equivalent, rethink it or accept it'll need custom CSS.
- **Layout = flex containers:** row/column, gap, wrap, align, width%. Cards = inner containers
  with `flex-grow` + a px width acting as flex-basis (so they wrap responsively).
- **Tokens, not literals:** map every color to a Global Color and every font to a Global Font,
  so the translated block references `globals/...` instead of hardcoding. Backgrounds, radius,
  shadow, padding are container settings — note their exact values in the prototype.
- Keep a short **spec note per section**: container settings + each widget + which globals it
  binds. That note is the translation brief for phase 4.

## Composing skills here

- `frontend-design` — visual direction, hierarchy, type pairing/scale.
- `web-design-guidelines` — UI best practices, spacing/contrast/accessibility.
- For landing pages that convert: `page-cro` (section order & conversion structure),
  `copywriting`/`copy-editing` (section copy), `form-cro`/`popup-cro`, `marketing-psychology`.

Output of this phase: an approved HTML prototype + per-section spec notes → feed phase 4.
