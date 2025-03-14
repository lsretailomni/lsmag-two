<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Block\Adminhtml\Logs;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Test\Fixture\LogFile;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use \Ls\Replication\Block\Adminhtml\Logs\Report;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class ReportTest extends TestCase
{
    public $objectManager;
    public $block;
    public $request;
    public $fixtures;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->block         = $this->objectManager->get(
            LayoutInterface::class
        )->createBlock(
            Report::class
        );
        $this->request       = $this->objectManager->get(
            RequestInterface::class
        );
        $this->fixtures        = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('adminhtml'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        DataFixture(
            LogFile::class,
            [
                'log_file_name' => AbstractIntegrationTest::SAMPLE_LOG_FILE_NAME,
                'len' => 5000010
            ],
            as: 'message'
        ),
    ]
    public function testGetQueryUrlData(): void
    {
        $message = $this->fixtures->get('message');
        $this->request->setParams([
            'log_filename' => AbstractIntegrationTest::SAMPLE_LOG_FILE_NAME
        ]);
        $this->block->setTemplate('Ls_Replication::logs/reports.phtml')->setData('message', $message['message']);
        $output = $this->block->toHtml();

        $elementPaths = [
            "//form[contains(@id, 'logs')]",
            "//select[contains(@class, 'admin__control-select')]",
            "//option[@value='" . AbstractIntegrationTest::SAMPLE_LOG_FILE_NAME . "' and @selected='selected']",
        ];
        $eleCount     = implode('', $elementPaths);

        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath($eleCount, $output),
            sprintf('Can\'t validate selected log file: %s', $output)
        );
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('adminhtml'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website')
    ]
    public function testGetQueryUrlDataNoSelection(): void
    {
        $this->block->setTemplate('Ls_Replication::logs/reports.phtml');
        $output = $this->block->toHtml();

        $elementPaths = [
            "//form[contains(@id, 'logs')]",
            "//select[contains(@class, 'admin__control-select')]",
            "//option[@selected='selected']",
        ];
        $eleCount     = implode('', $elementPaths);

        $this->assertEquals(
            0,
            Xpath::getElementsCountForXpath($eleCount, $output),
            sprintf('Can\'t validate selected log file: %s', $output)
        );
    }
}
