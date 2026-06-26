# Phase 4 — Translate to Elementor blocks & pages

Convert each designed section into Elementor JSON (schema `version: 0.4`, container/Flexbox),
import via the bridge, verify, assemble.

## JSON shape

`content` is an array of top-level elements (each section = one container). Import body:
```
POST /allure/v1/import-block
{ "title": "...", "type": "container", "content": [ <containers...> ],
  "page_settings": [], "id"?: <update existing>, "post_type"?: "elementor_library" | "page" }
```
- `post_type: "elementor_library"` (default) → reusable Saved Template (does NOT render at a
  public URL on its own).
- `post_type: "page"` → a real, public page (`_elementor_template_type` becomes `wp-page`).
  Returns `view_url`. **This is how full pages are built** — a page is just a post with the
  3 Elementor metas; the bridge writes them on any post_type.
- Pass `id` to update in place (great for design iteration on a preview page).

## Element skeleton

```json
{ "id": "8hexchars", "elType": "container", "isInner": false,
  "settings": { ... }, "elements": [ ... ] }
```
- `elType`: `container` or `widget`. Widgets add `"widgetType": "..."`.
- `id`: unique 8-char-ish hex, unique across the whole document (no collisions when
  concatenating blocks — use distinct prefixes per block).
- `isInner`: `true` for a nested (inner) container.

## Container settings (common)

- `content_width`: `"boxed"` | `"full"`. For a full-bleed background with centered content,
  use a `full` outer container (with `background_*`) wrapping a `boxed` inner container.
- Flexbox: `flex_direction` (`row`/`column`), `flex_wrap` (`wrap`), `flex_gap`
  (`{unit,size,column,row}`), `flex_align_items`.
- Flex item (inner): `_flex_grow`, plus `width` (acts as flex-basis; px width + grow ⇒
  responsive wrap).
- Background: `background_background:"classic"`, `background_color:"#..."`.
- Spacing: `padding`/`_margin` = `{unit:"px",top,right,bottom,left,isLinked}`.

## Widget patterns (verified working)

- **heading**: `title`, `header_size` (`h1`..`h6`), `align`. Typography override (group prefix
  `typography_`): `typography_typography:"custom"`, `typography_font_family`,
  `typography_font_weight`, `typography_font_size:{unit,size}` (+ `_mobile`/`_tablet`),
  `typography_line_height`.
- **text-editor**: `editor` (HTML string). Inline `<span style="color:#...">` works for
  two-tone text.
- **image**: `image:{url,id}` (external URL ok with empty id), `image_size:"full"`,
  `align`, `width:{unit,size}`.
- **icon**: `selected_icon:{value:"fas fa-x",library:"fa-solid"}`, `view:"framed"|"stacked"`,
  `shape:"circle"`, `primary_color`, `size:{unit,size}`.
- **button**: `text`, `align`, `link:{url}`.
- **accordion** (FAQ): `tabs: [ {_id, tab_title, tab_content:"<p>..</p>"}, ... ]`. Note: on
  Elementor 4.x the classic `accordion` may be legacy (V4 has `nested-accordion`) — data is
  fine, check render.
- **form** (Pro): `form_name`, `form_fields: [ {_id, field_type:"text|email|tel", field_label,
  placeholder, required:"true"|"false", width:"100"|"50", custom_id} ]`, `button_text`,
  `button_size`.

## Referencing Global tokens (the DS link)

Bind a control to a token via the element's `__globals__` map (key = control name):
```json
"settings": {
  "__globals__": {
    "title_color": "globals/colors?id=primary",
    "button_background_color": "globals/colors?id=primary",
    "primary_color": "globals/colors?id=primary"   // icon color
  }
}
```
Color ids: `primary|secondary|text|accent` (+ custom slug hashes). Prefer globals over literals
so re-theming is one edit in the Kit.

## Verify & assemble

- Read back: `GET /allure/v1/block/<id>` → returns `data_len`, `el_version`, and decoded
  `data`. Confirm widgets + `__globals__` survived.
- Public render check: for `post_type: page`, fetch the `view_url` and grep for the expected
  text/markup. (Library singles don't render — make a preview *page* to see a block.)
- Assemble a page: concatenate each section's container into one `content` array (order =
  page order), `post_type:"page"`. IDs must not collide across blocks.

## Iteration loop (cheap)

Create a small preview **page** with just the section you're refining; re-import with its `id`
to update in place; refetch/screenshot. Promote to the acervo + final page once approved.
Keep block JSONs versioned (e.g. the `wordplates` acervo repo).
