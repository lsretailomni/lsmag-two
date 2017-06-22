# LS Mag for Magento 2 Development Guide 
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
6. This installs our module with a symlink. This breaks a few things.
  - First, it breaks the `ls-mag` binary. To get it to run, add a symlink to the `magento/vendor/autoload.php` file in the directory `ls-mag-two/vendor` (which you need to create):

    Run inside ls-mag-two:

    - `mkdir vendor`

    - `ln -s /var/www/magento2/vendor/autoload.php vendor/autoload.php`

    Now you can run `bin/ls-mag` again.
  - It also breaks the template loading. Go to the admin backend and set Stores->Advanced->Developer->Template Settings->Allow symlinks to True.

## Development Notes:

- In contrast to Magento1, in Magento2 the whole module is on a single folder, inside vendor/lsretail/ls-mag-two if you followed the above steps

- If you add a new module like omni/customer/replication inside the composer autoload directive, you need to remove and add it again with Composer

- To test the connection with the Omni Server, run `bin/ls-mag omni:client:ping` inside the lsmag-two folder

- It might be useful to use the automatic conversion tools at https://github.com/magento/code-migration to convert an old Magento1 installation with our modules for Magento 1 and base the new code on the converted file. As there are a lot of changes in Magento2, you can probably only re-use the general logic from the old module, not any actual code

- If you are working on the InstalLData class of a module and want to re-run the install method, drop the appropriate line from the `setup_module` table and run `bin/magento setup:upgrade`

- If you are debugging a cronjob with Xdebug on the command line via `XDEBUG_CONFIG="idekey=php-vs" PHP_IDE_CONFIG="serverName=developmentHost.local" bin/magento cron:run --group="XYZ"`, remember to disable the separate processes for each cronjob in the admin backend (Stores->Advanced->System->Cron->Appropriate group->Use separate process: False). Also, disable the automatic calls to the cron task from the crontab, this would interfere with the debugging

- Currently, if you want to set another omni server, you need to adjust the variable `DEFAULT_BASE_URL` inside `Ls\Omni\Service\Service` 

- After switching branches or changing the signature of a constructor, you will probably need to run `bin/magento setup:di:compile` again

Other notes:
- Development inside app/code? See http://devdocs.magento.com/guides/v2.0/extension-dev-guide/build/module-file-structure.html
-- No feasible due to composer dependencies we need
- php7.0 bin/ls-mag omni:client:generate -b "http://vmw-lsnav.local/LSOmniService/"
