# Client DS profile — Allure (agro)

Reference profile + the first real client. DS CSS: `clients/allure/allure-ds.css`.

## Target site

- Production: `https://allure.agr.br` (Elementor Pro **3.29.2**, container/Flexbox).
- **Staging / test**: Yuza Lab `https://aqua-antelope-484183.hostingersite.com` (Hostinger temp
  domain). Confirmed disposable, fully isolated from production — safe to break.
  Staging runs Elementor **core 4.1.4** + Pro 3.29.2 (host auto-updated core). Active Kit id 5.
- Auth: JWT ≤24h from the wordpress-mcp Authentication Tokens tab.

## Global colors → Kit slots

| Kit slot  | Token name    | Hex       | Usage |
|-----------|---------------|-----------|-------|
| primary   | Verde Allure  | `#32B61B` | brand green (green-500, corporate). NOT `#5AB82C` (that's green-400, the app/PWA green) |
| secondary | Verde escuro  | `#0A8F3C` | dark green (green-700) |
| text      | Ink           | `#1C1C21` | body/headings text |
| accent    | Menta         | `#69DD9A` | mint accent (green-300) |

Full ramp + neutrals (cream `#F6F4EF`, off-white `#FAF9F7`, sage, etc.) live in `allure-ds.css`.

## Typography → Kit

| Kit slot  | Font    | Weight | Source |
|-----------|---------|--------|--------|
| primary   | Aspekta | 700    | self-hosted (display/titles) |
| secondary | Roboto  | 400    | Google (body) |
| text      | Roboto  | 400    | Google |
| accent    | Aspekta | 700    | self-hosted |

- **Aspekta** = self-hosted, NOT on Google Fonts (github `ivodolenc/aspekta`). Files go in
  `wp-content/mu-plugins/`: prefer `AspektaVF.woff2` (variable, one file); the static
  `Aspekta-350.otf` + `Aspekta-650.otf` also work (bridge maps 350→300-400, 650→500-700).
- **Theme Style**: body = Roboto 400; H1–H6 = Aspekta 700; `body_color` → text token (ink).

## Logos / brand assets

- Site logo: `logo-preto.svg` (dark, for light backgrounds). On Yuza = attachment 2682.
- Variants: `logo-branco.svg` (light); icons `icon-verde.svg`, `icon-branco.svg`.
- Source: `~/Desktop/Projetos/Apresentações Allure/assets/`.

## DS reference

- DS CSS: `clients/allure/allure-ds.css` (display Aspekta, body Roboto, green `#32B61B`,
  ink `#1C1C21`; cream/sage/forest neutrals from OneSoil-style palette).
- The corporate brand green is `#32B61B`; `#5AB82C` is the app/PWA green — don't mix them up.
- Acervo of finished Elementor blocks: `github.com/luciano633/wordplates`.
