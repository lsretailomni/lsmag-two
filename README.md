# LS Ecommerce - Magento Integration

## Compatibility
1. Magento Open Source 2.2.8 or later
2. LS Central 13.05 or later
3. LS Omni Server 4.0 or later

## Installation:

1. Navigate to your magento2 installation directory and run `composer require "lsretail/lsmag-two"`
2. Run `composer update` to install all the dependencies it needs.
3. Once done, you will see the list of our LS retail modules in disable section once you triggered the  `php bin/magento module:status` command from your Magento 2 CLI and lsretail folder in the vendor directory.
4. To enable all our modules, run command from command line, `php bin/magento module:enable Ls_Core Ls_Omni Ls_Customer Ls_Replication`
5. Set the base url of Omni by using this command `php bin/magento config:set ls_mag/service/base_url http://yourOmnihostname/LSOmniService`
6. Run `php bin/magento omni:client:generate` to generate all the files required for the communication with omni.
7. Run `php bin/magento replication:generate` to generate all the files required for replication.
followed by `php bin/magento setup:upgrade ` and  `php bin/magento setup:di:compile` from Magento 2 instance so that it can update the magento2 database with our modules schema and interceptor files.
8. Once done, you will see the list of our modules by running `php bin/magento module:status` which means our module is now good to go.  
9. To check the commands available for our module run 'php bin/magento' from the command line, you will see all the commands available for our extension. 
10. To test the connectivity to Omni server, run `php bin/magento omni:client:ping` to test the connection. If Ping return successfully, then you can procedd with next steps.
10. Once done, you will see all the new tables created in your Magento 2 database with prefix `ls_replication_*`
11. Next thing is to set configurations of Nav store and Hierarchy from backend to replicate data, to do so, navigate to Stores->Configuration->LS Retail->General Configuration, and choose the store and Hierarchy code to replicate data. Make sure you do all the configurations which are required on the Omni server for ecommerce i-e disabling security token for authentication.
12. If your server is setup for cron, then you will see all the new crons created in the `cron_schedule` table if not, it means your server is not setup to schedule cron, to trigger the cron manually,run `php bin/magento cron:run from command line. 
13. To Trigger the cron manually from admin panel, navigate to LS Retail -> Cron Listing from the left menu and click on the cron which needs to be run.
14. To see if the data is replicated in the Magento completely or not, you can navigate to any Replication job from `LS Retail -> Replication` Status and there we can see the status with `Processed` or `Not Processed` in the grid.