# Changelog

All notable changes to this project will be documented in this file.
## [1.3.1] - 2020-05-07

### Added

- Added control to manage the sort order display of product variant options on product detail page from LS Central. [OMNI-5130](https://solutions.lsretail.com/jira/browse/OMNI-5130)
- Added more validation rules for retrieving image response from LS Central during Image synchronizations. [OMNI-5157](https://solutions.lsretail.com/jira/browse/OMNI-5157)
- Closing hours for specific physical stores will now be visible on Store listing page.[OMNI-5172](https://solutions.lsretail.com/jira/browse/OMNI-5172)
- LS Central user will now be able to see the error message while capturing payment and posting invoice from LS Central through web hooks[OMNI-5155](https://solutions.lsretail.com/jira/browse/OMNI-5155)
 
### Changed

- Remove all unnecessary columns from the Replication Grids which are no longer available at LS Central [OMNI-5159](https://solutions.lsretail.com/jira/browse/OMNI-5159)
- Remove kiwicommerce cron extension from the required package. [OMNI-5176](https://solutions.lsretail.com/jira/browse/OMNI-5176)

### Bugs/Fixes

- Issue with cron job status for Product synchronization in Admin panel.  [OMNI-5164](https://solutions.lsretail.com/jira/browse/OMNI-5164)
- Issue with running "reset cron" jobs against each store from admin Panel. [OMNI-5169](https://solutions.lsretail.com/jira/browse/OMNI-5169)
- Issue in loading LS Retail configuration page from Admin panel when the LS Central service is down.  [OMNI-5175](https://solutions.lsretail.com/jira/browse/OMNI-5175)
- Issue in storing customer card ID when logging in for the very first time.[OMNI-5179](https://solutions.lsretail.com/jira/browse/OMNI-5179)



## [1.3.0] - 2020-03-02

### Added

- Support for multiple web store in same Magento instance. [OMNI-4879](https://solutions.lsretail.com/jira/browse/OMNI-4879)
- Added support for LS Omni 4.6 and LS Central 15.01|15.02 versions.
- Added Unit tests for All the LS Omni web services. [OMNI-5036](https://solutions.lsretail.com/jira/browse/OMNI-5036)
- Added Unit tests for Customer registration into LS Central. [OMNI-5008](https://solutions.lsretail.com/jira/browse/OMNI-5008)
- Added Unit tests for Order Placement into LS Central. [OMNI-5012](https://solutions.lsretail.com/jira/browse/OMNI-5012)
- Added Unit tests for Basket Calculation into LS Central [OMNI-5011](https://solutions.lsretail.com/jira/browse/OMNI-5011)
- Added community checklist into the github repository. [OMNI-5128](https://solutions.lsretail.com/jira/browse/OMNI-5128)
 
### Changed

- Check data for updates before setting is_updated flag to true by maintaining different checksum. This is helpful when there are continuous schedulers running on LS Central side which keep sending data to Magento without any changes.[OMNI-5077](https://solutions.lsretail.com/jira/browse/OMNI-5077)
- Configurable option in admin to set the prefix of Magento Order id send to LS Central in order to avoid sending duplicate ID. This is helpful which two or more different magento environment (staging/production) connected to same LS Central.[OMNI-5121](https://solutions.lsretail.com/jira/browse/OMNI-5121)

### Bugs/Fixes

- Issue with setting Mix & Match discount prices in Magento. [OMNI-5150](https://solutions.lsretail.com/jira/browse/OMNI-5150)
- Issue with synchronization of parent category once its removed from LS Central. [OMNI-5149](https://solutions.lsretail.com/jira/browse/OMNI-5149)
- Fixed the ACL issues for LS Retail resources in the admin Roles. [OMNI-5146](https://solutions.lsretail.com/jira/browse/OMNI-5146)
- Issue with Coupon code remain in checkout session after the order is placed. [OMNI-5122](https://solutions.lsretail.com/jira/browse/OMNI-5122)
- Issue with synchronization of Inventories when the item does not exist in Magento. [OMNI-5150](https://solutions.lsretail.com/jira/browse/OMNI-5125)
- Issue with saving customer address in Magento when LS Central user login into the Magento for the very first time. [OMNI-5119](https://solutions.lsretail.com/jira/browse/OMNI-5119)
- Issue in checkout page design when user navigate back to Shipping stage from Payment stage. [OMNI-5126](https://solutions.lsretail.com/jira/browse/OMNI-5126)



## [1.2.1] - 2020-02-19

### Added

- Added support for LS Omni 4.5 and LS Central 15.0 versions. [OMNI-5098](https://solutions.lsretail.com/jira/browse/OMNI-5098)
- Provided configurable option to check real time inventory at LS Central before adding item to the cart. [OMNI-5114](https://solutions.lsretail.com/jira/browse/OMNI-5114)
- Added support to display LS Central Order ID in the sales order grid section of Magento admin panel. [OMNI-5113](https://solutions.lsretail.com/jira/browse/OMNI-5113)
- Enhanced catalog rules replication to support multiple discount value created against same offer in LS Central. [OMNI-4952](https://solutions.lsretail.com/jira/browse/OMNI-4952)
- Improvement in discount/coupon code display on shopping cart page. [OMNI-5013](https://solutions.lsretail.com/jira/browse/OMNI-5013)
- Provided configurable options to control the display of time formats on store listing page. [OMNI-5028](https://solutions.lsretail.com/jira/browse/OMNI-5028)
- Added "is_deleted" column in all the Replication Grids in Admin panel to view and filter data 'deleted' from LS Central. [OMNI-5084](https://solutions.lsretail.com/jira/browse/OMNI-5084) 
- Added "processed_date" column in all flat replication grids to display the date and time when those values were processed in the Magento data structure. [OMNI-5082](https://solutions.lsretail.com/jira/browse/OMNI-5082)
- Enhanced Discount display section on product detail page to control the display of duolicate discounts against same Item. [OMNI-5088](https://solutions.lsretail.com/jira/browse/OMNI-5088)
- Provided configurable option on Item Availability functionality on product detail page to control display of "All store" where the item is available or only those which are marked as "Click and Collect". [OMNI-5099](https://solutions.lsretail.com/jira/browse/OMNI-5099)
- Enhanced category synchronization process to support changing of hierarchy on different levels. [OMNI-5096](https://solutions.lsretail.com/jira/browse/OMNI-5096)
- Added support for controlling the order of variants display on product detail page from LS Central. [OMNI-5103](https://solutions.lsretail.com/jira/browse/OMNI-5103)
- Added all the missing phrases in the translation file en_US.csv. [OMNI-5106](https://solutions.lsretail.com/jira/browse/OMNI-5106)
- Improved synchronization process of variants images. [OMNI-5101](https://solutions.lsretail.com/jira/browse/OMNI-5101)
- Added support to pick missing attribute value and assign it to products. [OMNI-5095](https://solutions.lsretail.com/jira/browse/OMNI-5095)
- Created separate synchronization cron to monitor and create new/updated attribute option values and assigning it to products. [OMNI-5095](https://solutions.lsretail.com/jira/browse/OMNI-5095)
- Added support to store LS Omni and LS Central version in Magento database and display values on admin configuration. [OMNI-5118](https://solutions.lsretail.com/jira/browse/OMNI-5118)
- Unit Tests for Replication API's. [OMNI-5007](https://solutions.lsretail.com/jira/browse/OMNI-5007)
- Unit Tests for Customer Login to LS Central. [OMNI-5009](https://solutions.lsretail.com/jira/browse/OMNI-5009)
 
### Changed

- Modify order object to send authorization token for order payments to LS Central. [OMNI-5000](https://solutions.lsretail.com/jira/browse/OMNI-5000)
- Force Disable the configuration for "Applying tax on Custom Price".[OMNI-5001](https://solutions.lsretail.com/jira/browse/OMNI-5001)
- Remove section for store hours set as "Closed" under the operating hours on Store listing page. [OMNI-5086](https://solutions.lsretail.com/jira/browse/OMNI-5086)
- PSR-2 Compliance for the whole package. [OMNI-5100](https://solutions.lsretail.com/jira/browse/OMNI-5100)

### Bugs/Fixes

- Fixed issues in resetting the replication crons from Admin panel. [OMNI-5087](https://solutions.lsretail.com/jira/browse/OMNI-5087)
- Resolved compatibility issue with "Glace Magic Zoom" extention for Item availability functionality on Product detail page. [OMNI-5093](https://solutions.lsretail.com/jira/browse/OMNI-5093)
- Resolved issue for unique identification of attribute option values received from LS Central and processing those values in Magento. [OMNI-5094](https://solutions.lsretail.com/jira/browse/OMNI-5094)
- Resolved issue for the "Print Shipment" and "Print Invoice" section on Order History page. [OMNI-5109](https://solutions.lsretail.com/jira/browse/OMNI-5109)
- Resolved session blocking issue (When session is configured to be used as "Files" instead of "DB" or "Redis") for Add to cart on product detail page. [OMNI-5098](https://solutions.lsretail.com/jira/browse/OMNI-5098)
- Resolved issue for the status of discount create cron. [OMNI-5124](https://solutions.lsretail.com/jira/browse/OMNI-5124) 
- Resolved issue for the processing of variants with same "Logical Order". [OMNI-5129](https://solutions.lsretail.com/jira/browse/OMNI-5129)
