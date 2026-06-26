---
name: project-dev-environment
description: How to run bin/magento, compile, and cache:flush for the ls-hyva Magento install (Docker, PHP 8.3)
metadata:
  type: project
---

The ls-hyva Magento install requires PHP 8.3, but the host CLI PHP is 8.1 — running `php bin/magento` directly on the host fails with a Composer platform check error. Run all Magento CLI inside the Docker container instead.

**Why:** The repo uses a markoshust Docker stack (compose*.yaml in the lsmag-two working dir). The PHP-FPM container is `ls-hyva-phpfpm-1` (PHP 8.3); Magento root inside it is `/var/www/html`.

**How to apply:**
- Compile: `docker exec ls-hyva-phpfpm-1 sh -c 'cd /var/www/html && php bin/magento setup:di:compile'`
- Cache flush: `docker exec ls-hyva-phpfpm-1 sh -c 'cd /var/www/html && php bin/magento cache:flush'`
- The `Ls_Omni` (lsmag-two) and hospitality modules are symlinked into `/var/www/html/vendor/lsretail/` from `artifacts/lsretail/`, so edits in the artifacts tree are live in the container.
- To schema-validate layout XML (xmllint not installed; xsd uses urn: includes DOMDocument can't resolve), use Magento's own validator: `\Magento\Framework\Config\Dom::validateDomDocument($dom, $resolver->getRealPath('urn:magento:framework:View/Layout/etc/page_configuration.xsd'))` via a bootstrap script in the container.

**Known pre-existing compile blocker (NOT caused by cart/template work):** `setup:di:compile` fails on `Ls\Hospitality\Block\Order\Info` — "Incompatible argument type: Required type: \Magento\Framework\App\Http\Context. Actual type: array". This is in the separate `hospitality` module and is unrelated to `Ls_Omni` changes.
