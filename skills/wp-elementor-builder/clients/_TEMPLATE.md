# Client DS profile — <CLIENT NAME>

Copy this to `clients/<name>/ds.md` and fill it. This is the source of truth for phases 1–4.
Use `extract-design-system` / `theme-factory` to derive values from the brand's references.

## Target site

- Production URL: `https://...`
- **Staging / test URL** (where we write — must be non-production): `https://...`
- WP admin user / MCP JWT source: Authentication Tokens tab (JWT ≤24h)
- Notes (host, caching, Elementor versions core/pro):

## Global colors → Kit slots

| Kit slot   | Token name | Hex      | Usage |
|------------|------------|----------|-------|
| primary    |            | #        |       |
| secondary  |            | #        |       |
| text       |            | #        |       |
| accent     |            | #        |       |

Custom colors (optional, keep alongside system): `{_id,title,color}` …

## Typography → Kit

| Kit slot   | Font family | Weight | Google or self-hosted? |
|------------|-------------|--------|------------------------|
| primary    |             |        |                        |
| secondary  |             |        |                        |
| text       |             |        |                        |
| accent     |             |        |                        |

- Self-hosted fonts: file(s) + where to get them (woff2 variable preferred). They go in
  `wp-content/mu-plugins/`; the bridge registers them + prints `@font-face`.
- Theme Style: body font = ?, H1–H6 font = ?, body color token = ?

## Logos / brand assets

- Primary logo (site logo): file / URL
- Variants (light/dark), icon/mark: …
- Source folder:

## DS reference

- DS CSS file in this profile: `clients/<name>/<name>-ds.css` (tokens + components)
- Design references (Figma, live site, screenshots):
- Voice/tone, do/don't:
