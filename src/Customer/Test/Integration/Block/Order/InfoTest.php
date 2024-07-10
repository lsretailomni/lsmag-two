<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Block\Order;

use Ls\Core\Model\LSR;
use Ls\Customer\Block\Order\Custom\View;
use Ls\Customer\Block\Order\Info;
use Ls\Customer\Test\Fixture\CustomerFixture;
use Ls\Customer\Test\Integration\AbstractIntegrationTest;
use Ls\Omni\Helper\OrderHelper;
use Magento\Customer\Model\Session;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 */
class InfoTest extends TestCase
{
    public $block;
    public $customerSession;
    public $fixtures;
    public $objectManager;
    public $orderHelper;
    private $pageFactory;
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
    public function testUsefulInformationSection()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
        $orders = $this->orderHelper->getCurrentCustomerOrderHistory(2000);


//        $order  = current($orders->getSalesEntry());
        $order  = $this->orderHelper->fetchOrder($order->getId(), $order->getIdType());
        $this->registry->register('current_order', $order);
        $this->block->setData('current_order', $order);
        $this->block->setNameInLayout('custom.order.info');
        $this->block->setTemplate('Ls_Customer::order/useful_info.phtml');
        $page = $this->pageFactory->create();
        $page->addHandle([
            'default',
            'customer_order_view',
        ]);
        $page->getLayout()->generateXml();
        $output = $this->block->toHtml();
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
    public function testOrderInformationSection()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
        $orders = $this->orderHelper->getCurrentCustomerOrderHistory(LSR::MAX_RECENT_ORDER);
        $order  = current($orders->getSalesEntry());
        $order  = $this->orderHelper->fetchOrder($order->getId(), $order->getIdType());
        $this->registry->register('current_order', $order);
        $this->block->setData('current_order', $order);
        $this->block->setNameInLayout('sales.order.info');
        $this->block->setTemplate('Ls_Customer::order/info.phtml');
        $page = $this->pageFactory->create();
        $page->addHandle([
            'default',
            'customer_order_view',
        ]);
        $page->getLayout()->generateXml();
        $output = $this->block->toHtml();
        $this->assertStringContainsString((string) __('Order Information'), $output);

        if (!empty($this->block->getShippingDescription())) {
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
    public function testOrderInformationSectionForDeliveryOrder()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
        $orders = $this->orderHelper->getCurrentCustomerOrderHistory();

        foreach ($orders->getSalesEntry() as $order) {
            if ($order->getClickAndCollectOrder()) {
                continue;
            }

            break;
        }
        $order  = $this->orderHelper->fetchOrder($order->getId(), $order->getIdType());
        $this->registry->register('current_order', $order);
        $this->block->setData('current_order', $order);
        $this->block->setNameInLayout('sales.order.info');
        $this->block->setTemplate('Ls_Customer::order/info.phtml');
        $page = $this->pageFactory->create();
        $page->addHandle([
            'default',
            'customer_order_view',
        ]);
        $page->getLayout()->generateXml();
        $output = $this->block->toHtml();
        $this->assertStringContainsString((string) __('Order Information'), $output);

        if (!empty($this->block->getShippingDescription())) {
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
        }

        if (!empty($order->get)) {
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
        }
    }

    public function validatePaths($output, $ele, $msg)
    {
        $eleCount = implode('', $ele);
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath($eleCount, $output),
            $msg
        );
    }
}
