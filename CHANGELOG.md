# Changelog

All notable changes to this project will be documented in this file.

## [2.1.0] - 2022-12-05

### Added

- Added support to send out order update emails using webhooks for hospitality based orders. [21353](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/21353)
- Added support to get coupons and discount based on ItemId using GraphQl interface. [24306](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/24306)
- Added support for color swatch attributes. [26087](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/26087)




### Changed

- Changed dependency from product sku to the new custom product attribute 'LS Central Item ID' for all the data mapping between LS Central and Magento.  [20380](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/20380)


### Bugs/Fixes

- Fixed issue with enabling "Used for Sorting in Product Listing" option for a product attribute on attribute edit page. [26808](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/26808)
- Fixed issue with using 'store email' contact to send out click and collect webhook emails. [28073](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/28073)
- Fixed issue of discount lines not going through when syncing order from admin. [27455](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/27455)
- Fixed issue in assigning categories and tax class to associated simple products of the configurable products. [28106](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/28106)
- Fixed currency issue in customer dashboard for all orders which were placed offline in physical stores. [28820](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/28820)
- Fixed issue with canceled order status not showing in restricted order configuration. [29817](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/29817)
- Fixed issue in Image replication logic to remove duplicate catalog images. [28105](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/28105)
- Fixed currency conversion rate in payment line when sending order to LS Central. [29839](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/29839)




## [2.0.0] - 2022-10-07

### Added

- Added support for compatibility of Magento 2.4.5. [24183](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/24183)
- Added support to restrict syncing orders with LS Central based on configured multi-select order statuses in the Magento admin. [23444](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/23444)
- Added support to enable/disable unit of measure from LS Central. This is helpful for merchants who want to enable/disable specific units of measure from selling on eCommerce. [19132](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/19132)
- Added support to translate text-based attributes in Magento. [25189](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/25189)
- Added support to translate option labels of variant type attributes from LS Central. [25678](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/25678)
- Added support to translate non variant attributes of items from LS Central. [25739](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/25739)
- Added support for anonymous ordering on GraphQl by prefilling address attributes based on the configuration from Magento admin. [23296](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/23296)
- Added support for QR Code ordering on GraphQl by setting encoded QR-code values into session to sync with LS Central while placing order. [23296](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/23296)
- Added support to expose all LS eCommerce configurations on GraohQl. [22856](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/22856)
- Added support to fetch real time information related to kitchen status and preparation time on success page for PWA. [24301](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/24301)
- Added support to map Item CountryOfOrigin from LS Central to be mapped with product CountryofManufacturer in Magento. [25680](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/25680)




### Bugs/Fixes

- Fixed issue with using different order prefix's on website level. [23374](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/23374)
- Fixed validation for UK based zip codes by allowing alphanumeric characters. [25189](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/25189)
- Fixed exception while loading hierarchy code on Magento admin due to incorrect scope. [25089](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/25089)
- Fixed issues with creating refund including shipment amount when cancelling orders from LS Central. [24955](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/24955)
- Fixed issue in displaying POS created orders in my account section. [25412](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/25412)
- Fixed issue with rendering order pickup and collected email templates. [25326](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/25326)
- Fixed issue in displaying available coupons for existing basket on checkout page. [25757](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/25757)
- Fixed compatibility issue with Magento 2.4.2 and older versions. [26075](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/26075)
- Fixed issue with parent configurable product showing as out-of-stock even if either of the child simple product is back in-stock. [25821](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/25821)
- Fix issue in displaying recommended products from LS Recommend on PDP and shopping cart page. [26085](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/26085)
- Fixed Issue with unprocessed item images having hyphens in the ItemID. [26966](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/26966)
- Fixed issue with unloading JS files on shopping cart and checkout page when LS eCommerce integration is disabled from admin panel. [26801](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/26801)
- Github issue [#38](https://github.com/lsretailomni/lsmag-two/issues/38)
- Github issue [#39](https://github.com/lsretailomni/lsmag-two/issues/39)
- Github issue [#40](https://github.com/lsretailomni/lsmag-two/issues/40)




## [1.18.0] - 2022-08-08

### Added

- Added additional support for the takeaway component on graphQL. [20779](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/20779)
- Added support for round pickup times based on configurable timeslots for takeaway orders. [20455](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/20455)
- Added support to optimise the web services call being made to LS Central for customer registration and login. [21639](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/21639)
- Added support to optimize checkout page load performance by making click and collect requests asynchronous. [23878](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/23878)




### Bugs/Fixes

- Fixed issues while creating UoM products by adding additional validations on empty descriptions. [22752](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/22752)
- Fixed variants creation and started filtering NULL values from Code and Dimension columns in the Extended Variant Value Table. [22752](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/22752)
- Fixed checkout crash on PWA/graphql due to incorrect quote merge when customer tries to login and merge the quote. [23235](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/23235)
- Fixed issues while setting out of stock parent item back to in-stock once either of its child product becomes in-stock. [23499](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/23499)
- Fixed issues with flat replication cron jobs which were running even though LS Ecommerce configuration was disabled. [23297](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/23297)



## [1.17.0] - 2022-06-27

### Added

- Added support for the compatibility of Magento 2.4.4. [20457](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/20457)
- Added functionality to enable/disable item variants from LS Central - This is helpful for merchants who want to enable/disable specific variants from selling on eCommerce. [18548](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/18548)
- Added functionality to enable/disable LS eCommerce module on specific website level. This is helpful for partners/customers who are using multi stores on licensed Magento instance with one or more store not connected to LS Central. [19117](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/19117)  
- Added support to replicate modifier and ingredient images to be used for custom options on the product detail page. [OMNI-5601](https://solutions.lsretail.com/jira/browse/OMNI-5601)
- Added support to display swatch images on the frontend for modifiers and ingredients selection. [OMNI-5601](https://solutions.lsretail.com/jira/browse/OMNI-5601)
- Added support to configure, select and send 'requested delivery date' information from Magento to LS Central. [OMNI-5502](https://solutions.lsretail.com/jira/browse/OMNI-5502)
- Added support to display LS Central shipment id in shipment detail page in Magento. [15377](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/15377)
- Added functionality to reset catalog, orders, and customers data on website/store view level. This is helpful for clients who already have one website live and another website in development. [19131](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/19131)




### Changed

- Changed api parameter from ShipmentId to ShipmentNo from LS Central while creating shipment through webhooks. [OMNI-5600](https://solutions.lsretail.com/jira/browse/OMNI-5600)
- Changed LS Commerce web service from **SalesEntryGet**  to **SalesEntryGetSalesByOrderId** for fetching invoice and shipment transactions once the order is posted.  [19126](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/19126)


### Bugs/Fixes

- Fixed issue in syncing all customers to LS Central which were created from the admin panel during downtime. [OMNI-5583](https://solutions.lsretail.com/jira/browse/OMNI-5583)
- Fixed issue in syncing customers to the correct Commerce Service endpoint when multiple Commerce Service instances are being used for multiple stores in Magento. [OMNI-5583](https://solutions.lsretail.com/jira/browse/OMNI-5583)
- Fixed issue in basket calculation when items are added to the requisition list. [OMNI-5583](https://solutions.lsretail.com/jira/browse/OMNI-5583)
- Fixed issue in creating variant when one of the variant dimension has 0 value. [OMNI-5600](https://solutions.lsretail.com/jira/browse/OMNI-5600)
- Fixed issue with processing of data for price replication when qty for unit of measure comes as 1. [OMNI-5600](https://solutions.lsretail.com/jira/browse/OMNI-5600)
- Fix invoice rounding issue when order was placed in offline mode and synced later to LS Central. [16611](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/16611)
- Fixed issue in displaying the return transactions in the original order. [15374](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/15374)
- Fixed issue with sending total amount in partial payment webhook when gift card or loyalty points are being used on the order. [16292](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/16292)
- Fixed issue in adding additional validation during product replication when uom exists but respective records are missing in extended variants. [20374](https://dev.azure.com/dev-lsretail/LS%20Ecommerce/_workitems/edit/20374)




## [1.16.0] - 2022-03-07

### Added

- Added support for QR code ordering for Hospitality stores. [OMNI-5590](https://solutions.lsretail.com/jira/browse/OMNI-5590)
- Added support to send the QR code location information as part of order comments to LS Central. [OMNI-5561](https://solutions.lsretail.com/jira/browse/OMNI-5561)
- Added support to create/update shipment in Magento from LS Central through Webhook. This is now supported for both Backoffice and POS. [OMNI-5564](https://solutions.lsretail.com/jira/browse/OMNI-5564)
- Added support to create invoices in Magento through webhooks (from LS Central) for orders placed using offline payment methods. [OMNI-5469](https://solutions.lsretail.com/jira/browse/OMNI-5469)
- Added support to display return policy from LS Central on the product detail page. [OMNI-5477](https://solutions.lsretail.com/jira/browse/OMNI-5469)
- Added support to get return policy from LS Central on graphql Interface. [OMNI-5477](https://solutions.lsretail.com/jira/browse/OMNI-5469)
- Added support to close/complete the click & collect orders in Magento through webhooks (from LS Central) when items are marked as collected from POS. [OMNI-5478](https://solutions.lsretail.com/jira/browse/OMNI-5478)
- Added support to replicate and translate Item Description (Item HTML) in multiple languages from LS Central. [OMNI-5479](https://solutions.lsretail.com/jira/browse/OMNI-5479)
- Added new configuration in Magento Admin panel to calculate basket both in real-time and on checkout for cross border VAT calculation. [OMNI-5560](https://solutions.lsretail.com/jira/browse/OMNI-5560)
- Added WSDL location to support connectivity with SSL secure server. [OMNI-5577](https://solutions.lsretail.com/jira/browse/OMNI-5577)





### Bugs/Fixes

- Fixed issue in removing duplicate payment method entries on the frontend for customer order detail page. [OMNI-5581](https://solutions.lsretail.com/jira/browse/OMNI-5581)
- Fixed issue in updating or deleting items from cart when 'calculate once on checkout' option is enabled for calculation. [OMNI-5584](https://solutions.lsretail.com/jira/browse/OMNI-5584)
- Fixed issue with empty TaxItemGroupId as a part of product replication. [OMNI-5584](https://solutions.lsretail.com/jira/browse/OMNI-5584)
- Fixed issue in duplicate store entries on store listing page in case of multistore. [OMNI-5584](https://solutions.lsretail.com/jira/browse/OMNI-5584)
- Fixed issue in handling crappy/invalid data by adding additional checks for attributes and variants. [OMNI-5569](https://solutions.lsretail.com/jira/browse/OMNI-5569)
- Fixed issues while resetting individual items in Magento for reprocessing. [OMNI-5573](https://solutions.lsretail.com/jira/browse/OMNI-5573)
- Fixed issues while adding same UoM in multiple websites. [OMNI-5573](https://solutions.lsretail.com/jira/browse/OMNI-5573)
- Fixed issues while syncing item updates on multiple stores. [OMNI-5573](https://solutions.lsretail.com/jira/browse/OMNI-5573) 
- Fixed issue with Klarna payment method. [OMNI-5577](https://solutions.lsretail.com/jira/browse/OMNI-5577)
- Fixed issue of discount amount still shows up in cart total after removing all items from the cart. [OMNI-5577](https://solutions.lsretail.com/jira/browse/OMNI-5577)
- Fix issue in displaying customer order number on order history and recent order section once the order is posted in LS Central.  [OMNI-5586](https://solutions.lsretail.com/jira/browse/OMNI-5586)



## [1.15.0] - 2022-02-07

### Added

- Added configurable option in Magento admin panel to minimize unnecessary basket calculation requests to LS Central and sync basket once on checkout. [OMNI-5532](https://solutions.lsretail.com/jira/browse/OMNI-5532)
- Added support to calculate cross-border VAT from LS Central. [OMNI-5541](https://solutions.lsretail.com/jira/browse/OMNI-5541)
- Added support to replicate VAT/TAX rules in Magento for offline calculation (When connection to LS Central is down). [OMNI-5545](https://solutions.lsretail.com/jira/browse/OMNI-5545)  
- Added support for click & collect queries and mutation through the GraphQl interface. [OMNI-5493](https://solutions.lsretail.com/jira/browse/OMNI-5493)  
- Added configurable option in Magento admin panel to show only those stores for click and collect on checkout where all items are available. [OMNI-5398](https://solutions.lsretail.com/jira/browse/OMNI-5398)
- Added support to restrict orders from sending to LS Central which are in 'pending_review' status. [OMNI-5546](https://solutions.lsretail.com/jira/browse/OMNI-5546)
- Added support to signup and login using social media platforms. [OMNI-5499](https://solutions.lsretail.com/jira/browse/OMNI-5499)
- Added support to encrypt sensitive information when logging requests and responses in omniclient.log. [OMNI-5555](https://solutions.lsretail.com/jira/browse/OMNI-5555)
- Added support to cache WSDL configuration based on Magento mode (default and production). It will be WSDL_CACHE_NONE for developer mode. [OMNI-5556](https://solutions.lsretail.com/jira/browse/OMNI-5556)
- Added support to reset specific item(s) from the Magento admin panel for reprocessing. [OMNI-5513](https://solutions.lsretail.com/jira/browse/OMNI-5513)
- Added support to partially cancel items from LS Central through webhooks. [OMNI-5558](https://solutions.lsretail.com/jira/browse/OMNI-5558)



### Changed

- Use single AppID for all replication jobs per store to support replication counter in LS Central for SaaS. [OMNI-5547](https://solutions.lsretail.com/jira/browse/OMNI-5547)

### Bugs/Fixes

- Fixed issue in the session lost on GraphQl by storing basket calculation response in Magento quote tables. [OMNI-5559](https://solutions.lsretail.com/jira/browse/OMNI-5559)
- Fixed issues with order totals for orders which were created during downtime and created through the Magento admin panel. [OMNI-5474](https://solutions.lsretail.com/jira/browse/OMNI-5474)
- Fixed issue in formatting loyalty points when creating order from Magento admin panel. [OMNI-5538](https://solutions.lsretail.com/jira/browse/OMNI-5538)
- Fixed issue in VAT amount not returning on GraphQl interface. [OMNI-5557](https://solutions.lsretail.com/jira/browse/OMNI-5557)



## [1.14.0] - 2021-10-14

### Added

- Added support for LS Central on SaaS and Commerce Service on cloud. [OMNI-5510](https://solutions.lsretail.com/jira/browse/OMNI-5510)
- Added configurable option in Magento admin panel to choose if LS Central is being used on hybrid or on cloud. [OMNI-5510](https://solutions.lsretail.com/jira/browse/OMNI-5510)
- Added support to use single sourcing location for inventory in multiple stores. This is helpfull for partners who are using multiple eCommerce stores in LS Central but all are sharing the same inventory sourcing location [OMNI-5517](https://solutions.lsretail.com/jira/browse/OMNI-5517)
- Added support to replicate attributes per variant for configurable products. [OMNI-5521](https://solutions.lsretail.com/jira/browse/OMNI-5521)
- Added support to capture partial invoice from LS Central through web hooks. [OMNI-5426](https://solutions.lsretail.com/jira/browse/OMNI-5426)
- Added support for item availability check on GraphQl interface. [OMNI-5494](https://solutions.lsretail.com/jira/browse/OMNI-5494)
- Added support to sync shipping agent code and shipping agent service code when sending order information to LS Central. [OMNI-5530](https://solutions.lsretail.com/jira/browse/OMNI-5530)



### Changed

- Use unique AppID for each replication job per store to support replication counter in LS Central for SaaS. [OMNI-5510](https://solutions.lsretail.com/jira/browse/OMNI-5510)

### Bugs/Fixes

- Fixed issue in basket calculation not sent to LS Central through Magento REST API's. [OMNI-5521](https://solutions.lsretail.com/jira/browse/OMNI-5521)
- Fixed issue in grand total when generating invoice and credit memo in Magento. [OMNI-5426](https://solutions.lsretail.com/jira/browse/OMNI-5426)
- Fixed issue in item quantities in order status. [OMNI-5426](https://solutions.lsretail.com/jira/browse/OMNI-5426)



## [1.13.0] - 2021-09-03

### Added

- Added configurable option in Magento admin to dynamically map LS Central tender type(s) with Magento payment method(s). [OMNI-5430](https://solutions.lsretail.com/jira/browse/OMNI-5430)
- Added indexer for missing columns in replication tables to reduce fetch time. [OMNI-5460](https://solutions.lsretail.com/jira/browse/OMNI-5460)
- Added support for multi select attributes for products. [OMNI-5498](https://solutions.lsretail.com/jira/browse/OMNI-5498)
- Added support for single store mode configuration in Magento. [OMNI-5458](https://solutions.lsretail.com/jira/browse/OMNI-5458)
- Added support to calculate and log the time LS Central takes to respond to each eCom request. This is helpful for merchants who wants to debug the performance issues. [OMNI-5485](https://solutions.lsretail.com/jira/browse/OMNI-5485)



### Changed

- Added unique constraints on replication tables to reduce the possibility of crappy data. [OMNI-5496](https://solutions.lsretail.com/jira/browse/OMNI-5496)

### Bugs/Fixes

- Fixed compatibility issues with Laminas Library on Magento 2.4.3. [OMNI-5458](https://solutions.lsretail.com/jira/browse/OMNI-5458)
- Fixed issue with displaying quantity warning in update basket when a real time inventory check is enabled. [OMNI-5476](https://solutions.lsretail.com/jira/browse/OMNI-5476)
- Fix issue with displaying payment method information on order detail page. [OMNI-5482](https://solutions.lsretail.com/jira/browse/OMNI-5482)
- Fixed issue in sending order cancelling request to LS Central on Store front. [OMNI-5489](https://solutions.lsretail.com/jira/browse/OMNI-5489)



## [1.12.0] - 2021-07-23

### Added

- Added admin configuration to support order cancellation from frontend. [OMNI-5416](https://solutions.lsretail.com/jira/browse/OMNI-5416)
- Added support for LS Commerce Service v4.20. [OMNI-5461](https://solutions.lsretail.com/jira/browse/OMNI-5461)
- Added support to allow order cancellation and generating credit memo from LS Central through webhook. [OMNI-5145](https://solutions.lsretail.com/jira/browse/OMNI-5145)
- Added support to allow shipment creation from LS Central through webhook. [OMNI-5450](https://solutions.lsretail.com/jira/browse/OMNI-5450)
- Added support for partial shipment based on item + qty's. [OMNI-5450](https://solutions.lsretail.com/jira/browse/OMNI-5450)
- Added support to notify customers through emails for click and collect orders (ready to pick or collected) from LS Central through webhook. [OMNI-5451](https://solutions.lsretail.com/jira/browse/OMNI-5451)
- Added support to display order kitchen status in order success page for hospitality store. [OMNI-5456](https://solutions.lsretail.com/jira/browse/OMNI-5456)
- Added admin configuration to choose tax group for shipping item. [OMNI-5466](https://solutions.lsretail.com/jira/browse/OMNI-5466)
- Added support to split item total price including tax when sending order to LS Central. [OMNI-5466](https://solutions.lsretail.com/jira/browse/OMNI-5466)
- Added support to reset replication data for specific store. [OMNI-5446](https://solutions.lsretail.com/jira/browse/OMNI-5446)


### Changed

- Rename HospOrderKotStatus to HospOrderStatus. [OMNI-5461](https://solutions.lsretail.com/jira/browse/OMNI-5461)
- Remove HospitalityMode and replaced with IsHospitality, SalesType for Onelist and Order request to LS Central. [OMNI-5461](https://solutions.lsretail.com/jira/browse/OMNI-5461)

### Bugs/Fixes

- Fixed issue with item lines not appearing on order detail page after posting the order. [OMNI-5473](https://solutions.lsretail.com/jira/browse/OMNI-5473)
- Fixed issue in customer/checkout session on GraphQl which was causing unexpected behavior on logout. [OMNI-5480](https://solutions.lsretail.com/jira/browse/OMNI-5480)
- Fixed issue with subtotal values in PayPal module. [OMNI-5472](https://solutions.lsretail.com/jira/browse/OMNI-5472)
- Fixed issue in order detail page not loading in offline mode. [OMNI-5483](https://solutions.lsretail.com/jira/browse/OMNI-5483)
- Fixed issue in displaying billing address in order detail page. [OMNI-5483](https://solutions.lsretail.com/jira/browse/OMNI-5483)
- Fixed issue in recent orders not displaying in offline mode. [OMNI-5483](https://solutions.lsretail.com/jira/browse/OMNI-5483)



## [1.11.1] - 2021-06-10

### Bugs/Fixes

- Fixed issue in inventory lookup for UoM based products. [OMNI-5463](https://solutions.lsretail.com/jira/browse/OMNI-5463)



## [1.11.0] - 2021-06-09

### Added

- Added support to allow gift card/loyalty points to be applied on whole order including the shipping amount. [OMNI-5454](https://solutions.lsretail.com/jira/browse/OMNI-5454)
- Added support for replication of items/variants with special characters in it. Earlier we were not supporting replication of items with hyphen in it. [OMNI-5425](https://solutions.lsretail.com/jira/browse/OMNI-5425)
- Added support to store tax informaiton in Magento once the basket is being calculated from LS Central. [OMNI-5429](https://solutions.lsretail.com/jira/browse/OMNI-5429)
- Added support to sync order cancellation from admin panel for hospitality stores. [OMNI-5438](https://solutions.lsretail.com/jira/browse/OMNI-5438)
- Added support to display order kitchen status in order detail page for hospitality store. [OMNI-5441](https://solutions.lsretail.com/jira/browse/OMNI-5441)
- Added support to reset replication data for specific store. [OMNI-5446](https://solutions.lsretail.com/jira/browse/OMNI-5446)


### Changed

- Change UnitPrice to UnitPriceInclTax in Item price replication. [OMNI-5428](https://solutions.lsretail.com/jira/browse/OMNI-5428)

### Bugs/Fixes

- Fixed issue in syncing order from admin panel for hospitality stores. [OMNI-5434](https://solutions.lsretail.com/jira/browse/OMNI-5434)
- Fixed issue in applying loyalty points on checkout due to session issue. [OMNI-5453](https://solutions.lsretail.com/jira/browse/OMNI-5453)
- Fixed the layout of order items in order detail page for hospitality stores. [OMNI-5453](https://solutions.lsretail.com/jira/browse/OMNI-5453)
- Fixed issue in replication of modifiers and recipes once we reset the data from admin. [OMNI-5453](https://solutions.lsretail.com/jira/browse/OMNI-5453)
- Fixed issue of associative array while updating customer account through GraphQl. [OMNI-5425](https://solutions.lsretail.com/jira/browse/OMNI-5425)



## [1.10.0] - 2021-05-07

### Added

- Added indexer in all the replication table. [OMNI-5424](https://solutions.lsretail.com/jira/browse/OMNI-5424)
- New mutation in GraphQl to support adding/updating/removing of loyalty points. [OMNI-5376](https://solutions.lsretail.com/jira/browse/OMNI-5376)
- Added customer meta information (cardId, lsr_username,LSR ID, Member Account Id, sales history) in the customer interface in GraphQl. [OMNI-5419](https://solutions.lsretail.com/jira/browse/OMNI-5419)
- Added document_id in response of sales_entry data of Customer Query in GraphQl. [OMNI-5432](https://solutions.lsretail.com/jira/browse/OMNI-5432)
- Added support to fire basket calculation from LS Central for reorder through GraphQl Interface. [OMNI-5399](https://solutions.lsretail.com/jira/browse/OMNI-5399)
- Added support to choose multiple modifiers in the same group for specific product. [OMNI-5421](https://solutions.lsretail.com/jira/browse/OMNI-5421)


### Changed

- Changed config retrieving through collection objects to retrieve real time config value and avoid cache issue. [OMNI-5433](https://solutions.lsretail.com/jira/browse/OMNI-5433)
- Changed the logic to sync Customer account firstname and lastname with LS Central instead of Customer Address firstname and lastname. [OMNI-5419](https://solutions.lsretail.com/jira/browse/OMNI-5419)
- Changed the logic to sync default billing address with LS Central instead of default shipping address. [OMNI-5419](https://solutions.lsretail.com/jira/browse/OMNI-5419)

### Bugs/Fixes

- Improve replication performance by writing the config values only when the new values are different from the existing one. [OMNI-5424](https://solutions.lsretail.com/jira/browse/OMNI-5424)
- Fixed issue in merging the defaultonelist when the shopping carts are merged after customer login. [OMNI-5423](https://solutions.lsretail.com/jira/browse/OMNI-5423)
- Fixed issue with synchronization of empty attribute values in Magento. [OMNI-5436](https://solutions.lsretail.com/jira/browse/OMNI-5436)
- Fixed customer registration issue when "Require email confirmation"  is set to true. [OMNI-5439](https://solutions.lsretail.com/jira/browse/OMNI-5439)



## [1.9.0] - 2021-03-23

### Added

- Added support for replication and synchronization of hospitality deals. [OMNI-5350](https://solutions.lsretail.com/jira/browse/OMNI-5350)
- Added support for basket calculation and order creation for hospitality deals. [OMNI-5351](https://solutions.lsretail.com/jira/browse/OMNI-5351)
- Added support for Omni 4.16.x. [OMNI-5413](https://solutions.lsretail.com/jira/browse/OMNI-5413)
- Added support to sync order cancellation request to LS Central from Magento admin panel.[OMNI-4759](https://solutions.lsretail.com/jira/browse/OMNI-4759)
- Added support to add/update/remove Giftcard through GraphQl. [OMNI-5375](https://solutions.lsretail.com/jira/browse/OMNI-5375)
- Added support to redeem/apply LS Central coupon through GraphQl Interface. [OMNI-5393](https://solutions.lsretail.com/jira/browse/OMNI-5393)
- Added support for Magento 2.4.2 and Composer V2. [OMNI-5401](https://solutions.lsretail.com/jira/browse/OMNI-5401)


### Changed

- Replace [LoginWeb](http://mobiledemo.lsretail.com/LSOmniHelp/html/M_LSOmni_Service_IUCService_LoginWeb.htm) with [Login](http://mobiledemo.lsretail.com/LSOmniHelp/html/M_LSOmni_Service_IUCService_Login.htm) for all the authentication calls sent to LS Central.

### Bugs/Fixes

- Fixed issue in merging the shopping cart after customer login. [OMNI-5404](https://solutions.lsretail.com/jira/browse/OMNI-5404)
- Fixed issue in automatically assigning new variant frameworks into the existing attribute set. [OMNI-5396](https://solutions.lsretail.com/jira/browse/OMNI-5396)
- Fixed totals in partial invoice and partial refund when giftcard or loyalty points are used as part of payment. [OMNI-5403](https://solutions.lsretail.com/jira/browse/OMNI-5403)
- Fixed issue in automatically adding new variant framework to existing products. [OMNI-5408](https://solutions.lsretail.com/jira/browse/OMNI-5408)
- Fixed issue in posting the order from LS Central when invoice is already created in Magento. [OMNI-5406](https://solutions.lsretail.com/jira/browse/OMNI-5406)
- Github Issue [#35](https://github.com/lsretailomni/lsmag-two/issues/35)
- Github Issue [#36](https://github.com/lsretailomni/lsmag-two/issues/36)



## [1.8.0] - 2020-02-05

### Added

- Added support to sync customer to LS Central created through GraphQl interface. [OMNI-5371](https://solutions.lsretail.com/jira/browse/OMNI-5371)
- Added support to authenticate login from LS Central using GraphQl interface. [OMNI-5372](https://solutions.lsretail.com/jira/browse/OMNI-5372)
- Added support to sync customer operation (account update, address creation, address update, password change, password reset) to LS Central through GraphQl interface. [OMNI-5373](https://solutions.lsretail.com/jira/browse/OMNI-5373)
- Added support to calculate basket from LS Central through GraphQl interface. [OMNI-5374](https://solutions.lsretail.com/jira/browse/OMNI-5374)
- Added support to sync order to LS Central through GraphQl interface. [OMNI-5389](https://solutions.lsretail.com/jira/browse/OMNI-5389)
- Added support to accept full payment of order made through loyalty points or gift card or incombination of those. [OMNI-5389](https://solutions.lsretail.com/jira/browse/OMNI-5389)
 
### Bugs/Fixes

- Fixed issue for constructor paramter in invoice object. [OMNI-5390](https://solutions.lsretail.com/jira/browse/OMNI-5390)



## [1.7.0] - 2021-01-13

### Added

- Added support for [LS eCommerce for hospitality.](https://github.com/lsretailomni/hospitality)
- Added support for Omni v4.14.x [OMNI-5340](https://solutions.lsretail.com/jira/browse/OMNI-5340)
 
### Bugs/Fixes

- Fixed issue in handling the crappy data for Hierarchy nodes - Adding a condition in order to make sure we only update the child category when we do have the parent for it for crappy and inconsistent data. [OMNI-5364](https://solutions.lsretail.com/jira/browse/OMNI-5364)
- Github Issue [#28](https://github.com/lsretailomni/lsmag-two/issues/28)
- Github Issue [#30](https://github.com/lsretailomni/lsmag-two/issues/30)
- Github Issue [#32](https://github.com/lsretailomni/lsmag-two/issues/32)
- Github Issue [#27](https://github.com/lsretailomni/lsmag-two/issues/27)



## [1.6.1] - 2020-11-23

### Added

- Added support to replicate and display vendor information on product detail page. [OMNI-5177](https://solutions.lsretail.com/jira/browse/OMNI-5177)
 
### Bugs/Fixes

- Fixed issue with syncing order to LS Order when the order payment is declined. [OMNI-5348](https://solutions.lsretail.com/jira/browse/OMNI-5348)
- Fixed issue in processing deleted product attribute values in Magento. [OMNI-5348](https://solutions.lsretail.com/jira/browse/OMNI-5348)



## [1.6.0] - 2020-11-19

### Added

- Added support to dynamically create attribute sets based on Item Category code or Product Group ID. [OMNI-5319](https://solutions.lsretail.com/jira/browse/OMNI-5319)
- Added configurable option in the admin panel to choose attribute set configuration. [OMNI-5321](https://solutions.lsretail.com/jira/browse/OMNI-5321)
- Added support for item distribution and prices based on multiple Unit of Measures (UoM). [OMNI-5295](https://solutions.lsretail.com/jira/browse/OMNI-5295)
- Added support to save Member Birthday & Gender in Magento and sync back to LS Central. [OMNI-5302](https://solutions.lsretail.com/jira/browse/OMNI-5302)
- Added BlockedOnEcommerce option in the Item Grid in the backend and in grid Filter. [OMNI-5318](https://solutions.lsretail.com/jira/browse/OMNI-5318)
 
### Changed

- Now all items will be assigned to the dynamic attribute set created against the Item Category code or Product Group ID instead of default attribute set.
- Now the same product can be available and sold in multiple Unit of Measures.

### Bugs/Fixes

- Fixed issue with resetting and deleting data using table prefix. [OMNI-5322](https://solutions.lsretail.com/jira/browse/OMNI-5322)
- Fixed stock and hierarchy code issues when a multi store with different industries is configured in a same magento instance. [OMNI-5323](https://solutions.lsretail.com/jira/browse/OMNI-5323)
- Fixed issues with hierarchy data translation in multi store instance. [OMNI-5324](https://solutions.lsretail.com/jira/browse/OMNI-5324)
- Fixed issue in syncing order to LS Central using PayPal express checkout. [OMNI-5336](https://solutions.lsretail.com/jira/browse/OMNI-5336)
- Fixed issues with product images not showing on product listing page / Search results. [OMNI-5340](https://solutions.lsretail.com/jira/browse/OMNI-5340)



## [1.5.1] - 2020-10-05

### Bugs/Fixes

- Fixed issue with replication of product status during first time replication. [OMNI-5318](https://solutions.lsretail.com/jira/browse/OMNI-5318)



## [1.5.0] - 2020-09-30

### Added

- Added compatibility for Magento 2.4.0 version [OMNI-5282](https://solutions.lsretail.com/jira/browse/OMNI-5282)
- Added support to control the status (enable/disable) of product from LS Central. [OMNI-5178](https://solutions.lsretail.com/jira/browse/OMNI-5178)
- Added support for data translation of product categories (hierarchy nodes) from LS Central. [OMNI-5269](https://solutions.lsretail.com/jira/browse/OMNI-5269)
- Added support for data translation of product name from LS Central. [OMNI-5296](https://solutions.lsretail.com/jira/browse/OMNI-5296)
- Added support for data translation of product attributes and product attributes option from LS Central. [OMNI-5294](https://solutions.lsretail.com/jira/browse/OMNI-5294)
- Added admin interface to dynamically control the shipping item Id from Magento admin panel. [OMNI-5299](https://solutions.lsretail.com/jira/browse/OMNI-5299)
- Added policies to whitelist external resources like fonts, scripts, images, styles being used in the repository using csp_whitelist.xml. [OMNI-5287](https://solutions.lsretail.com/jira/browse/OMNI-5287)
 
### Changed

- Added exception returned from LS Central when running manual cron from the admin panel. [OMNI-5291](https://solutions.lsretail.com/jira/browse/OMNI-5291)
- Removed unused less file being loaded from the layout.xml. [OMNI-5293](https://solutions.lsretail.com/jira/browse/OMNI-5293)
- Removed username field from the customer registration form on frontend. [OMNI-5272](https://solutions.lsretail.com/jira/browse/OMNI-5272)

### Bugs/Fixes

- Fixed issue with the creation of configuratble products on Magento 2.4.0 version. [OMNI-5281](https://solutions.lsretail.com/jira/browse/OMNI-5281)
- Fixed anchor link for LS Retail setup section appear on the admin notice. [OMNI-5284](https://solutions.lsretail.com/jira/browse/OMNI-5284)
- Fixed issue when empty scheme Id is returned from LS Central. [OMNI-5301](https://solutions.lsretail.com/jira/browse/OMNI-5301)



## [1.4.1] - 2020-08-24

### Changed

- As part of disaster management Inventory lookup on Product display page, LS Recommend, Click & Collect, Coupon recommendations together with there api calls will be disabled if omni goes down. [OMNI-5250](https://solutions.lsretail.com/jira/browse/OMNI-5250)

### Bugs/Fixes

- Fixed issue with maps not showing on product and checkout page. [OMNI-5288](https://solutions.lsretail.com/jira/browse/OMNI-5288)
- Added missing table columns while creating dynamic db_schema.xml. [OMNI-5285](https://solutions.lsretail.com/jira/browse/OMNI-5285)



## [1.4.0] - 2020-08-18

### Added

- Applied datatables library from jquery for customer order history to get sorting, pagination and search to work. [OMNI-5229](https://solutions.lsretail.com/jira/browse/OMNI-5229)
- Customer can proceed with both normal and ajax login even if omni is unreachable and is timed out as part of disaster recovery. [OMNI-5206](https://solutions.lsretail.com/jira/browse/OMNI-5206)
- Added timeout value which is configurable from admin. Customer can place the order on the checkout even if omni is not reachable and is timed out as part of disaster recovery. [OMNI-5209](https://solutions.lsretail.com/jira/browse/OMNI-5209)
- Customer can proceed with customer registration even if omni is unreachable and is timed out. [OMNI-5207](https://solutions.lsretail.com/jira/browse/OMNI-5207)
- As part of disaster recovery, handle basket calculation from Magento if LS Omni is not responding. [OMNI-5208](https://solutions.lsretail.com/jira/browse/OMNI-5208)
- Support to Sync Customer from Magento Admin, also added separate tab for membership information. [OMNI-5136](https://solutions.lsretail.com/jira/browse/OMNI-5136)
- Email notification to admin if omni service is down, synchronize customers and orders using cron job. [OMNI-5228](https://solutions.lsretail.com/jira/browse/OMNI-5228)
- Allow user to use same password that was created when Omni Service is down. [OMNI-5262](https://solutions.lsretail.com/jira/browse/OMNI-5262)
- Added configurable option for different industry such as Retail, Hospitality. [OMNI-5254](https://solutions.lsretail.com/jira/browse/OMNI-5254)
 
### Changed

- Made things like loyalty points, giftcard, coupon & coupon recommendations, loyalty elements on customer dashboard and signup/login notice on product display page configurable from the admin panel. [OMNI-5217](https://solutions.lsretail.com/jira/browse/OMNI-5217)
- Convert all CSS to LESS, format LESS according to Magento Standard and remove unnecessary CSS classes. [OMNI-5239](https://solutions.lsretail.com/jira/browse/OMNI-5239)
- Converted old schema install/upgrade scripts to declarative schema approach. [OMNI-5251](https://solutions.lsretail.com/jira/browse/OMNI-5251)
- Migrate all Install/Upgrade data classes into data patches. [OMNI-5252](https://solutions.lsretail.com/jira/browse/OMNI-5252)
- Support dynamic generation of declarative schema for all the replication tables. [OMNI-5253](https://solutions.lsretail.com/jira/browse/OMNI-5253)
- Replaced Zend Framework deprecated classes with Laminas Framework. [OMNI-5273](https://solutions.lsretail.com/jira/browse/OMNI-5273)

### Bugs/Fixes

- Incorporate multiple normal store hours of a day. [OMNI-5230](https://solutions.lsretail.com/jira/browse/OMNI-5230)
- Fixed issue with Document Id in order email. [OMNI-5236](https://solutions.lsretail.com/jira/browse/OMNI-5236)
- Fixed pay at store payment method visibility on checkout page for flat shipping method. [OMNI-5238](https://solutions.lsretail.com/jira/browse/OMNI-5238)
- Fixed issue with coupons not showing on cart page for configurable product. [OMNI-5232](https://solutions.lsretail.com/jira/browse/OMNI-5232)
- Fixed error on order success page that comes for offline payment methods. [OMNI-5267](https://solutions.lsretail.com/jira/browse/OMNI-5267)



## [1.3.2] - 2020-06-17

### Added

- Now basket calculation will works for the order created from admin panel and any order created from Magento admin panel will now also be sync to LS Central as well. [OMNI-4994](https://solutions.lsretail.com/jira/browse/OMNI-4994)
- Now you can try to make order request to Ls Central from order detail at admin panel if for some reason the request could not be completed on the frontend as part of disaster recovery. [OMNI-5163](https://solutions.lsretail.com/jira/browse/OMNI-5163)
- Added additional store information on map in product and checkout page. [OMNI-5215](https://solutions.lsretail.com/jira/browse/OMNI-5215)
- Added additional validation rules to handle duplicate attribute option values and crappy data. [OMNI-5204](https://solutions.lsretail.com/jira/browse/OMNI-5204)
- Added the validation for Username/Email before sending data to LS Central for customer login/registration. [OMNI-5202](https://solutions.lsretail.com/jira/browse/OMNI-5202)
 
### Changed

- Real-time request to load the Hierarchy Code list during the web store setup. [OMNI-5199](https://solutions.lsretail.com/jira/browse/OMNI-5199)

### Bugs/Fixes

- Resolved issue for inventory lookup in physical stores on product detail page. [OMNI-5203](https://solutions.lsretail.com/jira/browse/OMNI-5203)
- Resolve issue for the synchronization of Hierarchy images once its updated from LS Central. [OMNI-5194](https://solutions.lsretail.com/jira/browse/OMNI-5194)



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
