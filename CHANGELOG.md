# Changelog

All notable changes to this project will be documented in this file.

## [1.2.1] - 2020-02-19

### Added

- Added support for LS Omni 4.5 and LS Central 15.0 versions. [OMNI-5098](https://solutions.lsretail.com/jira/browse/OMNI-5098)
- Provided configurable option to check real time inventory at LS Central before adding item to the cart. [OMNI-5114](https://solutions.lsretail.com/jira/browse/OMNI-5114)
- Added support to display LS Central Order ID in the Sales order section of Magento admin panel. [OMNI-5113](https://solutions.lsretail.com/jira/browse/OMNI-5113)
- Enhancement in Catalog Rule replication to support multiple discount value created against same offer in LS Central. [OMNI-4952](https://solutions.lsretail.com/jira/browse/OMNI-4952)
- Improvement in Discount code display on shopping cart page. [OMNI-5013](https://solutions.lsretail.com/jira/browse/OMNI-5013)
- Enhanced configuration to provide option for multiple time formats on Store listing page. [OMNI-5028](https://solutions.lsretail.com/jira/browse/OMNI-5028)
- Added "is_deleted" column in all the Replication Grids in Admin panel together to view 'deleted' data together with option to filter grids based on the flag value. [OMNI-5084](https://solutions.lsretail.com/jira/browse/OMNI-5084) 
- Added "processed_date" column in all the replication grids to display the time when those values were processed in the Magento data structure. [OMNI-5082](https://solutions.lsretail.com/jira/browse/OMNI-5082)
- Enhanced Discount display on product detail page to merge similar discounts against different product variants into one. [OMNI-5088](https://solutions.lsretail.com/jira/browse/OMNI-5088)
- Provided configurable option on Item Availability functionality to support display of "All store" where the item is available or only those which are marked as "Click and Collect". [OMNI-5099](https://solutions.lsretail.com/jira/browse/OMNI-5099)
- Enhanced Hierarchy synchronization process to support changing of hierarchy on different levels. [OMNI-5096](https://solutions.lsretail.com/jira/browse/OMNI-5096)
- added support for controlling the order of variants display from LS Central. [OMNI-5103](https://solutions.lsretail.com/jira/browse/OMNI-5103)
- Added all the missing phrases in the translation file en_US.csv for all the modules. [OMNI-5106](https://solutions.lsretail.com/jira/browse/OMNI-5106)
- Improved synchronization process of variants images. [OMNI-5101](https://solutions.lsretail.com/jira/browse/OMNI-5101)
- Added support to pick missing attribute value and assign it to products. [OMNI-5095](https://solutions.lsretail.com/jira/browse/OMNI-5095)
- Added separate synchronization cron to monitor and create new/updated attribute option values and assigning it to products. [OMNI-5095](https://solutions.lsretail.com/jira/browse/OMNI-5095)
- Added support to store LS Omni and LS Central version in Magento database and display values on admin configuration. [OMNI-5118](https://solutions.lsretail.com/jira/browse/OMNI-5118)
- Unit Tests for Replication API's. [OMNI-5007](https://solutions.lsretail.com/jira/browse/OMNI-5007)
- Unit Tests for Customer Login to LS Central. [OMNI-5009](https://solutions.lsretail.com/jira/browse/OMNI-5009)
 
### Changed

- Modify order object to send authorization token for order payments to LS Central. [OMNI-5000](https://solutions.lsretail.com/jira/browse/OMNI-5000)
- Force Disable the configuration for "Applying tax on Custom Price".[OMNI-5001](https://solutions.lsretail.com/jira/browse/OMNI-5001)
- Remove section for store hours set as "Closed" under the operating hours on Store listing page. [OMNI-5086](https://solutions.lsretail.com/jira/browse/OMNI-5086)
- PSR-2 Compliance for the whole package. [OMNI-5100](https://solutions.lsretail.com/jira/browse/OMNI-5100)

### Bugs/Fixes

- Fixed issues in resetting the replication jobs from Admin panel. [OMNI-5087](https://solutions.lsretail.com/jira/browse/OMNI-5087)
- Resolved compatibility issue with "Glace Magic Zoom" extention for Item availability functionality on Product detail page. [OMNI-5093](https://solutions.lsretail.com/jira/browse/OMNI-5093)
- Resolved issue for unique identification of attribute option values received from LS Central and processing those values in Magento. [OMNI-5094](https://solutions.lsretail.com/jira/browse/OMNI-5094)
- Resolved issue for the "Print Shipment" and "Print Invoices" section on Order History page. [OMNI-5109](https://solutions.lsretail.com/jira/browse/OMNI-5109)
- Resolved session blocking issue (When being used as "Files" instead of "DB" or "Redis") for Add to cart on product detail page. [OMNI-5098](https://solutions.lsretail.com/jira/browse/OMNI-5098)
- Resolved issue for the status of discount create cron. [OMNI-5124](https://solutions.lsretail.com/jira/browse/OMNI-5124) 
- Resolved issue for the processing of variants with same "Logical Order". [OMNI-5129](https://solutions.lsretail.com/jira/browse/OMNI-5129)




