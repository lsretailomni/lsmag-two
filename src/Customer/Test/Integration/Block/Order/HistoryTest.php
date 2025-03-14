<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Block\Order;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Block\Order\History;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use Magento\Customer\Model\Session;
use Magento\Framework\View\LayoutInterface;
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
class HistoryTest extends TestCase
{
    public $block;
    public $customerSession;
    public $fixtures;
    public $objectManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->block         = $this->objectManager->get(
            LayoutInterface::class
        )->createBlock(
            History::class
        );

        $this->customerSession = $this->objectManager->get(Session::class);
        $this->fixtures        = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
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
    public function testOrderHistory()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
        $this->block->setTemplate('Ls_Customer::order/history.phtml');
        $orders = $this->block->getOrderHistory()->getSalesEntry();
        $output = $this->block->toHtml();

        if (count($orders)) {
            $columnLabels = [
                __('Doc ID'),
                __('Date'),
                __('Ship To'),
                __('Store Name'),
                __('Total'),
                __('Status'),
                __('Action')
            ];
            $this->validateTableColumns($output, $columnLabels);
            $columns = [
                'id',
                'date',
                'shipping',
                'store-name',
                'total',
                'status',
                'action'
            ];
            foreach ($columns as $column) {
                $elementPaths = [
                    "//table[contains(@class, 'table-order-items')]",
                    "//tbody",
                    "//tr",
                    sprintf("//td[contains(@class, '%s')]", $column)
                ];

                $this->validatePaths(
                    $output,
                    $elementPaths,
                    sprintf('Can\'t validate order history table: %s', $output),
                    1,
                    -1
                );
            }
        }
    }

    #[
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
    public function testOrderHistoryWithLsrDown()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->block->setTemplate('Ls_Customer::order/history.phtml');

        $output = $this->block->toHtml();
        $columnLabels = [
            __('Doc ID'),
            __('Date'),
            __('Ship To'),
            __('Total'),
            __('Status'),
            __('Action')
        ];
        $this->validateTableColumns($output, $columnLabels);
        $columns = [
            'id',
            'date',
            'shipping',
            'store-name',
            'total',
            'status',
            'action'
        ];
        foreach ($columns as $column) {
            $elementPaths = [
                "//table[contains(@class, 'table-order-items')]",
                "//tbody",
                "//tr",
                sprintf("//td[contains(@class, '%s')]", $column)
            ];

            $this->validatePaths(
                $output,
                $elementPaths,
                sprintf('Can\'t validate order history table: %s', $output),
                0
            );
        }
    }

    public function validateTableColumns($output, $columnLabels)
    {
        foreach ($columnLabels as $label) {
            $this->assertStringContainsString((string)$label, $output);
        }
    }

    public function validatePaths($output, $ele, $msg, $expected = 1, $condition = 1)
    {
        $eleCount = implode('', $ele);

        if ($condition == 1) {
            $this->assertEquals(
                $expected,
                Xpath::getElementsCountForXpath($eleCount, $output),
                $msg
            );
        } else {
            $this->assertGreaterThanOrEqual(
                $expected,
                Xpath::getElementsCountForXpath($eleCount, $output),
                $msg
            );
        }
    }
}
