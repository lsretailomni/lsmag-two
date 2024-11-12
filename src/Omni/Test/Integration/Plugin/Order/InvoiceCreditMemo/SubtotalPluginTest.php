<?php

namespace Ls\Omni\Test\Integration\Plugin\Order\InvoiceCreditMemo;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Plugin\Order\InvoiceCreditMemo\SubtotalPlugin;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Omni\Test\Fixture\CustomerAddressFixture;
use \Ls\Omni\Test\Fixture\CustomerOrder;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice\Total\Subtotal;
use Magento\Sales\Model\Order\Invoice;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoAppArea frontend
 */
class SubtotalPluginTest extends AbstractIntegrationTest
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var SubtotalPlugin
     */
    public $subtotalPlugin;

    /**
     * @var Subtotal
     */
    public $subtotal;

    /**
     * @var CartPlugin
     */
    public $cartPlugin;

    /**
     * @var DataFixtureStorageManager
     */
    public $fixtures;

    /**
     * @var mixed
     */
    public $invoice;

    /**
     * @var InvoiceService
     */
    public $invoiceService;

    /**
     * @var Order
     */
    public $salesOrder;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager  = Bootstrap::getObjectManager();
        $this->fixtures       = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->subtotalPlugin = $this->objectManager->get(SubtotalPlugin::class);
        $this->subtotal       = $this->objectManager->get(Subtotal::class);
        $this->invoice        = $this->objectManager->create(Invoice::class);
        $this->invoiceService = $this->objectManager->create(InvoiceService::class);
        $this->salesOrder     = $this->objectManager->create(Order::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, self::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
        Config(LSR::LSR_ORDER_EDIT, self::LSR_ORDER_EDIT, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, self::LS_CENTRAL_VERSION, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, self::RETAIL_INDUSTRY, 'store', 'default'),
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
                LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180',
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
    public function testAfterCollect()
    {
        $order = $this->fixtures->get('order');

        $orderItems  = $order->getItems();
        $invoiceQtys = [];
        foreach ($orderItems as $orderItem) {
            $invoiceQtys[$orderItem->getItemId()] = 1;
        }
        $invoice = $this->invoiceService->prepareInvoice($order, $invoiceQtys);
        $invoice->getOrder()->setData('items', null);

        $result = $this->subtotalPlugin->afterCollect($this->subtotal, $this->subtotal, $invoice);

        list($expectedSubTotal, $expectedBaseSubTotal) = $this->getCalcTotals($invoice);
        $this->assertEquals($expectedSubTotal, $result->getSubtotal());
        $this->assertEquals($expectedBaseSubTotal, $result->getBaseSubtotal());
    }

    /**
     * @param $invoice
     * @return array
     */
    private function getCalcTotals($invoice): array
    {
        $subtotal = $baseSubTotal = 0;
        foreach ($invoice->getAllItems() as $item) {
            $orderItem      = $invoice->getOrder()->getItemById($item->getOrderItemId());
            $discountAmount = ($orderItem->getDiscountAmount() / $orderItem->getQtyOrdered()) * $item->getQty();
            $taxAmount      = ($orderItem->getTaxAmount() / $orderItem->getQtyOrdered()) * $item->getQty();
            $subtotal       += ($item->getRowTotal() + $discountAmount) - $taxAmount;
            $baseSubTotal   += ($item->getBaseRowTotal()) - $taxAmount;
        }

        return [$subtotal, $baseSubTotal];
    }

}
