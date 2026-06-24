# Ls_Customer — Hyvä theme support

This module ships Hyvä-themed templates (loyalty offers grid, account widgets,
registration form) plus their styles. Under Luma nothing here applies — the
`hyva_*` layout handles and the `hyva_config_generate_before` observer only
activate when a Hyvä theme is installed.

## Styling: how it reaches the browser

The Hyvä styles live in:

```
view/frontend/tailwind/module.css
```

They are **not** loaded via `<css src>`. Instead they are compiled into the
theme's single global `styles.css` by Hyvä's Tailwind build (CSS-first
`@source`). This keeps page weight minimal — no extra CSS request per page.

The module registers itself with that build automatically via
`Observer/RegisterModuleForHyvaConfig.php` (bound to `hyva_config_generate_before`
in `etc/frontend/events.xml`), so no manual `hyva.config.json` editing is needed.

## ⚠️ Required build step after install / update

Because the styles compile into the theme's `styles.css`, the theme's **Tailwind
build must run** — `setup:upgrade` and `setup:static-content:deploy` alone do
**not** compile Tailwind. After installing or updating this module:

```bash
bin/magento setup:upgrade
bin/magento hyva:config:generate          # registers this module (automatic via the observer)

# In the active Hyvä theme directory:
cd <path-to-hyva-theme>/web/tailwind
npm install                               # first time only
npm run build                             # compiles styles.css (runs hyva-sources first)

bin/magento setup:static-content:deploy
bin/magento cache:flush
```

If the loyalty tiles render unstyled, the Tailwind build has not been run (or
`hyva:config:generate` was not run before it).

> Note: `setup:static-content:deploy` in developer mode can skip an unchanged
> output path. If a rebuilt `styles.css` is not published, delete
> `pub/static/frontend/<Vendor>/<theme>/<locale>/css/styles.css` and redeploy.
