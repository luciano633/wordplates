---
name: wp-elementor-builder
description: >-
  Build WordPress + Elementor pages for a given client, design-first. The workflow: pick the
  client and map their design system (or create a profile for a new one), configure a fresh WP
  for programmatic editing (MCP + the bridge mu-plugin), apply that DS to the Elementor Kit
  (global colors, typography incl. self-hosted fonts, logos), design each screen in HTML/CSS
  using the same DS, then translate each section into native Elementor blocks and import via
  the bridge — assembling real pages. Use when: onboarding a client's brand into WordPress,
  setting up a new WP for this pipeline, applying a design system to Elementor, designing
  landing pages, or generating/importing Elementor blocks and pages programmatically.
---

# WP + Elementor builder — design-first, per client

A **method + setup playbook**, client-agnostic. No frozen block templates — each block is
crafted per project. What this gives you: the pipeline that makes that fast and correct, the
hard-won gotchas, and a per-client DS profile system so the same machine serves any brand.

## Core idea

The MCP for WordPress does post CRUD but **cannot write Elementor's payload** (`_elementor_data`
and the Kit's global colors/fonts are protected meta). A small mu-plugin **bridge**
(`assets/allure-mcp-bridge.php`) exposes REST routes that write those metas server-side (as
admin), regenerate CSS, and read them back. With the bridge installed, you can fully build
Elementor blocks, the Kit (the DS), and whole pages over REST.

Keep two design layers separate:
- **Tokens** (color, typography) → the Elementor **Kit** (global). One source of truth.
- **Layout / spacing / component styling** → in the **block** (container + widget settings),
  NOT free CSS. Designing in HTML first locks the visual spec; translating it keeps to
  *native widget settings* so non-technical editors can still edit safely.

## Phase 0 — Define the client & map the DS

Everything downstream is parameterized by the **client profile** in `clients/<name>/`.

- **Existing client** (e.g. `clients/allure/`): read `clients/<name>/ds.md` (+ its CSS) and use
  those tokens, fonts, logos, and the target/staging notes.
- **New client:** copy `clients/_TEMPLATE.md` to `clients/<name>/ds.md` and fill it by mapping
  the brand's DS — colors → the 4 Kit slots (primary/secondary/text/accent) + customs, fonts
  (which are Google vs self-hosted), logos, target site/staging. Compose with
  `extract-design-system` (derive a DS from the client's references/site) and `theme-factory`
  (generate/round-out palettes), then capture the result into the profile.

Output: a filled `clients/<name>/ds.md` = the source of truth for phases 1–4.

## Phases 1–4

1. **Setup the WP** → `reference/01-setup-wp.md` — MCP (`wordpress-mcp`, JWT ≤24h, streamable
   endpoint), upload the bridge mu-plugin, confirm `GET /allure/v1/ping`.
2. **Define the design system in the Kit** → `reference/02-define-ds.md` — write the client's
   colors + typography (always **merging**), register self-hosted fonts, upload logos / set the
   site logo, apply Theme Style so tokens actually render.
3. **Design screens in HTML** → `reference/03-design-html.md` — prototype each screen with the
   client's DS CSS, sliced into block-sized sections thought through in Elementor's grain.
   Iterate in a headless browser. **Compose with design/marketing skills here.**
4. **Translate to Elementor** → `reference/04-translate-elementor.md` — convert each section to
   Elementor JSON (schema 0.4, container/widget), reference Kit globals, import via the bridge,
   verify, assemble into pages.

Always read `reference/gotchas.md` before touching the server.

## Skills that compose

This skill is the **orchestrator**. Invoke these at the right phase (must be installed locally;
review third-party instructions before trusting; load on demand, not all at once):

- **Phase 0/2 — mapping a brand's DS:** `extract-design-system` (derive DS from references),
  `theme-factory` (generate palettes).
- **Phase 3 — design HTML:** `frontend-design` (visual direction, hierarchy, type),
  `web-design-guidelines` (UI best practices).
- **Phase 3+ — landing pages that convert:** `page-cro`, `copywriting`/`copy-editing`,
  `form-cro`/`popup-cro`, `marketing-psychology`.
- **Post-build — technical:** `seo-audit`, `schema-markup`.

## Bundled

- `assets/allure-mcp-bridge.php` — the bridge mu-plugin (v0.6.0; generic infra — the REST
  namespace `allure/v1` is just the route prefix, not client-specific). Routes: `ping`,
  `import-block` (blocks AND pages via `post_type`), `block/<id>`, `set-global-colors`,
  `set-global-typography`, `kit-settings`, `set-kit-settings`, `import-media`.
- `clients/<name>/` — per-client DS profile (`ds.md` + CSS + font/logo assets). `clients/allure/`
  is the reference profile; `clients/_TEMPLATE.md` is the blank to clone for a new client.

## Environment notes

- Confirm any write target is **non-production** before touching it (use staging).
- Elementor target schema: 3.29.2 / container (Flexbox), JSON `version: 0.4`. Core 4.x still
  imports 0.4, but validate render.
- The MCP JWT expires ≤24h — regenerate and re-run `claude mcp add`.
