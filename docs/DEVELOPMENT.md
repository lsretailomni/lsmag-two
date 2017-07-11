# LS Mag for Magento 2 Development Guide 
More documents on Magento development inside LS Retail can be found under `R:\LS Omni\LS Mag`.

## Development Installation:

1. Install Magento2
2. Clone lsmag-two somewhere somwhere outside the installation folder of Magento2, for example inside /var/www/lsmag-two
3. Open composer.json of Magento2
4. Add path of step 2 to composer repositories:

`"repositories": [
        {
            "type": "path",
            "url" : "/var/www/lsmag-two"
        }
    ]`

5. Run composer require "lsretail/ls-mag-two @dev" inside the Magento2 directory
6. This installs our module with a symlink. This breaks the `ls-mag` binary. To get it to run, add a symlink to the `magento/vendor/autoload.php` file in the directory `ls-mag-two/vendor` (which you need to create):

Run inside ls-mag-two:

`mkdir vendor`

`ln -s /var/www/magento2/vendor/autoload.php vendor/autoload.php`

Now you can run `bin/ls-mag` again.

## Development Notes:

- In contrast to Magento1, in Magento2 the whole module is on a single folder, inside vendor/lsretail/ls-mag-two if you followed the above steps

- If you add a new module like omni/customer/replication inside the composer autoload directive, you need to remove and add it again with Composer

- To test the connection with the Omni Server, run bin/ls-mag omni:client:ping inside the lsmag-two folder

- It might be useful to use the automatic conversion tools at https://github.com/magento/code-migration to convert an old Magento1 installation with our modules and base the new code on the converted file. As there are a lot of changes in Magento2, you can probably only re-use the general logic from the old module, not any actual code

- If you are working on the InstalLData class of a module and want to re-run the install method, drop the appropriate line from the setup_module table and run bin/magento setup:upgrade.

## Conversion Notes

While converting the Module from Magento 1 to Magento 2, some architectural things changed. In Magento 1, we relied heavily on the registry and session. In Magento 2, we sometimes use Helper classes instead. Below, some identifiers and their replacements are noted:

* LSR::SESSION_CHECKOUT_BASKETCALCULATION => BasketHelper::getOneListCalculation()
* LSR::REGISTRY_LOYALTY_WATCHNEXTSAVE => AddToCartObserver::watchNextSave()
  * Used in CartObserver and BasketObserver

Older notes:
- Development inside app/code? See http://devdocs.magento.com/guides/v2.0/extension-dev-guide/build/module-file-structure.html
-- Not feasible due to composer dependencies we need
- php7.0 bin/ls-mag omni:client:generate -b "http://vmw-lsnav.local/LSOmniService/"
