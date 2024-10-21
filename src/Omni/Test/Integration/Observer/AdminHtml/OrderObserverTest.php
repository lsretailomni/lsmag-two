<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Omni\Test\Integration\Observer\AdminHtml;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Observer\Adminhtml\OrderObserver;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Test\Fixture\CustomerAddressFixture;
use \Ls\Omni\Test\Fixture\CustomerOrder;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Quote\Model\QuoteFactory;
use Magento\TestFramework\Fixture\AppArea;
use Magento\Framework\Registry;
use Magento\Framework\Event;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Message\ManagerInterface as MessageManager;

/**
 * @magentoAppArea adminhtml
 */
class OrderObserverTest extends AbstractIntegrationTest
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var mixed
     */
    public $request;

    /**
     * @var DataFixtureStorageManager
     */
    public $fixtures;

    /**
     * @var mixed
     */
    public $registry;

    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    /**
     * @var mixed
     */
    public $eventManager;

    /**
     * @var OrderObserver
     */
    public $orderObserver;

    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * @var Event
     */
    public $event;

    /** @var ItemHelper $itemHelper */
    public $itemHelper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var mixed
     */
    public $customerSession;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager   = Bootstrap::getObjectManager();
        $this->request         = $this->objectManager->get(HttpRequest::class);
        $this->fixtures        = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->eventManager    = $this->objectManager->create(ManagerInterface::class);
        $this->event           = $this->objectManager->get(Event::class);
        $this->orderObserver   = $this->objectManager->get(OrderObserver::class);
        $this->registry        = $this->objectManager->get(Registry::class);
        $this->basketHelper    = $this->objectManager->get(BasketHelper::class);
        $this->itemHelper      = $this->objectManager->get(ItemHelper::class);
        $this->messageManager  = $this->objectManager->get(MessageManager::class);
        $this->customerSession = $this->objectManager->get(CustomerSession::class);
        $this->checkoutSession = $this->objectManager->get(CheckoutSession::class);
    }

//    /**
//     * @magentoAppIsolation enabled
//     */
//    #[
//        AppArea('adminhtml'),
//        Config(LSR::SC_SERVICE_ENABLE, self::LS_MAG_ENABLE, 'store', 'default'),
//        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
//        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
//        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
//        Config(LSR::LS_INDUSTRY_VALUE, self::RETAIL_INDUSTRY, 'store', 'default'),
//        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, self::LICENSE, 'website'),
//        Config(LSR::LSR_ORDER_EDIT, self::LSR_ORDER_EDIT, 'store', 'default'),
//        Config(LSR::LSR_PAYMENT_TENDER_TYPE_MAPPING, self::TENDER_TYPE_MAPPINGS, 'store', 'default'),
//        DataFixture(
//            CustomerFixture::class,
//            [
//                'lsr_username' => AbstractIntegrationTest::USERNAME,
//                'lsr_id'       => AbstractIntegrationTest::LSR_ID,
//                'lsr_cardid'   => AbstractIntegrationTest::LSR_CARD_ID,
//                'lsr_token'    => AbstractIntegrationTest::CUSTOMER_ID
//            ],
//            as: 'customer'
//        ),
//        DataFixture(
//            CreateSimpleProductFixture::class,
//            [
//                LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180'
//            ],
//            as: 'p1'
//        ),
//        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart1'),
//        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1]),
//        DataFixture(
//            CustomerAddressFixture::class,
//            [
//                'customer_id' => '$customer.entity_id$'
//            ],
//            as: 'address'
//        ),
//        DataFixture(
//            CustomerOrder::class,
//            [
//                'customer' => '$customer$',
//                'cart1'    => '$cart1$',
//                'address'  => '$address$',
//                'payment'  => 'checkmo'
//            ],
//            as: 'order'
//        ),
//        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart2'),
//        DataFixture(
//            CustomerAddressFixture::class,
//            [
//                'customer_id' => '$customer.entity_id$'
//            ],
//            as: 'address2'
//        ),
//        DataFixture(AddProductToCart::class, ['cart_id' => '$cart2.id$', 'product_id' => '$p1.id$', 'qty' => 3]),
//        DataFixture(
//            CustomerOrder::class,
//            [
//                'customer' => '$customer$',
//                'cart1'    => '$cart2$',
//                'address'  => '$address2$',
//                'payment'  => 'checkmo'
//            ],
//            as: 'order2'
//        )
//    ]
//    /**
//     * Test admin order edit creation
//     */
//    public function testAdminOrderEdit()
//    {
//        $customer = $this->fixtures->get('customer');
//        $cart2    = $this->fixtures->get('cart2');
//        $order    = $this->fixtures->get('order');
//        $order2   = $this->fixtures->get('order2');
//        $this->customerSession->setData('customer_id', $customer->getId());
//        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
//        $this->checkoutSession->setQuoteId($cart2->getId());
//
//        $relationParentId = $order->getId();
//        $order2->setRelationParentId($relationParentId);
//        $order2->setEditIncrement(1);
//        $this->event->setData('order', $order2);
//
//        $oneList = $this->basketHelper->getOneListAdmin(
//            $cart2->getCustomerEmail(),
//            $cart2->getStore()->getWebsiteId(),
//            false
//        );
//
//        $oneList = $this->basketHelper->setOneListQuote($cart2, $oneList);
//        $this->basketHelper->setOneListCalculationInCheckoutSession($oneList);
//        $basketData = $this->basketHelper->update($oneList);
//        $quote      = $this->checkoutSession->getQuote();
//        $this->itemHelper->setDiscountedPricesForItems($quote, $basketData, 2);
//
//        // Execute the observer method
//        $this->orderObserver->execute(new Observer(
//            [
//                'event' => $this->event
//            ]
//        ));
//
//        $statusMessages = [];
//        foreach ($this->messageManager->getMessages()->getItems() as $messageObj) {
//            $statusMessages[] = $messageObj->getText();
//        }
//        $this->assertTrue(
//            in_array('Order edit request has been sent to LS Central successfully', $statusMessages),
//            'Expected validation message is generated.'
//        );
//    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('adminhtml'),
        Config(LSR::SC_SERVICE_ENABLE, self::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, self::RETAIL_INDUSTRY, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, self::LICENSE, 'website'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id'       => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid'   => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token'    => AbstractIntegrationTest::CUSTOMER_ID
            ],
            as: 'customer'
        ),
        DataFixture(
            CreateSimpleProductFixture::class,
            [
                LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180'
            ],
            as: 'p1'
        ),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart1'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1]),
        DataFixture(
            CustomerAddressFixture::class,
            [
                'customer_id' => '$customer.entity_id$'
            ],
            as: 'address'
        ),
        DataFixture(
            CustomerOrder::class,
            [
                'customer' => '$customer$',
                'cart1'    => '$cart1$',
                'address'  => '$address$',
                'payment'  => 'checkmo'
            ],
            as: 'order'
        )
    ]
    /**
     * Test admin order creation
     */
    public function testAdminOrderCreate()
    {
        $customer = $this->fixtures->get('customer');
        $cart     = $this->fixtures->get('cart1');
        $order    = $this->fixtures->get('order');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
        $this->checkoutSession->setQuoteId($cart->getId());

        $this->event->setData('order', $order);

        $oneList = $this->basketHelper->getOneListAdmin(
            $cart->getCustomerEmail(),
            $cart->getStore()->getWebsiteId(),
            false
        );

        $oneList = $this->basketHelper->setOneListQuote($cart, $oneList);
        $this->basketHelper->setOneListCalculationInCheckoutSession($oneList);
        $basketData = $this->basketHelper->update($oneList);
        $quote      = $this->checkoutSession->getQuote();
        $this->itemHelper->setDiscountedPricesForItems($quote, $basketData, 2);

        // Execute the observer method
        $this->orderObserver->execute(new Observer(
            [
                'event' => $this->event
            ]
        ));

        $statusMessages = [];
        foreach ($this->messageManager->getMessages()->getItems() as $messageObj) {
            $statusMessages[] = $messageObj->getText();
        }
        $this->assertTrue(
            in_array('Order request has been sent to LS Central successfully', $statusMessages),
            'Expected validation message is generated.'
        );
    }
}
