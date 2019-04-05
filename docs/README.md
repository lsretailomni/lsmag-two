LS eCommerce - Magento

# Table of Contents

 1. Introduction
 2. Installation
 3. Usage
 4. Troubleshooting
 
# Introduction
The purpose of this document is to give an overview of LS eCommerce - Magento, the Magento 2 extension part of the LS Retail’s Omni Solution.

The first chapter, *Introduction*, contains a high-level overview of the plugin and its functionalities.   
The second chapter, *Installation*, explains how to install the plugin and the initial configuration steps to get up & running.   
The third chapter, *Usage*, goes in detail over the features of the plugin and their configurations.
In the fourth chapter, *Troubleshooting*, we provide solutions and help for some common problems.

## LS Omni Solution overview
The LS Omni Solution consists of the LS Omni Service which uses the LS Omni DB and the LS Central’s retail web services and the clients, the apps, loyalty portal and e-commerce platforms.  
LS eCommerce particularly consumes the SOAP eCommerceService interface; other clients consume other interfaces exposed by the LS Omni Service.
![](resources/component.svg)

## About LS eCommerce - Magento

### LS eCommerce - Magento is a Magento 2 extension

LS eCommerce - Magento is a Magento extension that integrates with LS Central, allowing web retailers to leverage crucial operations to LS Central, i.e. discount calculations, order life cycle management.   
The extension will populate the web store’s catalog with items coming from LS Central and manage the life cycle of the orders created in Magento mirroring the status updates coming from LS Central.  
LS eCommerce - Magento has been follows the best standards available for the Magento community.  
LS eCommerce - Magento is compatible with Magento Open Source 2.2.x and 2.3.x.

### LS eCommerce - Magento is an LS Omni Service client

The LS Omni Service is a WCF web service running on an IIS web server.
LS eCommerce uses the LS Omni Service’s SOAP eCommerceService.
The LS Omni Service will call LS Central’s web services on behalf of Magento. Also will serve content already stored in the LS Omni Database. 

### What LS eCommerce - Magento does

* Replication
  - LS eCommerce - Magento populates the Magento catalog (products and categories) using the replication endpoints on the LS Omni Service.
  - LS eCommerce - Magento keeps track of the last *preaction* fetching only delta updates.
  - Some of the data from LS Omni Service is stored in Magento using custom-made, non-native entities used by the plugin.

* Member Management
  - LS Central’s members have immediate access to the Magento web store using the same LS Central’s credentials.
  - LS eCommerce - Magento enables member registration through Magento.
  - Loyalty information will be shown in Magento on the account’s dashboard, i.e. points balance, club.

* Cart Integration
  - The Magento cart is mirrored in LS Omni as a OneList.
  - The cart totals are calculated directly on LS Central and then updated in Magento.
  - LS Omni’s OneLists are shared with all LS Omni’s clients, i.e. the loyalty app. 

* Order Management
  - LS eCommerce - Magento will register an order and push it back to LS Central.
  - LS eCommerce - Magento updates the Magento’s order state by polling changes directly from LS Central.
  - LS eCommerce - Magento can create two types of web orders:
    - Sales Order: orders with payment & shipment information.
    - Special Order: orders using Click & Collect shipment method.

### Dependencies

* Magento Open Source 2.2.x or 2.3.x
  - [Magento 2.2.x technology stack requirements](https://devdocs.magento.com/guides/v2.2/install-gde/system-requirements-tech.html)
  - [Magento 2.3.x technology stack requirements](https://devdocs.magento.com/guides/v2.3/install-gde/system-requirements-tech.html)
* LS Omni Server 3.6 or later
* LS Central 13.x
* Composer
* Git

