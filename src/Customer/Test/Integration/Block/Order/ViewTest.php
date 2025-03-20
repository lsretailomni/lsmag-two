<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Block\Order;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Block\Order\Custom\View;
use \Ls\Customer\Test\Fixture\CreateSimpleProduct;
use \Ls\Customer\Test\Fixture\CustomerAddressFixture;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Customer\Test\Fixture\CustomerOrder;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DocumentIdType;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Customer\Model\Session;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class ViewTest extends TestCase
{
    public $block;
    public $customerSession;
    public $fixtures;
    public $objectManager;
    public $orderHelper;
    public $pageFactory;
    public $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager   = Bootstrap::getObjectManager();
        $this->block           = $this->objectManager->get(
            LayoutInterface::class
        )->createBlock(
            View::class
        );
        $this->orderHelper     = $this->objectManager->get(OrderHelper::class);
        $this->customerSession = $this->objectManager->get(Session::class);
        $this->pageFactory     = $this->objectManager->get(PageFactory::class);
        $this->fixtures        = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->registry        = $this->objectManager->get(Registry::class);
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        Config(LSR::SC_ORDER_CANCELLATION_PATH, AbstractIntegrationTest::ENABLED, 'store', 'default'),
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
            CustomerAddressFixture::class,
            [
                'customer_id' => '$customer.entity_id$'
            ],
            as: 'address'
        ),
        DataFixture(
            CreateSimpleProduct::class,
            [
                'lsr_item_id' => '40180',
                'sku'         => '40180'
            ],
            as: 'product'
        ),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart1'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$product.id$', 'qty' => 1]),
        DataFixture(
            CustomerOrder::class,
            [
                'customer' => '$customer$',
                'cart1'    => '$cart1$',
                'address'  => '$address$'
            ],
            as: 'order'
        )
    ]
    public function testOrderViewWithMagentoOrder()
    {
        $magentoOrder = $this->fixtures->get('order');
        $customer     = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
        $centralOrder = $this->orderHelper->fetchOrder($magentoOrder->getDocumentId(), DocumentIdType::ORDER);
        $this->registry->register('current_mag_order', $magentoOrder);
        $this->registry->register('current_order', $centralOrder);
        $this->block->setData('current_order', $centralOrder);
        $this->block->setNameInLayout('sales.order.view');
        $page = $this->pageFactory->create();
        $page->addHandle([
            'default',
            'customer_order_view',
        ]);
        $page->getLayout()->generateXml();
        $output = $this->block->toHtml();
        $this->validateOrderItemsLabelsPath($output);
        $this->validateOrderTotalsValuesPath($output);
        $this->validateOrderTotalsLabelsPath($output);
        $this->validateOrderItemsValuesPath($output);
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id'       => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid'   => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token'    => AbstractIntegrationTest::CUSTOMER_ID
            ],
            as: 'customer'
        )
    ]
    public function testOrderViewWitoutMagentoOrder()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
        $orders = $this->orderHelper->getCurrentCustomerOrderHistory(LSR::MAX_RECENT_ORDER);
        $order  = current($orders->getSalesEntry());
        $order  = $this->orderHelper->fetchOrder($order->getId(), $order->getIdType());
        $this->registry->register('current_order', $order);
        $this->block->setData('current_order', $order);
        $this->block->setNameInLayout('sales.order.view');
        $page = $this->pageFactory->create();
        $page->addHandle([
            'default',
            'customer_order_view',
        ]);
        $page->getLayout()->generateXml();
        $output = $this->block->toHtml();
        $this->validateOrderItemsLabelsPath($output);
        $this->validateOrderTotalsValuesPath($output);
        $this->validateOrderTotalsLabelsPath($output);
        $this->validateOrderItemsValuesPath($output);
    }

    public function validateOrderTotalsValuesPath($output)
    {
        $orderTotalsValuesPaths = [
            'subtotal' => [
                "//tr[contains(@class, 'subtotal')]",
                "//td",
                "//span"
            ],
            'shipping' => [
                "//tr[contains(@class, 'shipping')]",
                "//td",
                "//span"
            ],
            'tax' => [
                "//tr[contains(@class, 'totals-tax')]",
                "//td",
                "//span"
            ],
            'grand_total' => [
                "//tr[contains(@class, 'grand_total')]",
                "//td",
                "//strong",
                "//span"
            ],
        ];

        $msg = sprintf('Can\'t validate order totals values in Html: %s', $output);
        $this->validatePaths($output, $orderTotalsValuesPaths, $msg);
    }
    public function validateOrderTotalsLabelsPath($output)
    {
        $orderTotalsLabelsPaths = [
            'subtotal' => [
                "//tr[contains(@class, 'subtotal')]",
                sprintf("//th[contains(text(), '%s')]", __('Subtotal (Inc.Tax)'))
            ],
            'shipping' => [
                "//tr[contains(@class, 'shipping')]",
                sprintf("//th[contains(text(), '%s')]", __('Shipment & Handling'))
            ],
            'tax'      => [
                "//tr[contains(@class, 'totals-tax')]",
                sprintf("//th[contains(text(), '%s')]", __('Tax'))
            ],
            'grand_total'      => [
                "//tr[contains(@class, 'grand_total')]",
                "//th",
                sprintf("//strong[contains(text(), '%s')]", __('Total'))
            ]
        ];
        $msg = sprintf('Can\'t validate order totals paths in Html: %s', $output);
        $this->validatePaths($output, $orderTotalsLabelsPaths, $msg);
    }

    public function validateOrderItemsLabelsPath($output)
    {
        $orderItemsLabelPath = [
            'product_name' => [
                "//div[contains(@class, 'order-items')]",
                "//thead",
                "//tr",
                sprintf("//th[contains(text(), '%s')]", __('Product Name'))
            ],
            'sku' => [
                "//div[contains(@class, 'order-items')]",
                "//thead",
                "//tr",
                sprintf("//th[contains(text(), '%s')]", __('SKU'))
            ],
            'price' => [
                "//div[contains(@class, 'order-items')]",
                "//thead",
                "//tr",
                sprintf("//th[contains(text(), '%s')]", __('Price'))
            ],
            'qty' => [
                "//div[contains(@class, 'order-items')]",
                "//thead",
                "//tr",
                sprintf("//th[contains(text(), '%s')]", __('Qty'))
            ],
            'subtotal' => [
                "//div[contains(@class, 'order-items')]",
                "//thead",
                "//tr",
                sprintf("//th[contains(text(), '%s')]", __('Subtotal'))
            ]
        ];

        $msg = sprintf('Can\'t validate order items labels in Html: %s', $output);
        $this->validatePaths($output, $orderItemsLabelPath, $msg);
    }

    public function validateOrderItemsValuesPath($output)
    {
        $orderItemsValuesPath = [
            'product_name' => [
                "//div[contains(@class, 'order-items')]",
                "//tbody",
                "//tr",
                "//td[contains(@class, 'name')]",
                "//strong"
            ],
            'sku' => [
                "//div[contains(@class, 'order-items')]",
                "//tbody",
                "//tr",
                "//td[contains(@class, 'sku')]",
            ],
            'price' => [
                "//div[contains(@class, 'order-items')]",
                "//tbody",
                "//tr",
                "//td[contains(@class, 'price')]",
            ],
            'qty' => [
                "//div[contains(@class, 'order-items')]",
                "//tbody",
                "//tr",
                "//td[contains(@class, 'qty')]",
            ],
            'subtotal' => [
                "//div[contains(@class, 'order-items')]",
                "//tbody",
                "//tr",
                "//td[contains(@class, 'subtotal')]",
            ]
        ];

        $msg = sprintf('Can\'t validate order items values in Html: %s', $output);
        $this->validatePathsGreaterThanOrEqualTo($output, $orderItemsValuesPath, $msg);
    }

    public function validatePaths($output, $elementPaths, $msg)
    {
        foreach ($elementPaths as $index => $ele) {
            $eleCount = implode('', $ele);
            $this->assertEquals(
                1,
                Xpath::getElementsCountForXpath($eleCount, $output),
                $msg
            );
        }
    }

    public function validatePathsGreaterThanOrEqualTo($output, $elementPaths, $msg)
    {
        foreach ($elementPaths as $index => $ele) {
            $eleCount = implode('', $ele);
            $this->assertGreaterThanOrEqual(
                1,
                Xpath::getElementsCountForXpath($eleCount, $output),
                $msg
            );
        }
    }
}
