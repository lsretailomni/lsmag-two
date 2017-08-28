# LS Mag for Magento 2 Development Guide 
More documents on Magento development at LS Retail can be found under `R:\LS Omni\LS Mag`.

## Development Installation:

1. Install Magento2
2. Clone lsmag-two somewhere somewhere outside the installation folder of Magento2, for example inside `/var/www/lsmag-two`
    * We use a [Bitbucket repository](https://bitbucket.org/account/user/lsmag/projects/LM) for version control. LS Retail IT is part of this team, so they should be able to give you access
    * There is also a mirror on the [internal Bitbucket instance](https://bitbucket.lsretail.com/projects/OMNI/repos/lsmag-two/browse), but as this is not reachable from the outside, it cannot be used for pulling from outside servers. It also might not be up to date as the mirroring is not automated.
3. Open composer.json of Magento2 (usually `/var/www/magento/composer.json` or `/srv/magento/composer.json`).
4. Add path of step 2 to composer repositories:

    `"repositories": [
            {
                "type": "path",
                "url" : "/var/www/lsmag-two"
            }
        ]`

5. Now you can either do a symlink installation or a copy. The symlink breaks a few things, which need to be fixed with more symlinks, the copy on the other hand needs a `composer update` every time you change something in the code.
    - Symlink installation:
        1. Run `composer require "lsretail/ls-mag-two @dev"` inside the Magento2 directory (the [@dev tells composer to use a symlink](https://stackoverflow.com/questions/29994088/composer-require-local-package))
        2. This installs our module with a symlink. This breaks the `ls-mag` binary. To get it to run, add a symlink to the `magento/vendor/autoload.php` file and the composer directory in the directory `ls-mag-two/vendor` (which you need to create):
            - Run inside `ls-mag-two`:
                - `mkdir vendor`
                - `ln -s /var/www/magento2/vendor/autoload.php vendor/autoload.php`
                - `ln -s /var/www/magento2/vendor/composer vendor/composer`
            - Now you can run `bin/ls-mag` again.
         3. Templates
    - Copy installation:
        1. Run `composer require "lsretail/ls-mag-two"` inside the Magento2 directory
        2. Run the command again after _each_ code change
 
- Releases don't contain some folders as noted in our `composer.json`:
    - `/docs` (raw documentation files not intented for customers)
    - `/dev` (Docker installation, should get releases seperately)
    - `/README.txt` (Not intended for customers)
## Development Notes:

- In contrast to Magento1, in Magento2 the whole module is on a single folder, inside vendor/lsretail/ls-mag-two if you followed the above steps

- If you add a new module like omni/customer/replication inside the composer autoload directive, you need to remove and add it again with Composer

- To test the connection with the Omni Server, run bin/ls-mag omni:client:ping inside the lsmag-two folder

- It might be useful to use the automatic conversion tools at https://github.com/magento/code-migration to convert an old Magento1 installation with our modules and base the new code on the converted file. As there are a lot of changes in Magento2, you can probably only re-use the general logic from the old module, not any actual code without changes.

- If you are working on the InstallData class of a module and want to re-run the install method, drop the appropriate line from the `setup_module` table and run `bin/magento setup:upgrade`.

- After switching branches or changing the signature of a constructor, you will probably need to run `bin/magento setup:di:compile` again. If you switch to a branch where one of the modules specifies a lower version than the previous one (inside `etc\module.xml`), you need to either uninstall the module and install it again (`bin/magento module:uninstall module && bin/magento module:install module`) or manually edit the database table `setup_module` (which might break things).

## Conversion Notes

We are currently in the process of converting our solution from Magento 1 to Magento 2. We first need to get up to the feature level of Magento 1 before we proceed.

While converting the Module from Magento 1 to Magento 2, some architectural things changed. In Magento 1, we relied heavily on the registry and sessions using a centralized key storage in the LSR class. In Magento 2, we sometimes use Helper classes instead. Below, some identifiers and their replacements are noted:

* LSR::SESSION_CHECKOUT_BASKETCALCULATION => BasketHelper::getOneListCalculation()
* LSR::REGISTRY_LOYALTY_WATCHNEXTSAVE => AddToCartObserver::watchNextSave()
  * Used in CartObserver and BasketObserver

## Release process
We run our own composer repository on the stable server under the URL [lsmag-stable.lsretail.com/dist](http://lsmag-stable.lsretail.com/dist/). It should be populated on every release using this process:

1. Log into master server
2. Switch to omni user if you are not already: `sudo -i -u omni`
3. Go to `/home/omni/satis`
4. Run `php5 bin/satis build -n satis.json ./build`
5. Copy the `build` directory to the web server:
    
    `sudo cp -r build/* /srv/magento/dist`
6. Change ownership back to www-data:
    
    `sudo chown -R www-data:www-data /srv/magento/dist`

It might also be possible to use this as a source of updates on the master server. This needs to be investigated.

## Older notes:
- Development inside app/code? See http://devdocs.magento.com/guides/v2.0/extension-dev-guide/build/module-file-structure.html
-- Not feasible due to composer dependencies we need
- php7.0 bin/ls-mag omni:client:generate -b "http://vmw-lsnav.local/LSOmniService/"
