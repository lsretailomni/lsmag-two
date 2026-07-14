<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Omni\Test\Integration\Model\GiftCard;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use \Ls\Omni\Model\GiftCard\GiftCardManagement;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Fixture\AppArea;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the unified multi-entry POS data entry API introduced in PR #84642.
 *
 * GiftCardManagement::applyEntry()/removeEntry()/getEntries() is the single implementation
 * shared by the Luma GiftCardUsed controller and the GraphQL PosDataEntry resolvers. Gift
 * cards (entry_type = GIFTCARDNO) and vouchers (any other tender-type-mapped entry_type) are
 * stored uniformly in the quote's ls_pos_data_entries JSON column.
 *
 * The service and gift card / voucher configuration is applied at class level so every test
 * runs against the same LS Central connection and admin setup.
 *
 * @magentoAppIsolation enabled
 */

class GiftCardManagementTest extends TestCase
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
     * @var CustomerSession
     */
    public $customerSession;

    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    /**
     * @var ManagerInterface
     */
    public $eventManager;

    /**
     * @var CartRepositoryInterface
     */
    public $quoteRepository;

    /**
     * @var GiftCardManagement
     */
    public $giftCardManagement;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManager      = Bootstrap::getObjectManager();
        $this->fixtures           = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->giftCardManagement = $this->objectManager->get(GiftCardManagement::class);
        $this->customerSession    = $this->objectManager->get(CustomerSession::class);
        $this->checkoutSession    = $this->objectManager->get(CheckoutSession::class);
        $this->eventManager       = $this->objectManager->create(ManagerInterface::class);
        $this->quoteRepository    = $this->objectManager->create(CartRepositoryInterface::class);
    }

    /**
     * Prepare the customer/checkout session for the fixture cart and fire the cart-save event.
     *
     * @return \Magento\Quote\Api\Data\CartInterface
     */

    private function prepareCart()
    {
        $customer = $this->fixtures->get('customer');
        $cart     = $this->fixtures->get('cart1');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
        $this->checkoutSession->setQuoteId($cart->getId());
        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);

        return $cart;
    }

    /**
     * A valid gift card (entry_type = GIFTCARDNO) is appended as a single entry.
     *
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_CENTRAL_VERSION, 'website'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::LS_MAG_ENABLE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL,  'store', 'default'),
        Config(LSR::LS_GIFTCARD_SHOW_PIN_CODE_FIELD, AbstractIntegrationTest::GC_SHOW_PIN_CODE_FIELD, 'store', 'default'),
        Config(LSR::LS_VOUCHER_GIFT_CARD_CONFIGURATION, AbstractIntegrationTest::VOUCHER_CONFIGURATION, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id' => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid' => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token' => AbstractIntegrationTest::CUSTOMER_ID
            ],
            as: 'customer'
        ),
        DataFixture(
            CreateSimpleProductFixture::class,
            [
                LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180',
            ],
            as: 'p1'
        ),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart1'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1])
    ]

    public function testApplyValidGiftCardEntry()
    {
        $cart = $this->prepareCart();

        $result = $this->giftCardManagement->applyEntry(
            (int)$cart->getId(),
            AbstractIntegrationTest::GIFTCARD_ENTRY_TYPE,
            AbstractIntegrationTest::GIFTCARD,
            AbstractIntegrationTest::GIFTCARD_PIN,
            (float)AbstractIntegrationTest::GIFTCARD_AMOUNT
        );

        $this->assertTrue($result);

        $entries = $this->giftCardManagement->getEntries((int)$cart->getId());
        $this->assertCount(1, $entries);
        $this->assertSame(AbstractIntegrationTest::GIFTCARD, $entries[0]['code']);
        $this->assertSame('GIFTCARDNO', strtoupper((string)$entries[0]['entry_type']));
        $this->assertEquals((float)AbstractIntegrationTest::GIFTCARD_AMOUNT, $entries[0]['amount']);
    }

    /**
     * A valid voucher (entry_type = VOUCHER) travels through the same contract and is stored
     * as a non-GIFTCARDNO entry.
     *
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_CENTRAL_VERSION, 'website'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::LS_MAG_ENABLE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL,  'store', 'default'),
        Config(LSR::LS_GIFTCARD_SHOW_PIN_CODE_FIELD, AbstractIntegrationTest::GC_SHOW_PIN_CODE_FIELD, 'store', 'default'),
        Config(LSR::LS_VOUCHER_GIFT_CARD_CONFIGURATION, AbstractIntegrationTest::VOUCHER_CONFIGURATION, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id' => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid' => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token' => AbstractIntegrationTest::CUSTOMER_ID
            ],
            as: 'customer'
        ),
        DataFixture(
            CreateSimpleProductFixture::class,
            [
                LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180',
            ],
            as: 'p1'
        ),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart1'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1])
    ]
    public function testApplyValidVoucherEntry()
    {
        $cart = $this->prepareCart();

        $result = $this->giftCardManagement->applyEntry(
            (int)$cart->getId(),
            AbstractIntegrationTest::VOUCHER_ENTRY_TYPE,
            AbstractIntegrationTest::VOUCHER,
            AbstractIntegrationTest::VOUCHER_PIN,
            (float)AbstractIntegrationTest::VOUCHER_AMOUNT
        );

        $this->assertTrue($result);

        $entries = $this->giftCardManagement->getEntries((int)$cart->getId());
        $this->assertCount(1, $entries);
        $this->assertSame(AbstractIntegrationTest::VOUCHER, $entries[0]['code']);
        $this->assertNotSame('GIFTCARDNO', strtoupper((string)$entries[0]['entry_type']));
    }

    /**
     * A gift card and a voucher can be applied to the same cart; both accumulate as separate
     * entries in the unified column.
     *
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_CENTRAL_VERSION, 'website'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::LS_MAG_ENABLE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL,  'store', 'default'),
        Config(LSR::LS_GIFTCARD_SHOW_PIN_CODE_FIELD, AbstractIntegrationTest::GC_SHOW_PIN_CODE_FIELD, 'store', 'default'),
        Config(LSR::LS_VOUCHER_GIFT_CARD_CONFIGURATION, AbstractIntegrationTest::VOUCHER_CONFIGURATION, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id' => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid' => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token' => AbstractIntegrationTest::CUSTOMER_ID
            ],
            as: 'customer'
        ),
        DataFixture(
            CreateSimpleProductFixture::class,
            [
                LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180',
            ],
            as: 'p1'
        ),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart1'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1])
    ]
    public function testApplyMultipleEntries()
    {
        $cart = $this->prepareCart();

        $this->giftCardManagement->applyEntry(
            (int)$cart->getId(),
            AbstractIntegrationTest::GIFTCARD_ENTRY_TYPE,
            AbstractIntegrationTest::GIFTCARD,
            AbstractIntegrationTest::GIFTCARD_PIN,
            (float)AbstractIntegrationTest::GIFTCARD_AMOUNT
        );
        $this->giftCardManagement->applyEntry(
            (int)$cart->getId(),
            AbstractIntegrationTest::VOUCHER_ENTRY_TYPE,
            AbstractIntegrationTest::VOUCHER,
            AbstractIntegrationTest::VOUCHER_PIN,
            (float)AbstractIntegrationTest::VOUCHER_AMOUNT
        );

        $entries = $this->giftCardManagement->getEntries((int)$cart->getId());
        $this->assertCount(2, $entries);

        $codes = array_column($entries, 'code');
        $this->assertContains(AbstractIntegrationTest::GIFTCARD, $codes);
        $this->assertContains(AbstractIntegrationTest::VOUCHER, $codes);
    }

    /**
     * Applying the same gift card twice must be rejected.
     *
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_CENTRAL_VERSION, 'website'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::LS_MAG_ENABLE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL,  'store', 'default'),
        Config(LSR::LS_GIFTCARD_SHOW_PIN_CODE_FIELD, AbstractIntegrationTest::GC_SHOW_PIN_CODE_FIELD, 'store', 'default'),
        Config(LSR::LS_VOUCHER_GIFT_CARD_CONFIGURATION, AbstractIntegrationTest::VOUCHER_CONFIGURATION, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id' => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid' => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token' => AbstractIntegrationTest::CUSTOMER_ID
            ],
            as: 'customer'
        ),
        DataFixture(
            CreateSimpleProductFixture::class,
            [
                LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180',
            ],
            as: 'p1'
        ),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart1'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1])
    ]
    public function testApplySameGiftCardTwiceThrows()
    {
        $cart = $this->prepareCart();

        $this->giftCardManagement->applyEntry(
            (int)$cart->getId(),
            AbstractIntegrationTest::GIFTCARD_ENTRY_TYPE,
            AbstractIntegrationTest::GIFTCARD,
            AbstractIntegrationTest::GIFTCARD_PIN,
            (float)AbstractIntegrationTest::GIFTCARD_AMOUNT
        );

        $this->expectException(CouldNotSaveException::class);
        $this->giftCardManagement->applyEntry(
            (int)$cart->getId(),
            AbstractIntegrationTest::GIFTCARD_ENTRY_TYPE,
            AbstractIntegrationTest::GIFTCARD,
            AbstractIntegrationTest::GIFTCARD_PIN,
            (float)AbstractIntegrationTest::GIFTCARD_AMOUNT
        );
    }

    /**
     * An expired gift card cannot be applied.
     *
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_CENTRAL_VERSION, 'website'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::LS_MAG_ENABLE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL,  'store', 'default'),
        Config(LSR::LS_GIFTCARD_SHOW_PIN_CODE_FIELD, AbstractIntegrationTest::GC_SHOW_PIN_CODE_FIELD, 'store', 'default'),
        Config(LSR::LS_VOUCHER_GIFT_CARD_CONFIGURATION, AbstractIntegrationTest::VOUCHER_CONFIGURATION, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id' => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid' => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token' => AbstractIntegrationTest::CUSTOMER_ID
            ],
            as: 'customer'
        ),
        DataFixture(
            CreateSimpleProductFixture::class,
            [
                LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180',
            ],
            as: 'p1'
        ),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart1'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1])
    ]
    public function testApplyExpiredGiftCardEntryThrows()
    {
        $cart = $this->prepareCart();

        $this->expectException(CouldNotSaveException::class);
        $this->giftCardManagement->applyEntry(
            (int)$cart->getId(),
            AbstractIntegrationTest::GIFTCARD_ENTRY_TYPE,
            AbstractIntegrationTest::GIFTCARD_EXPIRED,
            AbstractIntegrationTest::GIFTCARD_EXPIRED_PIN,
            (float)AbstractIntegrationTest::GIFTCARD_AMOUNT
        );
    }

    /**
     * removeEntry() drops the matching entry by type + code and recomputes totals.
     *
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_CENTRAL_VERSION, 'website'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::LS_MAG_ENABLE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL,  'store', 'default'),
        Config(LSR::LS_GIFTCARD_SHOW_PIN_CODE_FIELD, AbstractIntegrationTest::GC_SHOW_PIN_CODE_FIELD, 'store', 'default'),
        Config(LSR::LS_VOUCHER_GIFT_CARD_CONFIGURATION, AbstractIntegrationTest::VOUCHER_CONFIGURATION, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id' => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid' => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token' => AbstractIntegrationTest::CUSTOMER_ID
            ],
            as: 'customer'
        ),
        DataFixture(
            CreateSimpleProductFixture::class,
            [
                LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180',
            ],
            as: 'p1'
        ),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart1'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1])
    ]
    public function testRemoveGiftCardEntry()
    {
        $cart = $this->prepareCart();

        $this->giftCardManagement->applyEntry(
            (int)$cart->getId(),
            AbstractIntegrationTest::GIFTCARD_ENTRY_TYPE,
            AbstractIntegrationTest::GIFTCARD,
            AbstractIntegrationTest::GIFTCARD_PIN,
            (float)AbstractIntegrationTest::GIFTCARD_AMOUNT
        );
        $this->assertCount(1, $this->giftCardManagement->getEntries((int)$cart->getId()));

        $result = $this->giftCardManagement->removeEntry(
            (int)$cart->getId(),
            AbstractIntegrationTest::GIFTCARD_ENTRY_TYPE,
            AbstractIntegrationTest::GIFTCARD
        );

        $this->assertTrue($result);
        $this->assertCount(0, $this->giftCardManagement->getEntries((int)$cart->getId()));

        $cartQuote = $this->quoteRepository->get($cart->getId());
        $this->assertEmpty($cartQuote->getLsPosDataEntries());
    }

    /**
     * Removing one entry leaves the other applied entries intact.
     *
     * @magentoAppIsolation enabled
     */

    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_CENTRAL_VERSION, 'website'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::LS_MAG_ENABLE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL,  'store', 'default'),
        Config(LSR::LS_GIFTCARD_SHOW_PIN_CODE_FIELD, AbstractIntegrationTest::GC_SHOW_PIN_CODE_FIELD, 'store', 'default'),
        Config(LSR::LS_VOUCHER_GIFT_CARD_CONFIGURATION, AbstractIntegrationTest::VOUCHER_CONFIGURATION, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id' => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid' => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token' => AbstractIntegrationTest::CUSTOMER_ID
            ],
            as: 'customer'
        ),
        DataFixture(
            CreateSimpleProductFixture::class,
            [
                LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180',
            ],
            as: 'p1'
        ),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart1'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1])
    ]
    public function testRemoveOneEntryKeepsOthers()
    {
        $cart = $this->prepareCart();

        $this->giftCardManagement->applyEntry(
            (int)$cart->getId(),
            AbstractIntegrationTest::GIFTCARD_ENTRY_TYPE,
            AbstractIntegrationTest::GIFTCARD,
            AbstractIntegrationTest::GIFTCARD_PIN,
            (float)AbstractIntegrationTest::GIFTCARD_AMOUNT
        );
        $this->giftCardManagement->applyEntry(
            (int)$cart->getId(),
            AbstractIntegrationTest::VOUCHER_ENTRY_TYPE,
            AbstractIntegrationTest::VOUCHER,
            AbstractIntegrationTest::VOUCHER_PIN,
            (float)AbstractIntegrationTest::VOUCHER_AMOUNT
        );

        $this->giftCardManagement->removeEntry(
            (int)$cart->getId(),
            AbstractIntegrationTest::GIFTCARD_ENTRY_TYPE,
            AbstractIntegrationTest::GIFTCARD
        );

        $entries = $this->giftCardManagement->getEntries((int)$cart->getId());
        $this->assertCount(1, $entries);
        $this->assertSame(AbstractIntegrationTest::VOUCHER, $entries[0]['code']);
    }
}
