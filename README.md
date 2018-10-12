# LS Mag for Magento 2 Development Guide 
## Development Installation:

1. Navigate to your magento2 installation directory and run `composer require "lsretail/lsmag-two:dev-Release/1.0" `
2. Run `composer update` to install all the dependencies it needs.
3. Once done, you will see the list of our LS retail modules in disable section once you triggered the  `php bin/magento module:status` command from your Magento 2 CLI and lsretail folder in the vendor directory.
4. To enable all our modules, run command from command line, `php bin/magento module:enable Ls_Core Ls_Omni Ls_Customer Ls_Replication`
5. Set the base url of Omni by using this command `php bin/magento config:set ls_mag/service/base_url http://yourOmnihostname/LSOmniService`
6. Run `php bin/magento omni:client:generate` to generate all the files required for the communication. 
7. Run `php bin/magento replication:generate` to generate all the files required for replication 
Followed by `php bin/magento  setup:upgrade ` and  `php bin/magento setup:di:compile` from Magento 2 instance so that it can update the magento2 database with our modules schema and interceptor files.
8. Once done, you will see the list of our modules by running `php bin/magento module:status` which means our module is now good to go.  
9. To check the commands available for our module run 'php bin/magento' from the command line, you will see all the commands available for our extension.  
. 
10. Once done, you will see all the new tables created in your Magento 2 database with prefix `ls_replication_*`.
11. Next thing is to set configurations and Nav store from Backend to replicate data, to do so, navigate to Stores->Configuration->LS Retail->General Configuration, and choose the store and Hierarchy code to replicate data. make sure you do all the configurations which are required on the Omni server for ecommerce i-e disabling security token for authentication.
12. If your server is setup for cron, then you will see all the new cron created in the `cron_schedule` table if not, it means your server is not setup to schedule cron, to trigger the cron manually,run `php bin/magento cron:run` from command line. 
13. Once done, you will see all the data in their respective LS and Magento tables from Omni. 
14. Once all data are replicated, uncomment these lines from `Replication/etc/crontab.xml` file to start populating those data into magento tables.

`
<group id="replication">

    <job name="repl_attributes" instance="Ls\Replication\Cron\AttributesCreateTask" method="execute">
      <schedule>*/5 * * * *</schedule>
    </job>
    <job name="repl_category" instance="Ls\Replication\Cron\CategoryCreateTask" method="execute">
      <schedule>*/5 * * * *</schedule>
    </job>
    <job name="repl_products" instance="Ls\Replication\Cron\ProductCreateTask" method="execute">
      <schedule>*/10 * * * *</schedule>
    </job>
    <job name="repl_barcode_update" instance="Ls\Replication\Cron\BarcodeUpdateTask" method="execute">
      <schedule>*/12 * * * *</schedule>
    </job>
    <job name="repl_discount_create" instance="Ls\Replication\Cron\DiscountCreateTask" method="execute">
      <schedule>*/20 * * * *</schedule>
    </job>
  </group>
  `
 


 