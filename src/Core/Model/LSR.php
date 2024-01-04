<?php

namespace Ls\Core\Model;

use \Ls\Core\Model\Data;
use Ls\Omni\Client\OperationInterface;
use \Ls\Omni\Service\ServiceType;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * LSR Model
 *
 */
class LSR
{
    const LSR_INVALID_MESSAGE = '<strong>LS Retail Setup Incomplete</strong><br/>
Please define the LS Retail Service Base URL and Web Store to proceed.<br/>
Go to Stores > Configuration > LS Retail > General Configuration.';
    const EXTENSION_COMPOSER_PATH_VENDOR = "vendor/lsretail/lsmag-two/composer.json";
    const EXTENSION_COMPOSER_PATH_APP = "app/code/lsretail/lsmag-two/composer.json";
    const CRON_STATUS_PATH_PREFIX = 'ls_mag/replication/status_';
    const URL_PATH_EXECUTE = 'ls_repl/cron/grid';

    // DEFAULT IMAGE SIZE
    const DEFAULT_IMAGE_WIDTH = 500;
    const DEFAULT_IMAGE_HEIGHT = 500;

    // DEFAULT ITEM IMAGE SIZE
    const DEFAULT_ITEM_IMAGE_HEIGHT = 0;
    const DEFAULT_ITEM_IMAGE_WIDTH = 0;

    // CACHE PATHS
    const CACHE_OMNICLIENT_OPERATIONDATA_PREFIX = 'lsr-oc-od-{@1}';
    const CACHE_OMNISERVICEABSTRACT_OPTIONS_PREFIX = 'lsr-osa-o-{@1}';
    const CACHE_DOMAIN_ITEM_PREFIX = 'lsr-d-i-{@1}';
    const CACHE_PRODUCTGROUP_HASH_PREFIX = 'lsr-pg-h-{@1}';
    const CACHE_CONTACT_OFFERS_PREFIX = 'lsr-c-o-{@1}';
    const CACHE_CONTACT_COUPONS_PREFIX = 'lsr-c-c-{@1}';
    const CACHE_CONTACT_TRANSACTIONS_PREFIX = 'lsr-c-tx-{@1}';
    const CACHE_CONTACT_TRANSACTION_PREFIX = 'lsr-c-tnx-{@1}';
    const CACHE_CONTACT_ADVERTISEMENTS_PREFIX = 'lsr-adv-a-{@1}';
    const CACHE_CONTACT_CLUB_PREFIX = 'lsr-cl-id-{@1}';
    const CACHE_CONTACT_PROFILE_PREFIX = 'lsr-prf-id-{@1}';
    const CACHE_COREOBSERVER_WSDLCHANGE = 'lsr-co-wc';
    const CACHE_ADMINHTML_CONFIGURATIONWATCHER_PREFIX = 'lsr-ah-cw-{@1}';
    const CACHE_CONFIGDATA_WATCHES = 'lsr-cd-w';
    const CACHE_PROCESS_CHECK_PREFIX = 'lsr-p-c-{@1}';
    const CACHE_STORE_ENABLED_PREFIX = 'lsr-s-e-{@1}';
    const CACHE_NAV_PROFILE = 'lsr-n-p';
    const CACHE_CUSTOMER_SYNCHRONIZE_SESSID_PREFIX = 'lsr-c-s-sid-{@1}';
    const CACHE_OMNICLIENT_TOKENIZED_OPERATION_PREFIX = 'lsr-oc-t-o-{@1}';

    // STORE CONFIGURATION PATHS
    // SYSTEM CONFIG
    const SC_SYSTEM_SYMLINK = 'dev/template/allow_symlink';

    // ENABLE
    const SC_MODULE_ENABLED = 'ls_mag/ls_enable/enabled';

    // SERVICE
    const SC_SERVICE_ENABLE = 'ls_mag/service/enabled';
    const SC_SERVICE_BASE_URL = 'ls_mag/service/base_url';
    const SC_SERVICE_LS_KEY = 'ls_mag/service/ls_key';
    const SC_SERVICE_STORE = 'ls_mag/service/selected_store';
    const SC_SERVICE_DEBUG = 'ls_mag/service/debug';
    const SC_SERVICE_TOKENIZED = 'ls_mag/service/tokenized_operations';
    const SC_SERVICE_TIMEOUT = 'ls_mag/service/timeout';
    const SC_SERVICE_VERSION = 'ls_mag/service/version';
    const SC_SERVICE_LS_CENTRAL_VERSION = 'ls_mag/service/ls_central_version';

    // REPLICATION
    const SC_REPLICATION_GETCATEGORIES = 'ls_mag/replication/replicate_category';
    const SC_REPLICATION_HIERARCHY_CODE = 'ls_mag/service/replicate_hierarchy_code';
    const SC_REPLICATION_CREATEATTRSET = 'ls_mag/replication/create_attribute_set';
    const SC_REPLICATION_CATEGORIZE = 'ls_mag/replication/categorize_products';
    const SC_REPLICATION_BATCHSIZE = 'ls_mag/replication/batch_size_configuration';
    const SC_REPLICATION_CRONEXPR = 'ls_mag/replication/cron_expr_configuration';
    const SC_REPLICATION_VARIANTMAP = 'ls_mag/replication/variant_map';
    const SC_REPLICATION_CATEGORYPATH = 'ls_mag/replication/category_path';
    const SC_REPLICATION_DEBUGONERROR = 'ls_mag/replication/debug_on_error';
    const SC_REPLICATION_CRONEXPR_PREFIX = 'ls_mag/replication/cron_expr_{@1}';
    const SC_REPLICATION_BATCHSIZE_PREFIX = 'ls_mag/replication/batch_size_{@1}';
    const SC_REPLICATION_DEFAULT_ITEM_IMAGE_WIDTH = 'ls_mag/replication/item_image_width';
    const SC_REPLICATION_DEFAULT_ITEM_IMAGE_HEIGHT = 'ls_mag/replication/item_image_height';
    const SC_REPLICATION_DEFAULT_BATCHSIZE = 'ls_mag/replication/default_batch_size';
    const SC_REPLICATION_PRODUCT_BATCHSIZE = 'ls_mag/replication/product_batch_size';
    const SC_REPLICATION_PRODUCT_ATTRIBUTE_BATCH_SIZE = 'ls_mag/replication/product_attribute_batch_size';
    const SC_REPLICATION_DISCOUNT_BATCH_SIZE = 'ls_mag/replication/discount_batch_size';
    const SC_REPLICATION_PRODUCT_INVENTORY_BATCH_SIZE = 'ls_mag/replication/product_inventory_batch_size';
    const SC_REPLICATION_PRODUCT_PRICES_BATCH_SIZE = 'ls_mag/replication/product_prices_batch_size';
    const SC_REPLICATION_PRODUCT_IMAGES_BATCH_SIZE = 'ls_mag/replication/product_images_batch_size';
    const SC_REPLICATION_PRODUCT_BARCODE_BATCH_SIZE = 'ls_mag/replication/product_barcode_batch_size';
    const SC_REPLICATION_VARIANT_BATCH_SIZE = 'ls_mag/replication/variant_batch_size';
    const SC_REPLICATION_PRODUCT_ASSIGNMENT_TO_CATEGORY_BATCH_SIZE =
        'ls_mag/replication/product_assignment_to_category_batch_size';
    const SC_REPLICATION_ALL_STORES_ITEMS = 'ls_mag/replication/replicate_all_stores_items';
    const SC_REPLICATION_MANUAL_CRON_GRID_DEFAULT_WEBSITE = 'ls_mag/replication/manual_cron_grid_default_website';
    const SC_REPLICATION_IDENTICAL_TABLE_WEB_SERVICE_LIST = 'ls_mag/replication/identical_table_web_service_list';
    const SC_REPLICATION_ATTRIBUTE_SETS_MECHANISM = 'ls_mag/replication/attribute_sets_mechanism';
    const GIFT_CARD_IDENTIFIER = 'ls_mag/replication/gift_card_items_list';
    //Attribute Set
    const SC_REPLICATION_ATTRIBUTE_SET_ITEM_CATEGORY_CODE = 'ITEM_CATEGORY_CODE';
    const SC_REPLICATION_ATTRIBUTE_SET_PRODUCT_GROUP_ID = 'PRODUCT_GROUP_ID';
    const SC_REPLICATION_ATTRIBUTE_SET_SOFT_ATTRIBUTES_GROUP = 'LS Central Attributes';
    const SC_REPLICATION_ATTRIBUTE_SET_VARIANTS_ATTRIBUTES_GROUP = 'LS Central Variants';
    const SC_REPLICATION_ATTRIBUTE_SET_EXTRAS = 'Extras';

    //check for Attribute
    const SC_SUCCESS_CRON_ATTRIBUTE = 'ls_mag/replication/success_repl_attribute';
    const SC_CRON_ATTRIBUTE_CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_attributes';
    const ATTRIBUTE_OPTION_VALUE_SORT_ORDER = 10000;

    //check for Attribute Variant
    const SC_SUCCESS_CRON_ATTRIBUTE_VARIANT = 'ls_mag/replication/success_repl_attribute_variant';

    //check for Standard Attribute Variant
    const SC_SUCCESS_CRON_ATTRIBUTE_STANDARD_VARIANT = 'ls_mag/replication/success_repl_attribute_standard_variant';

    //check for Category
    const SC_SUCCESS_CRON_CATEGORY = 'ls_mag/replication/success_repl_category';
    const SC_CRON_CATEGORY_CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_category';

    //check for Product
    const SC_SUCCESS_CRON_PRODUCT = 'ls_mag/replication/success_repl_product';
    const SC_CRON_PRODUCT_CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_products';

    //check for Product Price
    const SC_SUCCESS_CRON_PRODUCT_PRICE = 'ls_mag/replication/success_sync_price';
    const SC_PRODUCT_PRICE_CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_price_sync';

    //check for Product Inventory
    const SC_SUCCESS_CRON_PRODUCT_INVENTORY = 'ls_mag/replication/success_sync_inventory';
    const SC_PRODUCT_INVENTORY_CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_inventory_sync';

    //check for Discount
    const SC_SUCCESS_CRON_DISCOUNT = 'ls_mag/replication/success_repl_discount';
    const SC_CRON_DISCOUNT_CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_discount_create';
    const SC_SUCCESS_CRON_DISCOUNT_SETUP = 'ls_mag/replication/success_repl_discount_setup';
    const SC_SUCCESS_CRON_DISCOUNT_VALIDATION = 'ls_mag/replication/success_repl_discount_validation';
    const SC_CRON_DISCOUNT_CONFIG_PATH_LAST_EXECUTE_SETUP = 'ls_mag/replication/last_execute_repl_discount_create_setup';

    //check for Product Assignment to Categories
    const SC_SUCCESS_CRON_ITEM_UPDATES = 'ls_mag/replication/success_sync_item_updates';
    const SC_ITEM_UPDATES_CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_item_updates_sync';

    //check for Product Images
    const SC_SUCCESS_CRON_ITEM_IMAGES = 'ls_mag/replication/success_sync_item_images';
    const SC_ITEM_IMAGES_CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_item_images_sync';

    //check for Product Attributes Value Sync
    const SC_SUCCESS_CRON_ATTRIBUTES_VALUE = 'ls_mag/replication/success_sync_attributes_value';

    //execute time for sync attributes value
    const LAST_EXECUTE_REPL_SYNC_ATTRIBUTES_VALUE = 'ls_mag/replication/last_execute_repl_attributes_value_sync';

    //check for Vendor Attributes Value Sync
    const SC_SUCCESS_CRON_VENDOR = 'ls_mag/replication/success_repl_vendor';
    const SC_SUCCESS_CRON_VENDOR_ATTRIBUTE = 'ls_mag/replication/success_sync_repl_vendor_attributes';

    //execute time for sync vendor attributes value
    const LAST_EXECUTE_REPL_SYNC_VENDOR_ATTRIBUTES = 'ls_mag/replication/last_execute_repl_vendor_attributes_sync';

    //check for Data Translation
    const SC_SUCCESS_CRON_DATA_TRANSLATION_TO_MAGENTO = 'ls_mag/replication/success_repl_data_translation_to_magento';
    const SC_CRON_DATA_TRANSLATION_TO_MAGENTO_CONFIG_PATH_LAST_EXECUTE =
        'ls_mag/replication/last_execute_repl_data_translation_to_magento';
    const SC_STORE_DATA_TRANSLATION_LANG_CODE = 'ls_mag/replication/replicate_data_translation_lang_code';
    const SC_TRANSLATION_ID_ITEM_DESCRIPTION = 'T0000000027-F0000000003';
    const SC_TRANSLATION_ID_ITEM_HTML = 'T0010001410-F0000000020';
    const SC_TRANSLATION_ID_HIERARCHY_NODE = 'T0010000921-F0000000004';
    const SC_TRANSLATION_ID_ATTRIBUTE = 'T0010000784-F0000000005';
    const SC_TRANSLATION_ID_ATTRIBUTE_OPTION_VALUE = 'T0010000785-F0000000003';
    const SC_TRANSLATION_ID_PRODUCT_ATTRIBUTE_VALUE = 'T0010000786-F0000000003';
    const SC_TRANSLATION_ID_EXTENDED_VARIANT_VALUE = 'T0010001413-F0000000011';
    const SC_TRANSLATION_ID_EXTENDED_VARIANT = 'T0010001412-F0000000011';
    const SC_TRANSLATION_ID_STANDARD_VARIANT_ATTRIBUTE_OPTION_VALUE = 'T0000005401-F0000000004';
    const SC_ITEM_HTML_JOB_CODE = 'repl_html_translation';

    const SC_VERSION_CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_sync_version';

    //check for tax rules
    const SC_SUCCESS_CRON_TAX_RULES = 'ls_mag/replication/success_repl_tax_rules';
    const SC_CRON_TAX_RULES_CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_tax_rules';

    // ENHANCEMENT
    const SC_ENHANCEMENT_CRONEXPR_PREFIX = 'ls_mag/replication/cron_expr_{@1}';
    const SC_ENHANCEMENT_STORE_UUID_PREFIX = 'ls_mag/cron_enhancement/requests_per_run';
    const SC_ENHANCEMENT_INVENTORY_ACTIVE_FROM = 'ls_mag/cron_enhancement/inventory_active_from';
    const SC_ENHANCEMENT_INVENTORY_ACTIVE_TO = 'ls_mag/cron_enhancement/inventory_active_to';
    const SC_ENHANCEMENT_STORE_INVENTORY_CALCULATION = 'ls_mag/cron_enhancement/invetory_per_store';
    const SC_ENHANCEMENT_STORE_UPDATE_INVENTORY_WHEN_ZERO = 'ls_mag/cron_enhancement/if_zero';

    // LOYALTY
    const SC_LOYALTY_ENABLE_LOYALTY_ELEMENTS = 'ls_mag/loyalty/enable_loyalty_elements';
    const SC_LOYALTY_SHOW_LOYALTY_OFFERS = 'ls_mag/loyalty/show_loyalty_offers';
    const SC_LOYALTY_OFFERS_USE_STATIC_BLOCK = 'ls_mag/loyalty/use_static_block';
    const SC_LOYALTY_OFFERS_STATIC_BLOCK = 'ls_mag/loyalty/offers_block';
    const SC_LOYALTY_SHOW_POINT_OFFERS = 'ls_mag/loyalty/show_point_offers';
    const SC_LOYALTY_SHOW_MEMBER_OFFERS = 'ls_mag/loyalty/show_member_offers';
    const SC_LOYALTY_SHOW_GENERAL_OFFERS = 'ls_mag/loyalty/show_general_offers';
    const SC_LOYALTY_SHOW_COUPON_OFFERS = 'ls_mag/loyalty/show_coupon_offers';
    const SC_LOYALTY_SHOW_NOTIFICATIONS = 'ls_mag/loyalty/show_notifications';
    const SC_LOYALTY_SHOW_NOTIFICATIONS_TOP = 'ls_mag/loyalty/show_notifications_top';
    const SC_LOYALTY_SHOW_NOTIFICATIONS_LEFT = 'ls_mag/loyalty/show_notifications_left';
    const SC_LOYALTY_PAGE_IMAGE_WIDTH = 'ls_mag/loyalty/set_image_size_width_for_loyalty_page';
    const SC_LOYALTY_PAGE_IMAGE_HEIGHT = 'ls_mag/loyalty/set_image_size_height_for_loyalty_page';
    const SC_LOYALTY_EXPIRY_DATE_FORMAT = 'ls_mag/loyalty/loyalty_expiry_date_format';
    const SC_LOYALTY_CUSTOMER_USERNAME_PREFIX_PATH = 'ls_mag/loyalty/prefix';
    const SC_LOYALTY_SHOW_CLUB_INFORMATION = 'ls_mag/loyalty/show_club_information';
    const SC_LOYALTY_CUSTOMER_REGISTRATION_USERNAME_API_CALL = 'ls_mag/loyalty/username_search_by_api';
    const SC_LOYALTY_CUSTOMER_REGISTRATION_EMAIL_API_CALL = 'ls_mag/loyalty/email_search_by_api';
    const SC_LOYALTY_CUSTOMER_REGISTRATION_CONTACT_BY_CARD_ID_API_CALL = 'ls_mag/loyalty/get_contact_by_card_id_api';
    const SC_LOYALTY_POINTS_EXPIRY_CHECK = 'ls_mag/loyalty/enable_loyalty_points_expiry_check';
    const SC_LOYALTY_POINTS_EXPIRY_NOTIFICATION_INTERVAL = 'ls_mag/loyalty/loyalty_points_expiry_interval';
    const SC_ORDER_CANCELLATION_PATH = 'ls_mag/loyalty/allow_order_cancellation';
    const SC_MASTER_PASSWORD = 'ls_mag/loyalty/master_password';

    // CART
    const SC_CART_CHECK_INVENTORY = 'ls_mag/one_list/availability_check';
    const SC_CART_PRODUCT_AVAILABILITY = 'ls_mag/one_list/product_availability';
    const SC_CART_DISPLAY_STORES = 'ls_mag/one_list/display_stores';
    const SC_CART_UPDATE_INVENTORY = 'ls_mag/one_list/update_inventory';
    const SC_CART_GUEST_CHECKOUT_EMAIL = 'ls_mag/one_list/guest_checkout_email';
    const SC_CART_GUEST_CHECKOUT_PASSWORD = 'ls_mag/one_list/guest_checkout_password';
    const SC_CART_SALES_ORDER_CREATE_METHOD = 'ls_mag/one_list/sales_order_create_method';
    const SC_CART_SPECIAL_ORDER_RETRIES = 'ls_mag/one_list/special_order_create_retries';
    const SC_CART_ORDER_RETRIES = 'ls_mag/one_list/sales_order_create_retries';
    const SC_CART_SHIPMENT_FEE = 'ls_mag/one_list/shipment_fee';

    // CLICK & COLLECT
    const SC_CLICKCOLLECT_ACTIVE = 'carriers/clickcollect/active';
    const SC_CLICKCOLLECT_MAP = 'carriers/clickcollect/map';
    const SC_CLICKCOLLECT_HERE_APP_ID = 'carriers/clickcollect/app_id';
    const SC_CLICKCOLLECT_HERE_APP_CODE = 'carriers/clickcollect/app_code';
    const SC_CLICKCOLLECT_STOCKLEVEL_STORES = 'ls_mag/clickcollectsetup/showstockforstores';
    const SC_CLICKCOLLECT_GOOGLE_API_KEY = 'omni_clickandcollect/general/maps_api_key';
    const SC_CLICKCOLLECT_DEFAULT_LATITUDE = 'omni_clickandcollect/general/default_latitude';
    const SC_CLICKCOLLECT_DEFAULT_LONGITUDE = 'omni_clickandcollect/general/default_longitude';
    const SC_CLICKCOLLECT_DEFAULT_ZOOM = 'omni_clickandcollect/general/default_zoom';
    const SC_PAYMENT_OPTION = 'carriers/clickandcollect/payment_option';

    //Delivery and pickup time options
    const PICKUP_TIMESLOTS_ENABLED = 'ls_mag/ls_delivery_pickup_date_time/pickup_date_time_slot';
    const PICKUP_TIME_INTERVAL = 'ls_mag/ls_delivery_pickup_date_time/pickup_time_interval';
    const PICKUP_DATE_FORMAT = 'ls_mag/ls_delivery_pickup_date_time/pickup_date_format';
    const PICKUP_TIME_FORMAT = 'ls_mag/ls_delivery_pickup_date_time/pickup_time_format';

    //Pay At Store Payment Method
    const SC_PAYMENT_PAY_AT_STORE_ACTIVE = 'payment/ls_payment_method_pay_at_store/active';

    // CUSTOM CONFIGURATION PATHS
    const CONFIG_REPLICATION_JOBS = 'ls_mag/replication/jobs';
    const CONFIG_CONFIGDATA_WATCHES = 'ls_mag/configdata/watches';

    // REGISTRY PATHS
    const REGISTRY_LOYALTY_LOGINRESULT = 'lsr-l-lr';
    const REGISTRY_LOYALTY_WATCHNEXTSAVE = 'lsr-l-cwns';
    const REGISTRY_LOYALTY_WATCHNEXTSAVE_ADDED = 'lsr-l-cwns-a';
    const REGISTRY_LOYALTY_WATCHNEXTSAVE_REMOVED = 'lsr-l-cwns-r';
    const REGISTRY_CURRENT_REPLICATION_RUN = 'lsr-c-r-r';
    const REGISTRY_CURRENT_ENHANCEMENT_RUN = 'lsr-c-e-r';
    const REGISTRY_CURRENT_STORE = 'lsr-c-s';
    const REGISTRY_WEBSITE = 'lsr-w';
    const REGISTRY_CURRENT_JSON_PAYLOAD = 'lsr-c-j-p';

    // SESSION KEYS
    const SESSION_CUSTOMER_SECURITYTOKEN = 'lsr-s-c-st';
    const SESSION_CUSTOMER_CARDID = 'lsr-s-c-cid';
    const SESSION_CUSTOMER_LSRID = 'lsr-s-c-lid';
    const SESSION_CART_ONELIST = 'lsr-s-c-onelist';
    const SESSION_CART_WISHLIST = 'lsr-s-c-wishlist';
    const SESSION_CHECKOUT_MEMBERPOINTS = 'member_points';
    const SESSION_CHECKOUT_LAST_DOCUMENT_ID = 'last_document_id';
    const SESSION_CHECKOUT_ONE_LIST_CALCULATION = 'one_list_calculation';
    const SESSION_CHECKOUT_COUPON_CODE = 'coupon_code';
    const SESSION_CHECKOUT_CORRECT_STORE_ID = 'correct_store_id';
    const SESSION_GROUP_ID = 'customer_group_id';
    const SESSION_CUSTOMER_ID = 'customer_id';

    // WORKFLOW
    const W_TYPE = 'T';
    const W_PAYLOAD = 'P';
    const W_CURRENT = 'C';
    const W_STEPS = 'S';
    const W_WEBSITE = 'w';
    const W_STORE = 's';
    const W_STORES = 'ss';
    const W_JOB = 'j';
    const W_TIEDPAYLOAD_PREFIX = 'tp-{@1}';
    const W_BEFORE_DISPATCH = 'w-f-d';
    const W_STORE_REPLICATION_PREFIX = 'lsr_replication_store_{@1}';
    const W_STORE_ENHANCEMENT_PREFIX = 'lsr_enhancement_store_{@1}';
    const W_STORE_ENHANCEMENT_JOB_PREFIX = 'lsr_enhancement_store_{@1}_{@2}';

    // JOBS
    const JOB_CUSTOMER_SYNCHRONIZE = 'lsr_customer_synchronize';
    const JOB_SALESORDER_CREATE = 'lsr_order_create';
    const JOB_SALESORDER_CONSOLIDATOR = 'lsr_order_consolidate';
    const JOB_CLICKCOLLECT_CREATE = 'lsr_clickcollect_create';
    const JOB_HEARTBEAT = 'lsr_heartbeat';
    const JOB_SALES_ORDER_SYNCHRONIZE = 'lsr_sos';

    // CONFIGURATION WATCHER KEYS
    const CW_BEFORE = 'before';
    const CW_AFTER = 'after';
    const CW_PATH = 'path';
    const CW_WEBSITE = 'website';
    const CW_STORE = 'store';

    // SESSION MESSAGE SEVERITY
    const SEVERITY_NOTICE = 'notice';
    const SEVERITY_ERROR = 'error';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_SUCCESS = 'success';

    // ATTRIBUTE CODES
    const ATTRIBUTE_ORDER_STORE = 'store_id';
    const ATTRIBUTE_ORDER_NAVSTORE = 'lsr_clickcollect_navstore';
    const ATTRIBUTE_ORDER_SPECIALORDER_CREATED = 'lsr_specialorder_created';
    const ATTRIBUTE_ORDER_BLACKLIST = 'lsr_blacklist';
    const ATTRIBUTE_ORDER_ID = 'lsr_order_id';
    const ATTRIBUTE_ORDER_JSON = 'lsr_json';
    const ATTRIBUTE_ORDER_ERROR = 'lsr_error';
    const ATTRIBUTE_ORDER_HASH = 'lsr_hash';
    const ATTRIBUTE_ORDER_STATE = 'lsr_state';
    const ATTRIBUTE_PRODUCT_INVENTORY = 'lsr_inventory_check';
    const ATTRIBUTE_PRODUCT_DIVISION_CODE = 'lsr_division_code';
    const ATTRIBUTE_PRODUCT_ITEM_CATEGORY = 'lsr_item_category';
    const ATTRIBUTE_PRODUCT_PRODUCT_GROUP = 'lsr_product_group';
    const ATTRIBUTE_TAX = 'lsr_tax';
    const ATTRIBUTE_BASE_TAX = 'lsr_base_tax';
    const ATTRIBUTE_TAX_INVOICED = 'lsr_tax_invoiced';
    const ATTRIBUTE_BASE_TAX_INVOICED = 'lsr_base_tax_invoiced';
    const ATTRIBUTE_TAX_REFUNDED = 'lsr_tax_refunded';
    const ATTRIBUTE_BASE_TAX_REFUNDED = 'lsr_base_tax_refunded';
    const ATTRIBUTE_COUPON_CODE = 'lsr_coupon_code';
    const CONVERT_ATTRIBUTE_TO_VISUAL_SWATCH = 'ls_mag/replication/convert_attribute_to_visual_swatch';
    const VISUAL_TYPE_ATTRIBUTES = 'ls_mag/replication/visual_type_attributes';

    const IMAGE_CACHE_INDEPENDENT_OF_STORE_ID = 'ls_mag/replication/image_cache_independent_of_store_id';

    // ORDER STATES
    const ORDER_STATE_NA = 'NOT_AVAILABLE';
    const ORDER_STATE_NC = 'NOT_CREATED';
    const ORDER_STATE_NEW = 'NEW';
    const ORDER_STATE_GONE = 'GONE';
    const ORDER_STATE_CREATED = 'CREATED';
    const ORDER_STATE_OPEN = 'OPEN';
    const ORDER_STATE_PAID = 'PAID';
    const ORDER_STATE_COMPLETE = 'COMPLETE';

    //Store Hours Format
    const STORE_HOURS_TIME_FORMAT_12HRS = 'h:i A';
    const STORE_HOURS_TIME_FORMAT_24HRS = 'H:i';

    //Coupons
    const LS_ENABLE_COUPON_ELEMENTS = 'ls_mag/ls_coupons/active';
    const LS_COUPONS_SHOW_ON_CART = 'ls_mag/ls_coupons/cart';
    const LS_COUPONS_SHOW_ON_CHECKOUT = 'ls_mag/ls_coupons/checkout';
    const LS_COUPON_RECOMMENDATIONS_SHOW_ON_CART_CHECKOUT = 'ls_mag/ls_coupons/coupon_recommendations';

    //LoyaltyPoints
    const LS_ENABLE_LOYALTYPOINTS_ELEMENTS = 'ls_mag/ls_loyaltypoints/active';
    const LS_LOYALTYPOINTS_SHOW_ON_CART = 'ls_mag/ls_loyaltypoints/cart';
    const LS_LOYALTYPOINTS_SHOW_ON_CHECKOUT = 'ls_mag/ls_loyaltypoints/checkout';
    const LS_LOYALTYPOINTS_TENDER_TYPE = 'loypoints';

    //GiftCard
    const LS_ENABLE_GIFTCARD_ELEMENTS = 'ls_mag/ls_giftcard/active';
    const LS_GIFTCARD_SHOW_ON_CART = 'ls_mag/ls_giftcard/cart';
    const LS_GIFTCARD_SHOW_ON_CHECKOUT = 'ls_mag/ls_giftcard/checkout';
    const LS_GIFTCARD_SHOW_PIN_CODE_FIELD = 'ls_mag/ls_giftcard/pin_code';
    const LS_GIFTCARD_TENDER_TYPE = 'giftcard';

    //Discount Management
    const LS_DISCOUNT_SHOW_ON_PRODUCT = 'ls_mag/ls_discounts/discount';
    const LS_DISCOUNT_MIXANDMATCH_LIMIT = 'ls_mag/ls_discounts/discount_mixandmatch_limit';

    //Coupon Code Message
    const LS_STORES_OPENING_HOURS_FORMAT = 'ls_mag/ls_stores/timeformat';

    //LS New account reset password default password
    const LS_RESETPASSWORD_DEFAULT = 'Admin123@';

    //LS reset password email of the current customer
    const REGISTRY_CURRENT_RESETPASSWORD_EMAIL = 'reset-password-email';

    //Cache
    const IMAGE_CACHE = 'LS_IMAGE_';
    const POINTRATE = 'LS_POINT_RATE_';
    const PROACTIVE_DISCOUNTS = 'LS_PROACTIVE_';
    const COUPONS = 'LS_COUPONS_';
    const STORE = 'LS_STORE_';
    const STORE_HOURS = 'LS_STORE_HOURS_';
    const RETURN_POLICY_CACHE = 'LS_RETURN_POLICY_';

    // Date format to be used in fetching the data.
    const DATE_FORMAT = 'Y-m-d';
    const TIME_FORMAT = 'h:i:s A';

    //offer with no time limit for the discounts
    const NO_TIME_LIMIT = '1753-01-01T00:00:00';

    //Basket Calculation
    const LS_PLACE_TO_SYNC_BASKET_CALCULATION = 'ls_mag/ls_basket_calculation/place_to_sync';

    //Order Management
    const LS_ORDER_NUMBER_PREFIX_PATH = 'ls_mag/ls_order_management/prefix';
    const LSR_SHIPMENT_ITEM_ID = 'ls_mag/ls_order_management/shipping_item_id';
    const LSR_SHIPMENT_TAX = 'ls_mag/ls_order_management/shipping_tax';
    const LSR_PAYMENT_TENDER_TYPE_MAPPING = 'ls_mag/ls_order_management/tender_type_mapping';
    const LSR_STOCK_VALIDATION_ACTIVE = 'ls_mag/ls_order_management/stock_validation_active';
    const LSR_GRAPHQL_STOCK_VALIDATION_ACTIVE = 'ls_mag/ls_order_management/graphql_stock_validation_active';
    const LSR_DATETIME_RANGE_VALIDATION_ACTIVE = 'ls_mag/hospitality/dateandtime_range_validation_active';
    const LSR_GRAPHQL_DATETIME_RANGE_VALIDATION_ACTIVE
        = 'ls_mag/hospitality/graphql_dateandtime_range_validation_active';

    const LSR_RESTRICTED_ORDER_STATUSES = 'ls_mag/ls_order_management/sync_order_statuses';

    //Disaster Recovery Enabled/Disabled For Notification
    const LS_DISASTER_RECOVERY_STATUS = 'ls_mag/ls_disaster_recovery/notification';

    //Disaster Recovery Email Address For Notification
    const LS_DISASTER_RECOVERY_NOTIFICATION_EMAIL = 'ls_mag/ls_disaster_recovery/email';

    //Disaster Recovery Status For Email Notification
    const LS_DISASTER_RECOVERY_NOTIFICATION_EMAIL_STATUS = 'ls_mag/ls_disaster_recovery/email_sent';

    const SC_CRON_SYNC_ORDERS_CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_sync_orders';

    const SC_CRON_SYNC_CUSTOMERS_CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_sync_customers';

    const EMAIL_TEMPLATE_ID_FOR_OMNI_SERVICE_DOWN = 'ls_omni_disaster_recovery_email';

    //Order status through webhook
    const LS_STATE_CANCELED = 'CANCELED';
    const LS_STATE_CLOSED = 'CLOSED';
    const LS_STATE_COLLECTED = 'COLLECTED';
    const LS_STATE_PICKED = 'PICKED'; //Ready to Pick
    const LS_STATE_SHIPPED = 'SHIPPED';
    const LS_STATE_SHORTAGE = 'SHORTAGE';
    const LS_STATE_MISC = 'MISC';

    const LS_NOTIFICATION_EMAIL = 'email';

    //Email notification through webhook
    const LS_NOTIFICATION_TYPE = 'ls_mag/webhooks/webhooks_notification_type';
    const LS_EMAIL_NOTIFICATION_ORDER_STATUS = 'ls_mag/webhooks/webhooks_email_notification_order_status';
    const LS_NOTIFICATION_PICKUP = 'ls_mag/webhooks/notification_pickup';
    const LS_NOTIFICATION_EMAIL_TEMPLATE_PICKUP = 'ls_mag/webhooks/template_pickup';
    const LS_NOTIFICATION_COLLECTED = 'ls_mag/webhooks/notification_collected';
    const LS_NOTIFICATION_EMAIL_TEMPLATE_COLLECTED = 'ls_mag/webhooks/template_collected';
    const LS_NOTIFICATION_CANCEL = 'ls_mag/webhooks/notification_cancel';
    const LS_NOTIFICATION_EMAIL_TEMPLATE_CANCEL = 'ls_mag/webhooks/template_cancel';

    //Choose Industry
    const LS_INDUSTRY_VALUE_RETAIL = 'retail';
    const LS_INDUSTRY_VALUE_HOSPITALITY = 'hospitality';
    const LS_INDUSTRY_VALUE = 'ls_mag/ls_industry/ls_choose_industry';

    const LS_UOM_ATTRIBUTE = 'lsr_uom';
    const LS_UOM_ATTRIBUTE_QTY = 'lsr_uom_qty';
    const LS_UOM_ATTRIBUTE_HEIGHT = 'lsr_uom_height';
    const LS_UOM_ATTRIBUTE_LENGTH = 'lsr_uom_length';
    const LS_UOM_ATTRIBUTE_WIDTH = 'lsr_uom_width';
    const LS_UOM_ATTRIBUTE_CUBAGE = 'lsr_uom_cubage';

    const LS_VENDOR_ATTRIBUTE = 'lsr_vendor';
    const LS_ITEM_VENDOR_ATTRIBUTE = 'lsr_item_vendor';

    const LS_ITEM_ID_ATTRIBUTE_CODE = 'lsr_item_id';
    const LS_ITEM_ID_ATTRIBUTE_LABEL = 'Item ID';

    const LS_VARIANT_ID_ATTRIBUTE_CODE = 'lsr_variant_id';
    const LS_VARIANT_ID_ATTRIBUTE_LABEL = 'Variant ID';

    const LS_TARIFF_NO_ATTRIBUTE_CODE = 'lsr_tariff_no';
    const LS_TARIFF_NO_ATTRIBUTE_LABEL = 'Tariff No';

    const LS_ITEM_CATEGORY = 'lsr_item_category';
    const LS_ITEM_CATEGORY_LABEL = 'Item Category';
    const LS_ITEM_PRODUCT_GROUP = 'lsr_item_product_group';
    const LS_ITEM_PRODUCT_GROUP_LABEL = 'Product Group';

    const SALE_TYPE_POS = 'POS';

    const MAX_RECENT_ORDER = 5;

    const GIFT_CARD_RECIPIENT_TEMPLATE = 'giftcard_email_template';

    const LS_STANDARD_VARIANT_ATTRIBUTE_CODE = 'Standard Variant';

    // @codingStandardsIgnoreStart
    const LS_STANDARD_VARIANT_ATTRIBUTE_LABEL = 'Select Variant';
    // @codingStandardsIgnoreEnd

    const SC_REPLICATION_CENTRAL_TYPE = 'ls_mag/service/central_type';
    const OnPremise = '0';
    const Saas = '1';

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /** @var StoreInterface[] */
    public $stores;

    public $websites;

    /** @var array End Points */
    public $endpoints = [
        ServiceType::ECOMMERCE => 'UCService.svc'
    ];

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var Data
     */
    public $data;

    /**
     * @var null
     */
    public $validateBaseUrlResponse = null;

    /**
     * @var null
     */
    public $validateBaseUrlStoreId = null;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param \Ls\Core\Model\Data $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Data $data
    ) {
        $this->scopeConfig  = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->data         = $data;
    }

    /**
     * In case of notDefault we have to pass the StoreId
     * @param $path
     * @param bool $storeId
     * @return string | array
     */
    public function getStoreConfig($path, $storeId = false)
    {
        if ($storeId) {
            $sc = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORES, $storeId);
        } else {
            $sc = $this->scopeConfig->getValue($path);
        }
        return $sc;
    }

    /**
     * Use this where we want to retrieve non-cached value from core_config_data
     * i-e like in processing crons.
     * @param $path
     * @param string $scope
     * @param int $scopeId
     * @return mixed|null
     */
    public function getConfigValueFromDb($path, $scope = 'default', $scopeId = 0)
    {
        return $this->data->getConfigValueFromDb($path, $scope, $scopeId);
    }

    /**
     * This needs to be used only for Websites Scope
     * @param $path
     * @param bool $website_id
     * @return mixed
     */
    public function getWebsiteConfig($path, $website_id = false)
    {
        if ($website_id) {
            $sc = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_WEBSITES, $website_id);
        } else {
            $sc = $this->scopeConfig->getValue($path);
        }
        return $sc;
    }

    /**
     * Validate base url
     *
     * @param $baseUrl
     * @param $lsKey
     * @return bool
     * @throws NoSuchEntityException
     */
    public function validateBaseUrl($baseUrl = null, $lsKey = null)
    {
        if ($baseUrl == null) {
            $baseUrl = $this->getStoreConfig(self::SC_SERVICE_BASE_URL);
        }
        if ($lsKey == null) {
            $lsKey = $this->getStoreConfig(self::SC_SERVICE_LS_KEY);
        }
        if (empty($baseUrl)) {
            return false;
        }

        return $this->data->isEndpointResponding($baseUrl, $lsKey);
    }

    /**
     * Main function to check if service is configured and running properly for given store and scope
     *
     * @param bool $store_id
     * @param bool $scope
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isLSR($store_id = false, $scope = false)
    {
        if (!$this->isEnabled($store_id, $scope)) {
            return false;
        }

        if (isset($this->validateBaseUrlResponse) && $this->validateBaseUrlStoreId == $store_id) {
            return $this->validateBaseUrlResponse;
        }

        if ($scope == ScopeInterface::SCOPE_WEBSITES || $scope == ScopeInterface::SCOPE_WEBSITE) {
            $baseUrl = $this->getWebsiteConfig(LSR::SC_SERVICE_BASE_URL, $store_id);
            $store   = $this->getWebsiteConfig(LSR::SC_SERVICE_STORE, $store_id);
        } else {
            $baseUrl = $this->getStoreConfig(LSR::SC_SERVICE_BASE_URL, $store_id);
            $store   = $this->getStoreConfig(LSR::SC_SERVICE_STORE, $store_id);
        }
        if (empty($baseUrl) || empty($store)) {
            $this->validateBaseUrlResponse = false;
        } else {
            $this->validateBaseUrlResponse = $this->validateBaseUrl($baseUrl);
        }
        $this->validateBaseUrlStoreId = $store_id;

        return $this->validateBaseUrlResponse;
    }

    /**
     * @return string
     */
    public function getDefaultWebStore()
    {
        return $this->getStoreConfig(
            self::SC_SERVICE_STORE,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getActiveWebStore()
    {
        return $this->getStoreConfig(
            LSR::SC_SERVICE_STORE,
            $this->getCurrentStoreId()
        );
    }

    /**
     * @return string
     */
    public function getInvalidMessageContainer()
    {
        $message = '<div class="invalid-lsr">';
        $message .= '<strong>' . __('LS Retail Setup Incomplete') . '</strong>';
        $message .= '<br/>' . __('Please define the LS Retail Service Base URL and Web Store to proceed') . '<br/>';
        $message .= __('Go to Stores > Configuration > LS Retail > General Configuration.');
        $message .= '</div>';
        return $message;
    }

    /**
     * Get default google map api key from config
     * @return string
     * @throws NoSuchEntityException
     */
    public function getGoogleMapsApiKey()
    {
        return $this->scopeConfig->getValue(
            self::SC_CLICKCOLLECT_GOOGLE_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $this->getCurrentStoreId()
        );
    }

    /**
     * Get default latitude from config
     * @return string
     * @throws NoSuchEntityException
     */
    public function getDefaultLatitude()
    {
        return $this->scopeConfig->getValue(
            self::SC_CLICKCOLLECT_DEFAULT_LATITUDE,
            ScopeInterface::SCOPE_STORE,
            $this->getCurrentStoreId()
        );
    }

    /**
     * Get default longitude from config
     * @return string
     * @throws NoSuchEntityException
     */
    public function getDefaultLongitude()
    {
        return $this->scopeConfig->getValue(
            self::SC_CLICKCOLLECT_DEFAULT_LONGITUDE,
            ScopeInterface::SCOPE_STORE,
            $this->getCurrentStoreId()
        );
    }

    /**
     * Get default default zoom from config
     * @return string
     * @throws NoSuchEntityException
     */
    public function getDefaultZoom()
    {
        return $this->scopeConfig->getValue(
            self::SC_CLICKCOLLECT_DEFAULT_ZOOM,
            ScopeInterface::SCOPE_STORE,
            $this->getCurrentStoreId()
        );
    }

    /**
     * check if inventory lookup is enabled
     *
     * @param null $storeId
     * @return array|string
     * @throws NoSuchEntityException
     */
    public function inventoryLookupBeforeAddToCartEnabled($storeId = null)
    {
        //If StoreID is not passed they retrieve it from the global area.
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        return $this->getStoreConfig(self::SC_CART_CHECK_INVENTORY, $storeId);
    }

    /**
     * This can be used on all frontend areas to dynamically fetch the current storeId.
     * Try not to use it on backend or through Crons.
     * @return int
     * @throws NoSuchEntityException
     */
    public function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Return all the stores we have in Magento.
     * @return StoreInterface[]
     */
    public function getAllStores()
    {
        /** add it into the object in order to avoid loading multiple time within the same call. */
        if ($this->stores) {
            return $this->stores;
        }
        $this->stores = $this->storeManager->getStores();
        return $this->stores;
    }

    /**
     * Return all websites
     *
     * @return WebsiteInterface[]
     */
    public function getAllWebsites()
    {
        /** add it into the object in order to avoid loading multiple time within the same call. */
        if ($this->websites) {
            return $this->websites;
        }
        $this->websites = $this->storeManager->getWebsites();

        return $this->websites;
    }

    /**
     * Set Store ID in Magento Session
     * @param $storeId
     */
    public function setStoreId($storeId)
    {
        $this->storeManager->setCurrentStore($storeId);
    }

    /**
     * @param null $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getOmniVersion($storeId = null)
    {
        //If StoreID is not passed they retrieve it from the global area.
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }
        return $this->getStoreConfig(self::SC_SERVICE_VERSION, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getOmniTimeout($storeId = null)
    {
        //If StoreID is not passed they retrieve it from the global area.
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        return $this->getStoreConfig(self::SC_SERVICE_TIMEOUT, $storeId);
    }

    /**
     * Get configured industry for given store
     *
     * @param null $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCurrentIndustry($storeId = null)
    {
        //If StoreID is not passed they retrieve it from the global area.
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        return $this->getStoreConfig(self::LS_INDUSTRY_VALUE, $storeId);
    }

    /**
     * Check if order cancellation on frontend is enabled or not
     * @param null $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function orderCancellationOnFrontendIsEnabled($storeId = null)
    {
        //If StoreID is not passed they retrieve it from the global area.
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }
        return $this->getStoreConfig(self::SC_ORDER_CANCELLATION_PATH, $storeId);
    }

    /**
     * Return store manager object
     *
     * @return StoreManagerInterface
     */
    public function getStoreManagerObject()
    {

        return $this->storeManager;
    }

    /**
     * Returns configured place to sync basket
     *
     * @return mixed
     */
    public function getPlaceToCalculateBasket()
    {
        return $this->scopeConfig->getValue(
            self::LS_PLACE_TO_SYNC_BASKET_CALCULATION,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Return pickup and delivery option is enabled or not
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function isPickupTimeslotsEnabled()
    {
        return $this->scopeConfig->getValue(
            self::PICKUP_TIMESLOTS_ENABLED,
            ScopeInterface::SCOPE_WEBSITES,
            $this->storeManager->getStore()->getWebsiteId()
        );
    }

    /**
     * Get current website id
     *
     * @return int
     * @throws NoSuchEntityException
     */
    public function getCurrentWebsiteId()
    {
        return $this->storeManager->getStore()->getWebsiteId();
    }

    /**
     * Config to check if module is enabled or not for given store
     *
     * @param int $storeId
     * @param string $scope
     * @return array|mixed|string
     * @throws NoSuchEntityException
     */
    public function isEnabled($storeId = null, $scope = null)
    {
        if ($scope == ScopeInterface::SCOPE_WEBSITES || $scope == ScopeInterface::SCOPE_WEBSITE) {
            return $this->getWebsiteConfig(LSR::SC_MODULE_ENABLED, $storeId);
        }
        if ($storeId === null) {
            $storeId = $this->getCurrentStoreId();
        }

        return $this->getStoreConfig(LSR::SC_MODULE_ENABLED, $storeId);
    }

    /**
     * Get given config in given scope
     *
     * @param $configPath
     * @param $scopeId
     * @param $scope
     * @return array|mixed|string
     */
    public function getGivenConfigInGivenScope($configPath, $scope, $scopeId)
    {
        return $scope == ScopeInterface::SCOPE_WEBSITES ?
            $this->getWebsiteConfig($configPath, $scopeId) :
            $this->getStoreConfig($configPath, $scopeId);
    }

    /**
     * Function for getting colour codes based on variant value
     *
     * @return string[]
     */
    public function getColorCodes()
    {
        $colorCodes = [
            'ORANGE'    => '#FFA500',
            'GREEN'     => '#00FF00',
            'BLACK'     => '#000000',
            'GRAY'      => '#808080',
            'GREY'      => '#808080',
            'BLUE'      => '#0000FF',
            'BROWN'     => '#964B00',
            'WHITE'     => '#FFFFFF',
            'FAIR'      => '#F3CFBB',
            'LIGHT'     => '#eedd82',
            'NUDE'      => '#E3BC9A',
            'TAN'       => '#D2B48C',
            'YELLOW'    => '#FFFF00',
            'PEACH'     => '#FFE5B4',
            'PINK'      => '#FFC0CB',
            'BRONZE'    => '#CD7F32',
            'LIGHTRED'  => '#FFCCCB',
            'RED'       => '#FF0000',
            'DARKRED'   => '#8B0000',
            'RUST'      => ' #b7410e',
            'BLUEBERRY' => '#4f86f7',
            'DIMRED'    => '#4d3c45',
            'PURPLE'    => '#A020F0',
            'DEEP'      => '#361313',
            'NAVY'      => '#000080',
            'METALLIC'  => '#aaa9ad',
            'CREAM'     => '#FFFDD0'
        ];

        return $colorCodes;
    }

    /**
     * Getting current store currency code
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreCurrencyCode()
    {
        return $this->storeManager->getStore($this->getCurrentStoreId())->getCurrentCurrencyCode();
    }

    /**
     * Get gift card identifiers
     *
     * @return array|string
     * @throws NoSuchEntityException
     */
    public function getGiftCardIdentifiers()
    {
        return $this->getStoreConfig(self::GIFT_CARD_IDENTIFIER, $this->getCurrentStoreId());
    }

    /**
     * To keep running discount replication for commerce service older version and running discount replication for saas
     *
     * @param mixed $store
     * @return array
     * @throws NoSuchEntityException
     */
    public function validateForOlderVersion($store)
    {
        $status = ['discountSetup' => false, 'discount' => true];
        if (version_compare($this->getOmniVersion(), '2023.10', '>')) {
            if ($this->getWebsiteConfig(LSR::SC_REPLICATION_CENTRAL_TYPE, $store->getWebsiteId()) == LSR::OnPremise) {
                $status = ['discountSetup' => true, 'discount' => false];
            }
        }

        return $status;
    }
}
