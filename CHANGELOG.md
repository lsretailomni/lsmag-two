# Changelog

All notable changes to this project will be documented in this file.

## [1.2.1] - 2020-02-19

### Added

- Added support for LS Omni 4.5 and LS Central 15.0 versions [OMNI-5098](https://solutions.lsretail.com/jira/browse/OMNI-5098)
- Provided configurable option to check real time inventory at LS Central before adding item to the cart [OMNI-5114](https://solutions.lsretail.com/jira/browse/OMNI-5114)
- Added support to display LS Central Order ID in the Sales order section of Magento admin panel https://solutions.lsretail.com/jira/browse/OMNI-5113
- Enhancement in Catalog Rule replication to support multiple discount value created against same offer in LS Central. https://solutions.lsretail.com/jira/browse/OMNI-4952
- Improvement in Discount code display on shopping cart page https://solutions.lsretail.com/jira/browse/OMNI-5013
- Enhanced configuration to provide option for multiple time formats on Store listing page https://solutions.lsretail.com/jira/browse/OMNI-5028
- Added "is_deleted" column in all the Replication Grids in Admin panel together to view 'deleted' data together with option to filter grids based on the flag value https://solutions.lsretail.com/jira/browse/OMNI-5084 
- Added "processed_date" column in all the replication grids to display the time when those values were processed in the Magento data structure https://solutions.lsretail.com/jira/browse/OMNI-5082
- Enhanced Discount display on product detail page to merge similar discounts against different product variants into one. https://solutions.lsretail.com/jira/browse/OMNI-5088
- Provided configurable option on Item Availability functionality to support display of "All store" where the item is available or only those which are marked as "Click and Collect" https://solutions.lsretail.com/jira/browse/OMNI-5099
- Enhanced Hierarchy synchronization process to support changing of hierarchy on different levels https://solutions.lsretail.com/jira/browse/OMNI-5096
- added support for controlling the order of variants display from LS Central https://solutions.lsretail.com/jira/browse/OMNI-5103
- Added all the missing phrases in the translation file en_US.csv for all the modules https://solutions.lsretail.com/jira/browse/OMNI-5106
- Improved synchronization process of variants images https://solutions.lsretail.com/jira/browse/OMNI-5101
- Added support to pick missing attribute value and assign it to products https://solutions.lsretail.com/jira/browse/OMNI-5101
- Added separate synchronization cron to monitor and create new/updated attribute option values and assigning it to products https://solutions.lsretail.com/jira/browse/OMNI-5095
- Added support to store LS Omni and LS Central version in Magento database and display values on admin configuration https://solutions.lsretail.com/jira/browse/OMNI-5118
- Unit Tests for Replication API's. https://solutions.lsretail.com/jira/browse/OMNI-5007
- Unit Tests for Customer Login to LS Central. https://solutions.lsretail.com/jira/browse/OMNI-5009
 
### Changed

- Modify order object to send authorization token for order payments to LS Central, https://solutions.lsretail.com/jira/browse/OMNI-5000
- Force Disable the configuration for "Applying tax on Custom Price".https://solutions.lsretail.com/jira/browse/OMNI-5001
- Remove section for store hours set as "Closed" under the operating hours on Store listing page https://solutions.lsretail.com/jira/browse/OMNI-5086
- PSR-2 Compliance for the whole package https://solutions.lsretail.com/jira/browse/OMNI-5100

### Bugs/Fixes

- Fixed issues in resetting the replication jobs from Admin panel https://solutions.lsretail.com/jira/browse/OMNI-5087
- Resolved compatibility issue with "Glace Magic Zoom" extention for Item availability functionality on Product detail page https://solutions.lsretail.com/jira/browse/OMNI-5093
- Resolved issue for unique identification of attribute option values received from LS Central and processing those values in Magento https://solutions.lsretail.com/jira/browse/OMNI-5094
- Resolved issue for the "Print Shipment" and "Print Invoices" section on Order History page. https://solutions.lsretail.com/jira/browse/OMNI-5109
- Resolved session blocking issue (When being used as "Files" instead of "DB" or "Redis") for Add to cart on product detail page https://solutions.lsretail.com/jira/browse/OMNI-5098
- Resolved issue for the status of discount create cron https://solutions.lsretail.com/jira/browse/OMNI-5124 
- Resolved issue for the processing of variants with same "Logical Order" https://solutions.lsretail.com/jira/browse/OMNI-5129




