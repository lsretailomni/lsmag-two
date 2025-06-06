<?php

namespace Ls\Omni\Test\Integration\Controller\Adminhtml\Order;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Backend\Model\Session\Quote;
use Magento\Checkout\Model\Session;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddressFixture;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\Checkout\Model\Session as CheckoutSession;

class RequestTest extends AbstractBackendController
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
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * @var ItemHelper
     */
    public $itemHelper;

    /**
     * @var Quote
     */
    public $quoteSession;

    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    /**
     * @var ManagerInterface
     */
    public $eventManager;

    /**
     * @var Create
     */
    public $model;

    /**
     * @var OrderRepositoryInterface
     */
    public $orderRepository;

    /**
     * @var FormKey
     */
    public $formKey;

    /**
     * @var PaymentMethodManagementInterface
     */
    public $paymentManagement;

    /**
     * @var CartManagementInterface
     */
    public $cartManagement;

    /**
     * @var CustomerSession
     */
    public $customerSession;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager     = Bootstrap::getObjectManager();
        $this->model             = $this->objectManager->get(Create::class);
        $this->fixtures          = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->eventManager      = $this->objectManager->create(ManagerInterface::class);
        $this->quoteSession      = $this->objectManager->create(Quote::class);
        $this->checkoutSession   = $this->objectManager->create(CheckoutSession::class);
        $this->basketHelper      = $this->objectManager->get(BasketHelper::class);
        $this->itemHelper        = $this->objectManager->get(ItemHelper::class);
        $this->orderRepository   = $this->objectManager->get(OrderRepositoryInterface::class);
        $this->formKey           = $this->objectManager->get(FormKey::class);
        $this->paymentManagement = $this->objectManager->get(PaymentMethodManagementInterface::class);
        $this->cartManagement    = $this->objectManager->get(CartManagementInterface::class);
        $this->customerSession   = $this->objectManager->get(CustomerSession::class);

        $this->resource   = ['Magento_Backend::admin'];
        $this->uri        = 'backend/omni/order/request';
        $this->httpMethod = HttpRequest::METHOD_GET;
        parent::setUp();
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, AbstractIntegrationTest::RETAIL_INDUSTRY, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LICENSE, 'website'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::LS_MAG_ENABLE, 'website'),
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
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart1.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart1.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart1.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart1.id$'])
    ]
    public function testExecute(): void
    {
        $customer = $this->fixtures->get('customer');
        $quote    = $this->fixtures->get('cart1');
        $quote->setReservedOrderId('integration-test-1');

        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $quote->getAllVisibleItems()]);

        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
        $this->quoteSession->setQuoteId($quote->getId());

        $oneList = $this->basketHelper->getOneListAdmin(
            $quote->getCustomerEmail(),
            $quote->getStore()->getWebsiteId(),
            false
        );

        $oneList = $this->basketHelper->setOneListQuote($quote, $oneList);
        $this->checkoutSession->setQuoteId($quote->getId());
        $basketData = $this->basketHelper->update($oneList);
        $quote      = $this->quoteSession->getQuote();
        $this->itemHelper->setDiscountedPricesForItems($quote, $basketData, 2);
        $this->quoteSession->setQuote($quote);
        $this->model->setQuote($quote);
        $paymentMethod = $this->paymentManagement->get($quote->getId());
        $orderId       = (int)$this->cartManagement->placeOrder($quote->getId(), $paymentMethod);

        $orderData = [
            'order_id' => $orderId,
            'form_key' => $this->formKey->getFormKey()
        ];

        $this->getRequest()->setParams($orderData);
        $this->getRequest()->setMethod(HttpRequest::METHOD_GET);
        $this->dispatch('backend/omni/order/request');

        $order = $this->orderRepository->get($orderId);
        $this->assertNotNull($order->getDocumentId());
    }

    public function testAclHasAccess(): void
    {
        // Skip ACL check
    }
}
