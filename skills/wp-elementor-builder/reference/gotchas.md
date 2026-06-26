# Gotchas (read before touching the server)

These each cost real time. Most are also the things to re-check when replicating the bridge
on a new/production site.

1. **mu-plugins ≠ plugins.** The bridge file must be **directly** in `wp-content/mu-plugins/`
   (auto-loads, no activation). In `wp-content/plugins/` it won't run until activated in admin;
   in a subfolder it won't load at all. Symptom: routes return `rest_no_route` 404 and
   `allure/v1` is absent from the `GET /` namespaces list.

2. **`get_params()`, not `get_json_params()`.** The MCP streamable endpoint injects
   `run_api_function`'s `data` as **body params**, not raw JSON. Handlers reading
   `get_json_params()` get an empty body (`allure_no_content` / `allure_no_colors`). All POST
   handlers use `$req->get_params()`.

3. **`$kit->save()` overwrites — always merge.** Saving Kit settings replaces the whole
   settings document; it does not merge. Writing typography wiped colors, etc. Use
   `allure_kit_save_merged()` (read `_elementor_page_settings`, `array_merge`, save). Also:
   `set-global-colors` with only `system_colors` drops existing `custom_colors`.

4. **Curl gives 401; use the tool.** A raw curl with the Bearer JWT → `401 rest_forbidden`.
   The JWT is only honored by `/wp-json/wp/v2/wpmcp/streamable`, which runs the REST call
   internally as user_id 1. Always test via the `run_api_function` tool.

5. **JWT expires ≤24h.** Regenerate from the plugin's Authentication Tokens tab (24h, not 1h)
   and re-run `claude mcp add`. MCP only loads in a new session.

6. **Protected meta is the whole reason for the bridge.** `_elementor_data`,
   `_elementor_page_settings` are `_`-prefixed/protected → REST PATCH silently ignores them
   (`meta` returns `[]`). The bridge writes them with `update_post_meta` server-side
   (+ `wp_slash` on the JSON, since WP unslashes meta and Elementor needs the slashes kept).

7. **Elementor version mismatch.** Target schema is 3.29.2 / container, JSON `version:0.4`.
   Managed hosts (e.g. Hostinger `hostinger-auto-updates.php`) may bump Elementor **core to
   4.x** while Pro stays 3.29.x. 4.x still imports 0.4 templates, but **validate render** and
   watch for legacy widgets (e.g. classic `accordion` vs V4 `nested-accordion`).

8. **No Kit CSS file to inspect.** If `wp-content/uploads/elementor/css/post-<kit>.css` /
   `global.css` 404, the CSS Print Method is **inline** (embedded per page) — you can't verify
   styling by fetching a CSS file. Verify via the rendered page or the editor.

9. **base64 via MCP bloats context.** Sending file bytes through `import-media` floods the
   conversation (a ~6KB SVG ≈ 8KB base64 string). Prefer: upload via WP Admin → Media (SVG is
   enabled by the bridge), or pass an external URL straight into an image widget. Reserve
   `import-media` for genuinely programmatic uploads.

10. **SVG upload is disabled by default in WP.** The bridge enables + lightly sanitizes it
    (`upload_mimes` + `wp_check_filetype_and_ext`, strips `<script>`/`on*`). Fine for
    disposable/staging; harden sanitization before production.

11. **Library single doesn't render publicly.** A `post_type: elementor_library` item won't
    show its layout at a URL. To *see* a block, import it as a `post_type: page` (preview page).

12. **extract-design-system on dynamic/Framer/CSS-in-JS sites comes back thin.** The
    `normalized.json` / `tokens.json` may report 0 palette colors and a single font. **Mine
    `.extract-design-system/raw.json`** — the real palette is under `colors.palette` (with
    `count` + `confidence`) and `colors._raw`, plus `typography.styles[]`, `borderRadius`,
    `borders`, `shadows`. Filter noise like `#0000EE` (unstyled-link blue). Then curate the 4
    Kit slots from there. (Extraction is for initialization, not pixel-perfect reproduction.)

13. **Extraction may detect proprietary / non-web fonts** (e.g. Edwardian Script, "Gin",
    system Times). They won't load on the web → substitute Google equivalents at curation
    (script → Pinyon/Great Vibes; high-contrast serif → Playfair/Cormorant; etc.) and note the
    swap in the profile. Rubik/Inter/Roboto etc. are already Google.

14. **Swapping the DS tokens alone does NOT change the identity** — case (UPPER vs mixed),
    radius, script accents, frame weight live in the *block/layout treatment*, not the tokens.
    To truly re-skin (e.g. sporty→heritage), redo the treatment too, not just the palette/font
    vars. Reinforces "tokens são globais; treatment vive no bloco."
