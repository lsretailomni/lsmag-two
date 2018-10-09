# LS Mag for Magento 2 Development Guide 
## Development Installation:

1. Navigate to your magento2 installation directory and run `omposer require "lsretail/lsmag-two:dev-Release/1.0" `
2. Run `composer update` to install all the dependencies it needs.
3. Once done, you will see the list of our LS retail modules in disable section once you triggered the  `php bin/magento module:status` command from your Magento 2 CLI and lsretail folder in the vendor directory.
4. To enable all our modules, run command from command line, `php bin/magento module:enable Ls_Core Ls_Omni Ls_Customer Ls_Replication`
5. Followed by `php bin/magento  setup:upgrade ` and  `php bin/magento setup:di:compile` from Magento 2 instance so that it can update the magento2 database with our modules schema and interceptor files.
6. Once done, you will see the list of our modules by running `php bin/magento module:status` which means our module is now good to go.  
7. To check the commands available for our module run 'php bin/magento' from the command line, you will see all the commands available for our extension.  
8. Make sure to check Replication folder contains `Api/Data` folder. If it doesn’t we have to create a new folder. 
9. Run `php bin/magento replication:generate` to generate all the files required for replication. 
10. Make sure to check `Omni` folder contains `Client/Ecommerce/Entity` and `Client/Ecommerce/Operation` folder. If it doesn’t we have to create a new empty folder. 
11. Run `php bin/magento omni:client:generate` to generate all the files required for the communication. 
12. Once done, you will see all the new tables created in your Magento 2 database with prefix `ls_replication_*`.
13. Configure the Service Base URL from Magento Backend. (Refer Below), pleas make sure you do all the configurations which are required on the Omni server for ecommerce i-e disabling security token for authentication.
14. If your server is setup for cron, then you will see all the new cron created in the `cron_schedule` table if not, it means your server is not setup to schedule cron, to trigger the cron manually,run `php bin/magento cron:run` from command line. 
15. Once done, you will see all the data in their respective LS and Magento tables from Omni. 
 