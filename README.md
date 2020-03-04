# LS Ecommerce - Magento Integration (V1.2.1)

## Compatibility
1. Magento Open Source 2.3.2 | 2.3.3
2. LS Central 14.02 | 15.0
3. LS Omni Server 4.5

## Installation:

1. Navigate to your magento2 installation directory and run `composer require "lsretail/lsmag-two"`
2. Run `composer update` to install all the dependencies of the package.
3. Once done, you will see the list of our LS retail modules in disable section once you triggered the  `php bin/magento module:status` command from the root directory.
4. To enable all our modules, run `php bin/magento module:enable Ls_Core Ls_Omni Ls_Customer Ls_Replication Ls_Webhooks` from the root directory.
5. Run `php bin/magento setup:upgrade ` and  `php bin/magento setup:di:compile` from root directory to update magento2 database with the schema and generate interceptor files.
6. Once done, you will see the list of our modules in enabled section by running `php bin/magento module:status`.
7. Configure the connection with LS Central by navigating to LS Retail -> Configuration from Magento Admin panel, enter the base url of the Omni server and choose the store and Hierarchy code to replicate data. Make sure to do all the configurations which are required on the Omni server for ecommerce i-e disabling security token for authentication.
8. If your server is setup for cron, then you will see all the new crons created in the `cron_schedule` table and status of all the replication data by navigating to LS Retail -> Cron Listing from the Admin Panel.
9. To Trigger the cron manually from admin panel, navigate to LS Retail -> Cron Listing from the left menu and click on the cron which needs to be run.
10. To check the status of data replicated from LS Central, navigate to any Replication job from `LS Retail -> Replication Status` and there we can see the list of all data along with status with `Processed` or `Not Processed` in the grid.
