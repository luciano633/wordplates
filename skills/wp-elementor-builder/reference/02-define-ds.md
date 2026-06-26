# Phase 2 — Define the design system (Elementor Kit)

Tokens are the single source of truth and live in the active **Kit**. Everything here goes
through the bridge, which calls `$kit->save()` and regenerates global CSS.

## ⚠️ The merge rule (critical)

`$kit->save(['settings' => ...])` **replaces the entire settings document** — it does NOT
merge. Writing typography would wipe colors, etc. The bridge's write routes use
`allure_kit_save_merged()` which reads `_elementor_page_settings`, `array_merge`s at the top
level, and saves the whole thing. **When replicating the bridge or writing the Kit any other
way, always merge.** Also: sending only `system_colors` (without `custom_colors`) drops the
custom colors — send both if you want to keep them.

## Global colors

```
POST /allure/v1/set-global-colors
{ "system_colors": [ {"_id":"primary","title":"...","color":"#RRGGBB"}, ... ],
  "custom_colors": [ ... ]   // optional; include to preserve existing customs
}
```
System ids: `primary | secondary | text | accent`. Verify with `GET /elementor/v1/globals/colors`.

Values come from the **active client profile** (`clients/<name>/ds.md`). Example (Allure):
primary `#32B61B`, secondary `#0A8F3C`, text `#1C1C21`, accent `#69DD9A`.

## Global typography + self-hosted font

```
POST /allure/v1/set-global-typography
{ "system_typography": [
    {"_id":"primary","title":"...","typography_typography":"custom",
     "typography_font_family":"Aspekta","typography_font_weight":"700"}, ... ] }
```
ids: `primary | secondary | text | accent`. Verify with `GET /elementor/v1/globals/typography`.

Per the client profile. Example (Allure): primary/accent = **Aspekta** (self-hosted display),
secondary/text = **Roboto** (Google body — Elementor loads Google fonts natively).

**Self-hosted fonts:** Google fonts need nothing. A self-hosted font (e.g. Allure's Aspekta) —
the bridge handles it: filters `elementor/fonts/groups` (adds a font group) and
`elementor/fonts/additional_fonts` make it selectable, and it prints `@font-face` on `wp_head`
+ `elementor/editor/wp_head`. Drop the font file(s) into `wp-content/mu-plugins/`:
- `AspektaVF.woff2` (variable, preferred — one file, all weights), or
- `Aspekta-350.otf` + `Aspekta-650.otf` (static; bridge maps 350→300-400, 650→500-700).
`ping` reports which are present (`aspekta:{woff2_variable,otf_350,otf_650}`). For production,
prefer woff2 (OTF is heavier).

## Theme Style (make the tokens actually render site-wide)

Tokens existing ≠ applied. To make all text/headings adopt the DS, write Theme Style keys to
the Kit:
```
POST /allure/v1/set-kit-settings
{ "settings": {
    "body_typography_typography":"custom","body_typography_font_family":"Roboto","body_typography_font_weight":"400",
    "h1_typography_typography":"custom","h1_typography_font_family":"Aspekta","h1_typography_font_weight":"700",
    "... h2..h6 the same ...",
    "__globals__": { "body_color":"globals/colors?id=text" }
} }
```
Verify everything coexists with `GET /allure/v1/kit-settings`.

## Logos / media

```
POST /allure/v1/import-media
{ "filename":"logo.svg", "b64":"<base64>", "set_site_logo":true }
```
Decodes base64 → uploads → attachment + metadata; `set_site_logo` sets `custom_logo` theme_mod.
SVG is enabled + lightly sanitized by the bridge (`upload_mimes` + filetype filter).

**Practical:** base64 through the MCP bloats context badly (a ~6KB SVG ≈ 8KB base64). For
media, it's faster to upload files directly via WP Admin → Media (SVG already enabled by the
bridge), or pass an external URL straight into an image widget's `image.url`. Use
`import-media` only when programmatic upload is truly needed.
