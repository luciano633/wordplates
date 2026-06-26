# Client DS profile — Yuza (teste)

DS derivado por extração de `https://constantine.framer.ai/` (template Framer "Constantine"),
curado interativamente. Identidade editorial-esportiva: marinho + aço + creme, com pop coral.
DS CSS: `clients/yuza/yuza-ds.css`.

## Target site

- Produção: — (n/a; cliente de teste)
- **Staging / test**: WP novo a provisionar (este marco é só a definição do DS; setup do WP fica
  pra depois). Nome do cliente/WP de teste = Yuza.
- Auth: JWT ≤24h da aba Authentication Tokens (quando o WP existir).
- Notas: DS extraído de site Framer (dinâmico) → extração crua veio fraca no normalizado;
  paleta real recuperada do `raw.json`. Ruído descartado: `#0000EE` (link sem estilo).

## Global colors → Kit slots

| Kit slot   | Token name   | Hex       | Usage |
|------------|--------------|-----------|-------|
| primary    | Marinho      | `#0D3479` | marca, CTAs, títulos de destaque |
| secondary  | Azul-aço     | `#8B9DBC` | apoio, elementos mutados/UI |
| text       | Ink          | `#11161F` | corpo (quase-preto azulado) |
| accent     | Coral        | `#EE6C4D` | pops/CTAs (gerado via theme-factory, complementar ao marinho) |

Custom colors (manter junto dos system):
- Creme `#EEEDE4` (fundo de página) · Branco `#FFFFFF` (cards/superfícies)

## Typography → Kit

| Kit slot   | Font family | Weight | Google ou self-hosted? |
|------------|-------------|--------|------------------------|
| primary    | Bebas Neue  | 400    | **Google** (display, caixa-alta) |
| secondary  | Inter       | 700    | **Google** |
| text       | Inter       | 400    | **Google** |
| accent     | Bebas Neue  | 400    | **Google** |

- Self-hosted: **nenhuma** — Bebas Neue e Inter são Google Fonts (Elementor carrega nativo).
  Diferente do Allure (Aspekta self-hosted).
- Theme Style: body = Inter 400; H1–H6 = Bebas Neue 400 (uppercase); body_color → text token.

## Logos / brand assets

- A extração **não** trouxe logo (`logo:null`). Pendência: gerar/receber um logo "Yuza" (ou
  placeholder wordmark em Bebas Neue) antes de setar site logo.

## DS reference

- DS CSS: `clients/yuza/yuza-ds.css` (tokens semânticos + escala de tipo/espaço).
- Referência de design: `https://constantine.framer.ai/` (template "Constantine").
- Extração crua: `~/Desktop/Projetos/Sites/ds-extract/yuza/` (raw.json/normalized.json/tokens.*).
- Tom: editorial, esportivo, confiante; títulos display caixa-alta, muito respiro, fundo creme.
