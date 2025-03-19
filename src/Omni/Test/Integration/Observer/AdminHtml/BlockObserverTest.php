<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Omni\Test\Integration\Observer\AdminHtml;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Observer\Adminhtml\BlockObserver;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Test\Fixture\CustomerAddressFixture;
use \Ls\Omni\Test\Fixture\CustomerOrder;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Quote\Model\QuoteFactory;
use Magento\TestFramework\Fixture\AppArea;
use Magento\Framework\Registry;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\View\Result\PageFactory;

/**
 * @magentoAppArea adminhtml
 */
class BlockObserverTest extends AbstractIntegrationTest
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
     * @var mixed
     */
    public $checkoutSession;

    /**
     * @var mixed
     */
    public $eventManager;

    /**
     * @var BlockObserver
     */
    public $blockObserver;

    /**
     * @var LayoutInterface
     */
    public $block;

    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @var TransportInterface
     */
    private $transport;

    /**
     * @var mixed
     */
    public $page;

    /**
     * @var Event
     */
    public $event;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->request       = $this->objectManager->get(HttpRequest::class);
        $this->fixtures      = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->eventManager  = $this->objectManager->create(ManagerInterface::class);
        $this->event         = $this->objectManager->get(Event::class);
        $this->blockObserver = $this->objectManager->get(BlockObserver::class);
        $this->registry      = $this->objectManager->get(Registry::class);
        $this->transport     = $this->objectManager->get(TransportInterface::class);
        $this->block         = $this->objectManager->get(
            LayoutInterface::class
        );

        $this->pageFactory = $this->objectManager->get(PageFactory::class);
        $this->page        = $this->pageFactory->create();
    }

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
     * Test Block Observer behavior with Click and Collect shipping method
     */
    public function testBlockObserverWithClickAndCollect()
    {
        $order = $this->fixtures->get('order');
        $order->setShippingMethod('clickandcollect_clickandcollect');
        $this->registry->register('current_order', $order);
        $this->registry->register('sales_order', $order);

        $this->page->addHandle([
            'default',
            'sales_order_view',
        ]);
        $this->page->getLayout()->generateXml();
        $outputHtml = '
<div class="admin__page-section-item order-shipping-method">
    <div class="admin__page-section-item-title">
        <span class="title">Shipping &amp; Handling Information</span>
    </div>
    <div class="admin__page-section-item-content">
                            <strong>Click And Collect - Fixed</strong>


            <span class="price">£0.00</span>                        </div>
</div>';

        $result = $output = new DataObject();
        $output->setOutput($outputHtml);
        $this->event->setResult($result);

        // Execute the observer method
        $this->blockObserver->execute(new Observer(
            [
                'event'        => $this->event,
                'layout'       => $this->block,
                'transport'    => $output,
                'element_name' => 'order_shipping_view'
            ]
        ));

        $this->registry->unregister('current_order');
        $this->registry->unregister('sales_order');

        $this->assertStringContainsString((string)__('pickup-info-wrapper'), $this->event->getResult()->getOutput());
    }

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
     * Test Block Observer behavior with shipping block with flat shipping
     */
    public function testBlockObserverWithFlatShipping()
    {
        $order = $this->fixtures->get('order');
        $this->registry->register('current_order', $order);
        $this->registry->register('sales_order', $order);

        $this->page->addHandle([
            'default',
            'sales_order_view',
        ]);
        $this->page->getLayout()->generateXml();

        $result = new DataObject();
        $this->event->setResult($result);

        // Execute the observer method
        $this->blockObserver->execute(new Observer(
            [
                'event'        => $this->event,
                'layout'       => $this->block,
                'element_name' => 'order_shipping_view'
            ]
        ));

        $this->registry->unregister('current_order');
        $this->registry->unregister('sales_order');

        $this->assertNull($this->event->getResult()->getOutput());
    }
}
