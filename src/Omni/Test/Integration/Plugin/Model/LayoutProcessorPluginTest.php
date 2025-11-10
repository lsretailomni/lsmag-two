<?php

namespace Ls\Omni\Test\Integration\Plugin\Model;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Omni\Plugin\Checkout\Model\LayoutProcessorPlugin;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoAppArea frontend
 */
class LayoutProcessorPluginTest extends AbstractIntegrationTest
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var DataFixtureStorageManager
     */
    public $fixtures;

    /**
     * @var LayoutProcessor
     */
    public $layoutProcessor;

    /**
     * @var LayoutProcessorPlugin
     */
    public $layoutProcessorPlugin;

    /**
     * @var ContactHelper
     */
    public $contactHelper;

    public $jsLayout = [];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager         = Bootstrap::getObjectManager();
        $this->fixtures              = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->layoutProcessorPlugin = $this->objectManager->get(LayoutProcessorPlugin::class);
        $this->layoutProcessor       = $this->objectManager->get(LayoutProcessor::class);
        $this->contactHelper         = $this->objectManager->get(ContactHelper::class);

        $this->jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shippingAdditional']['children']['ls-pickup-additional-options-wrapper'] = 'ls-pickup-additional-options-wrapper';
        $this->jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shippingAdditional']['children']['select_store']                         = [];

        $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['discount']       = [];
        $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['loyalty-points'] = 'loy';
        $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['gift-card']      = 'gift-card';

        $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['renders']['children']['payatstore']                                = 'payatstore';
        $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children']['ls_payment_method_pay_at_store-form'] = 'ls_payment_method_pay_at_store';
        $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['loyalty-points']                       = 'loyalty-points';
        $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['gift-card']                            = 'gift-card';
        $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['additional-payment-validators']['children']['discount-validator']  = 'discount-validator';
        $this->jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['additional-payment-validators']['children']['discount-validator']  = 'discount-validator';

        $this->jsLayout['components']['checkout']['children']['sidebar']['children']['summary']['children']['totals']['children']['ls_gift_card_amount_used'] = 'ls_gift_card_amount_used';
        $this->jsLayout['components']['checkout']['children']['sidebar']['children']['summary']['children']['totals']['children']['ls_points_discount']       = 'ls_points_discount';
        $this->jsLayout['components']['checkout']['children']['sidebar']['children']['summary']['children']['totals']['children']['loyalty_points_label']     = 'loyalty_points_label';
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, self::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default' ),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website' ),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'store', 'default'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'store', 'default'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'store', 'default'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'store', 'default'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'store', 'default'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'store', 'default'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'store', 'default'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_CENTRAL_VERSION, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_CENTRAL_VERSION, 'store', 'default'),
        Config(LSR::LSR_ORDER_EDIT, self::LSR_ORDER_EDIT, 'store', 'default'),
        Config(LSR::LS_ENABLE_COUPON_ELEMENTS, self::ENABLE_COUPON_ELEMENTS, 'store', 'default'),
        Config(LSR::LS_COUPONS_SHOW_ON_CHECKOUT, 0, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, self::RETAIL_INDUSTRY, 'store', 'default'),
        Config(LSR::LS_ENABLE_COUPON_ELEMENTS, 0, 'store', 'default'),
        Config(LSR::LS_COUPONS_SHOW_ON_CHECKOUT, 0, 'store', 'default'),
        Config(LSR::LS_ENABLE_LOYALTYPOINTS_ELEMENTS, 0, 'store', 'default'),
        Config(LSR::LS_LOYALTYPOINTS_SHOW_ON_CHECKOUT, 0, 'store', 'default'),
        Config(LSR::LS_ENABLE_GIFTCARD_ELEMENTS, 0, 'store', 'default'),
        Config(LSR::LS_GIFTCARD_SHOW_ON_CHECKOUT, 0, 'store', 'default'),
        Config(LSR::LSR_DISCOUNT_VALIDATION_ACTIVE, 0, 'store', 'default'),
        Config(LSR::PICKUP_TIMESLOTS_ENABLED, 0, 'website'),
        Config(LSR::SC_CLICKCOLLECT_ENABLED, 0, 'store', 'default'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::LS_MAG_ENABLE, 'website')

    ]
    public function testAfterProcessWithCouponsAndLoyDisabled()
    {
        $result = $this->layoutProcessorPlugin->afterProcess($this->layoutProcessor, $this->jsLayout);

        $shippingStep       = $result['components']['checkout']['children']['steps']['children']['shipping-step'];
        $billingStep        = $result['components']['checkout']['children']['steps']['children']['billing-step'];
        $payment            = $billingStep['children']['payment'];
        $sideBar            = $result['components']['checkout']['children']['sidebar'];
        $shippingAdditional = $shippingStep['children']['shippingAddress']['children']['shippingAdditional'];

        $this->assertArrayNotHasKey('discount', $billingStep['children']['payment']['children']['afterMethods']['children']);
        $this->assertArrayNotHasKey('loyalty-points', $billingStep['children']['payment']['children']['afterMethods']['children']);
        $this->assertArrayNotHasKey('loyalty_points_label', $sideBar['children']['summary']['children']['totals']['children']);

        $this->assertArrayNotHasKey('gift-card', $billingStep['children']['payment']['children']['afterMethods']['children']);

        $this->assertArrayNotHasKey('discount-validator', $payment['children']['additional-payment-validators']['children']);
        $this->assertArrayNotHasKey('ls-pickup-additional-options-wrapper', $shippingAdditional['children']);

        $this->assertArrayNotHasKey('component', $shippingAdditional['children']['select_store']);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, self::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default' ),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website' ),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'store', 'default'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'store', 'default'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'store', 'default'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'store', 'default'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'store', 'default'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'store', 'default'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'store', 'default'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_CENTRAL_VERSION, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_CENTRAL_VERSION, 'store', 'default'),
        Config(LSR::LSR_ORDER_EDIT, self::LSR_ORDER_EDIT, 'store', 'default'),
        Config(LSR::LS_ENABLE_COUPON_ELEMENTS, self::ENABLE_COUPON_ELEMENTS, 'store', 'default'),
        Config(LSR::LS_COUPONS_SHOW_ON_CHECKOUT, self::COUPONS_SHOW_ON_CHECKOUT, 'store', 'default'),
        Config(LSR::LS_ENABLE_LOYALTYPOINTS_ELEMENTS, self::ENABLE_LOY_ELEMENTS, 'store', 'default'),
        Config(LSR::LS_LOYALTYPOINTS_SHOW_ON_CHECKOUT, self::LOY_SHOW_ON_CHECKOUT, 'store', 'default'),
        Config(LSR::LS_ENABLE_GIFTCARD_ELEMENTS, self::ENABLE_GC_ELEMENTS, 'store', 'default'),
        Config(LSR::LS_GIFTCARD_SHOW_ON_CHECKOUT, self::GC_SHOW_ON_CHECKOUT, 'store', 'default'),
        Config(LSR::LSR_DISCOUNT_VALIDATION_ACTIVE, self::DISCOUNT_VALIDATION_ACTIVE, 'store', 'default'),
        Config(LSR::PICKUP_TIMESLOTS_ENABLED, self::PICKUP_TIMESLOTS_ENABLED, 'website'),
        Config(LSR::SC_CLICKCOLLECT_ENABLED, self::SC_CLICKCOLLECT_ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, self::LS_CENTRAL_VERSION, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, self::RETAIL_INDUSTRY, 'store', 'default'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::LS_MAG_ENABLE, 'website')
    ]
    public function testAfterProcessWithAllDiscountOptionsEnabled()
    {
        $this->contactHelper->setCardIdInCustomerSession(self::LSR_CARD_ID);
        $result = $this->layoutProcessorPlugin->afterProcess($this->layoutProcessor, $this->jsLayout);

        $shippingStep       = $result['components']['checkout']['children']['steps']['children']['shipping-step'];
        $billingStep        = $result['components']['checkout']['children']['steps']['children']['billing-step'];
        $payment            = $billingStep['children']['payment'];
        $sideBar            = $result['components']['checkout']['children']['sidebar'];
        $shippingAdditional = $shippingStep['children']['shippingAddress']['children']['shippingAdditional'];

        $this->assertEquals('Ls_Omni/js/view/payment/discount', $billingStep['children']['payment']['children']['afterMethods']['children']['discount']['component']);
        $this->assertArrayHasKey('discount', $billingStep['children']['payment']['children']['afterMethods']['children']);
        $this->assertArrayHasKey('loyalty-points', $billingStep['children']['payment']['children']['afterMethods']['children']);
        $this->assertArrayHasKey('loyalty_points_label', $sideBar['children']['summary']['children']['totals']['children']);

        $this->assertArrayHasKey('gift-card', $billingStep['children']['payment']['children']['afterMethods']['children']);

        $this->assertArrayHasKey('discount-validator', $payment['children']['additional-payment-validators']['children']);
        $this->assertArrayHasKey('ls-pickup-additional-options-wrapper', $shippingAdditional['children']);

        $this->assertEquals('Ls_Omni/js/view/checkout/shipping/select-store', $shippingAdditional['children']['select_store']['component']);
    }
}
