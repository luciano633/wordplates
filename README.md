# wordplates

Project repo for the WordPress + Elementor build pipeline — skills, the block acervo, and any
other material for the work.

## Layout

- **`skills/wp-elementor-builder/`** — the Claude Code skill (method + setup playbook) for
  building WordPress + Elementor pages design-first, per client. See its `SKILL.md`.
  Activate locally by symlinking into `~/.claude/skills`:
  ```sh
  ln -s "$PWD/skills/wp-elementor-builder" ~/.claude/skills/wp-elementor-builder
  ```
- **`blocks/`** — Elementor block JSON acervo (schema `version: 0.4`, container/Flexbox).
  Reusable section blocks, imported via the bridge (`POST /allure/v1/import-block`).

## Pipeline (short)

A WordPress with the `wordpress-mcp` MCP + the `allure-mcp-bridge` mu-plugin can be built
programmatically: apply a client's design system to the Elementor Kit, design screens in HTML
with that DS, translate sections into native Elementor blocks, import + assemble pages. Full
method in `skills/wp-elementor-builder/`.
