<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Block\Order;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Block\Order\Info;
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
class InfoTest extends TestCase
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
            Info::class
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
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
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
    public function testUsefulInformationSectionForClickAndCollectOrder()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
        $order = $this->getRespectiveOrder();

        if ($order) {
            $output = $this->getHtmlOutputGivenOrder('Ls_Customer::order/useful_info.phtml', $order);
            $this->assertStringContainsString((string) __('Useful Information'), $output);

            if (!empty($order->getStoreName())) {
                $this->assertStringContainsString((string)__('Store Name:'), $output);

                $elementPaths = [
                    "//div[contains(@class, 'block-order-details-view-loyalty')]",
                    "//div[contains(@class, 'block-content')]",
                    sprintf("//span[contains(text(), '%s')]", $order->getStoreName()),
                ];

                $this->validatePaths(
                    $output,
                    $elementPaths,
                    sprintf('Can\'t validate order useful information in Html: %s', $output)
                );
            }

            if ($this->block->canShowClickAndCollect() && $order->getClickAndCollectOrder()) {
                $this->assertStringContainsString((string)__('Click & Collect:'), $output);

                $elementPaths = [
                    "//div[contains(@class, 'block-order-details-view-loyalty')]",
                    "//div[contains(@class, 'block-content')]",
                    sprintf("//span[contains(text(), '%s')]", __('Yes')),
                ];

                $this->validatePaths(
                    $output,
                    $elementPaths,
                    sprintf('Can\'t validate order useful information in Html: %s', $output)
                );
            }
        }
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
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
    public function testUsefulInformationSectionForDeliveryOrder()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
        $order = $this->getRespectiveOrder(false);

        if ($order) {
            $output = $this->getHtmlOutputGivenOrder('Ls_Customer::order/useful_info.phtml', $order);
            $this->assertStringContainsString((string) __('Useful Information'), $output);

            if (!empty($order->getStoreName())) {
                $this->assertStringContainsString((string)__('Store Name:'), $output);

                $elementPaths = [
                    "//div[contains(@class, 'block-order-details-view-loyalty')]",
                    "//div[contains(@class, 'block-content')]",
                    sprintf("//span[contains(text(), '%s')]", $order->getStoreName()),
                ];

                $this->validatePaths(
                    $output,
                    $elementPaths,
                    sprintf('Can\'t validate order useful information in Html: %s', $output)
                );
            }

            if ($this->block->canShowClickAndCollect()) {
                $this->assertStringNotContainsString((string)__('Click & Collect:'), $output);

                $elementPaths = [
                    "//div[contains(@class, 'block-order-details-view-loyalty')]",
                    "//div[contains(@class, 'block-content')]",
                    sprintf("//span[contains(text(), '%s')]", __('Yes')),
                ];

                $this->validatePaths(
                    $output,
                    $elementPaths,
                    sprintf('Can\'t validate order useful information in Html: %s', $output),
                    0
                );
            }
        }
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
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
    public function testOrderInformationSectionForClickAndCollectOrder()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
        $order = $this->getRespectiveOrder();

        if ($order) {
            $output = $this->getHtmlOutputGivenOrder('Ls_Customer::order/info.phtml', $order);
            $this->assertStringContainsString((string)__('Order Information'), $output);
            $this->assertStringNotContainsString((string)__('Shipping Address'), $output);
            $elementPaths = [
                "//div[contains(@class, 'block-order-details-view')]",
                "//div[contains(@class, 'block-content')]",
                "//div[contains(@class, 'box-order-shipping-address')]",
                "//div[contains(@class, 'box-content')]",
            ];

            $this->validatePaths(
                $output,
                $elementPaths,
                sprintf('Can\'t validate order useful information in Html: %s', $output),
                0
            );

            $this->assertStringNotContainsString((string)__('Billing Address'), $output);
            $elementPaths = [
                "//div[contains(@class, 'block-order-details-view')]",
                "//div[contains(@class, 'block-content')]",
                "//div[contains(@class, 'box-order-billing-address')]",
                "//div[contains(@class, 'box-content')]",
            ];

            $this->validatePaths(
                $output,
                $elementPaths,
                sprintf('Can\'t validate order useful information in Html: %s', $output),
                0
            );
            $this->assertStringContainsString((string)__('Shipping Method'), $output);

            $elementPaths = [
                "//div[contains(@class, 'block-order-details-view')]",
                "//div[contains(@class, 'block-content')]",
                "//div[contains(@class, 'box-order-shipping-method')]",
                "//div[contains(@class, 'box-content')]",
            ];

            $this->validatePaths(
                $output,
                $elementPaths,
                sprintf('Can\'t validate order useful information in Html: %s', $output)
            );

            $this->assertStringContainsString((string)__('Payment Method'), $output);

            $elementPaths = [
                "//div[contains(@class, 'block-order-details-view')]",
                "//div[contains(@class, 'block-content')]",
                "//div[contains(@class, 'box-order-billing-method')]",
                "//div[contains(@class, 'box-content')]",
            ];

            $this->validatePaths(
                $output,
                $elementPaths,
                sprintf('Can\'t validate order useful information in Html: %s', $output)
            );
        }
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
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
    public function testOrderInformationSectionForDeliveryOrder()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
        $order = $this->getRespectiveOrder(false);

        if ($order) {
            $output = $this->getHtmlOutputGivenOrder('Ls_Customer::order/info.phtml', $order);
            $this->assertStringContainsString((string)__('Order Information'), $output);
            $this->assertStringNotContainsString((string)__('Shipping Method'), $output);
            $this->assertStringContainsString((string)__('Shipping Address'), $output);

            $elementPaths = [
                "//div[contains(@class, 'block-order-details-view')]",
                "//div[contains(@class, 'block-content')]",
                "//div[contains(@class, 'box-order-shipping-address')]",
                "//div[contains(@class, 'box-content')]",
            ];

            $this->validatePaths(
                $output,
                $elementPaths,
                sprintf('Can\'t validate order useful information in Html: %s', $output)
            );

            $this->assertStringContainsString((string)__('Billing Address'), $output);

            $elementPaths = [
                "//div[contains(@class, 'block-order-details-view')]",
                "//div[contains(@class, 'block-content')]",
                "//div[contains(@class, 'box-order-billing-address')]",
                "//div[contains(@class, 'box-content')]",
            ];

            $this->validatePaths(
                $output,
                $elementPaths,
                sprintf('Can\'t validate order useful information in Html: %s', $output)
            );
            $this->assertStringContainsString((string)__('Payment Method'), $output);

            $elementPaths = [
                "//div[contains(@class, 'block-order-details-view')]",
                "//div[contains(@class, 'block-content')]",
                "//div[contains(@class, 'box-order-billing-method')]",
                "//div[contains(@class, 'box-content')]",
            ];

            $this->validatePaths(
                $output,
                $elementPaths,
                sprintf('Can\'t validate order useful information in Html: %s', $output)
            );
        }
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
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
    public function testOrderDateSection()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
        $order = $this->getRespectiveOrder(false);

        if ($order) {
            $output = $this->getHtmlOutputGivenOrder('Ls_Customer::order/order_date.phtml', $order);

            $elementPaths = [
                "//div[contains(@class, 'order-date')]",
                "//date"
            ];

            $this->validatePaths(
                $output,
                $elementPaths,
                sprintf('Can\'t validate order useful information in Html: %s', $output)
            );
        }
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
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
    public function testOrderStatusSection()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
        $order = $this->getRespectiveOrder(false);

        if ($order) {
            $output = $this->getHtmlOutputGivenOrder('Ls_Customer::order/order_status.phtml', $order);

            $elementPaths = [
                sprintf(
                    "//span[contains(text(), '%s')]",
                    $this->block->getOrderStatus()
                )
            ];

            $this->validatePaths(
                $output,
                $elementPaths,
                sprintf('Can\'t validate order useful information in Html: %s', $output)
            );
        }
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
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
    public function testOrderButtonsSectionWithoutMagentoOrder()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
        $order = $this->getRespectiveOrder(false);

        if ($order) {
            $output = $this->getHtmlOutputGivenOrder('Ls_Customer::order/info/buttons.phtml', $order);
            $this->assertStringNotContainsString((string)__('Reorder'), $output);
            $this->assertStringNotContainsString((string)__('Cancel'), $output);
            $this->assertStringNotContainsString((string)__('Print Order'), $output);
        }
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_ORDER_CANCELLATION_PATH, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
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
    public function testOrderButtonsSectionWithMagentoOrderAndCancelBtnEnabled()
    {
        $magentoOrder = $this->fixtures->get('order');
        $customer     = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
        $centralOrder = $this->orderHelper->fetchOrder($magentoOrder->getDocumentId(), DocumentIdType::ORDER);
        $this->registry->register('current_mag_order', $magentoOrder);

        if ($centralOrder) {
            $output = $this->getHtmlOutputGivenOrder('Ls_Customer::order/info/buttons.phtml', $centralOrder);

            $elementPaths = [
                'reorder' => [
                    "//div[contains(@class, 'actions')]",
                    "//a[contains(@class, 'reorder')]",
                    sprintf("//span[contains(text(), '%s')]", __('Reorder'))
                ],
                'cancel' => [
                    "//div[contains(@class, 'actions')]",
                    "//a[contains(@class, 'cancel')]",
                    sprintf("//span[contains(text(), '%s')]", __('Cancel'))
                ],
                'print' => [
                    "//div[contains(@class, 'actions')]",
                    "//a[contains(@class, 'print')]",
                    sprintf("//span[contains(text(), '%s')]", __('Print Order'))
                ]
            ];

            foreach ($elementPaths as $path) {
                $this->validatePaths(
                    $output,
                    $path,
                    sprintf('Can\'t validate buttons in Html: %s', $output)
                );
            }
        }
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
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
    public function testOrderButtonsSectionWithMagentoOrderAndCancelBtnDisabled()
    {
        $magentoOrder = $this->fixtures->get('order');
        $customer     = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
        $centralOrder = $this->orderHelper->fetchOrder($magentoOrder->getDocumentId(), DocumentIdType::ORDER);
        $this->registry->register('current_mag_order', $magentoOrder);

        if ($centralOrder) {
            $output = $this->getHtmlOutputGivenOrder('Ls_Customer::order/info/buttons.phtml', $centralOrder);
            $this->assertStringNotContainsString((string)__('Cancel'), $output);
        }
    }

    public function validatePaths($output, $ele, $msg, $expected = 1)
    {
        $eleCount = implode('', $ele);
        $this->assertEquals(
            $expected,
            Xpath::getElementsCountForXpath($eleCount, $output),
            $msg
        );
    }

    public function getRespectiveOrder($isClickAndCollect = true)
    {
        $orders = $this->orderHelper->getCurrentCustomerOrderHistory();
        $order  = null;

        foreach ($orders->getSalesEntry() as $order) {
            if (($isClickAndCollect && !$order->getClickAndCollectOrder()) ||
                !$isClickAndCollect && $order->getClickAndCollectOrder()
            ) {
                continue;
            }

            $order = $this->orderHelper->fetchOrder($order->getId(), $order->getIdType());
            break;
        }

        return $order;
    }

    public function getHtmlOutputGivenOrder($template, $order)
    {
        $this->registry->register('current_order', $order);
        $this->block->setData('current_order', $order);
        $this->block->setNameInLayout('sales.order.info');
        $this->block->setTemplate($template);
        $page = $this->pageFactory->create();
        $page->addHandle([
            'default',
            'customer_order_view',
        ]);
        $page->getLayout()->generateXml();

        return $this->block->toHtml();
    }
}
