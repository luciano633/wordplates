# Phase 1 — Setup a WordPress for this pipeline

Goal: from a fresh WP to `GET /allure/v1/ping` returning `ok:true`.

## 1. MCP for WordPress

- Plugin: `automattic/wordpress-mcp` (installed via .zip from GitHub; archived but works).
  Settings to enable: **Enable MCP**, **Enable REST API CRUD Tools**.
- Auth: a **JWT** from the plugin's *Authentication Tokens* tab. Application Passwords are
  often disabled on managed hosts — use the JWT. **It expires in ≤24h** → regenerate and
  re-run `claude mcp add` when it lapses. Always generate the 24h token, not 1h.
- Configure the MCP (user scope) with:
  - `WP_API_URL = https://<site>/wp-json/wp/v2/wpmcp/streamable`
  - `JWT_TOKEN = <token>`
  - proxy: `npx @automattic/mcp-wordpress-remote@latest`
- **Gotcha:** a raw `curl` with the Bearer JWT returns `401 rest_forbidden`. The JWT is only
  accepted by the `/wpmcp/streamable` endpoint, which runs the REST call internally as
  user_id 1 (admin). So **always test capability through the `run_api_function` tool**, never
  curl directly. This is also why the bridge routes can use `manage_options` perms.
- The MCP only loads in a **new session** after `claude mcp add`.

## 2. The bridge mu-plugin

The MCP alone can't write Elementor payload. Upload `assets/allure-mcp-bridge.php` to:

```
wp-content/mu-plugins/allure-mcp-bridge.php
```

**Must be directly in `mu-plugins/`** (auto-loads, no activation) — NOT `wp-content/plugins/`
(would need manual activation) and NOT a subfolder (mu-plugins don't recurse). If the route
404s, see gotchas.

Self-hosted font files (e.g. `Aspekta-*.otf` or `AspektaVF.woff2`) go in the **same**
`mu-plugins/` folder — the bridge serves them and prints the `@font-face`.

## 3. Verify

```
run_api_function  GET /allure/v1/ping
```
Expect: `{ ok:true, bridge:"x.y.z", elementor, elementor_pro, active_kit, aspekta:{...} }`.

If `rest_no_route` 404 → bridge not loaded. Sanity check namespaces:
```
run_api_function  GET /            → look for "allure/v1" in the namespaces list
```
(The full `/` response is huge; grep the saved tool-result file for `allure/v1`.)

## Quick capability map (what works once the bridge is up)

- Read globals: `GET /elementor/v1/globals/colors` · `/globals/typography`
- Read library/posts: `GET /wp/v2/elementor_library` · `/wp/v2/<post_type>`
- Write Elementor layout: `POST /allure/v1/import-block` (✅ via bridge)
- Write Kit colors/fonts: `POST /allure/v1/set-global-colors` · `/set-global-typography` (✅)
- Read back: `GET /allure/v1/block/<id>` · `/kit-settings`
