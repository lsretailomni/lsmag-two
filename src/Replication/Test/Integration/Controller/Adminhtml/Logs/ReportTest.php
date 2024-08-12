<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Controller\Adminhtml\Logs;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Test\Fixture\LogFile;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class ReportTest extends AbstractBackendController
{
    public const LOG_PAGE_URI = 'ls_repl/logs/report';
    public $objectManager;
    public $messageManager;
    public $gridController;
    public $urlBuilder;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resource   = 'Magento_Backend::admin';
        $this->uri        = 'backend/ls_repl/logs/report';
        $this->httpMethod = HttpRequest::METHOD_GET;
        parent::setUp();
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        DataFixture(
            LogFile::class,
            [
                'log_file_name' => AbstractIntegrationTest::SAMPLE_LOG_FILE_NAME
            ]
        ),
    ]
    public function testExecute(): void
    {
        $this->getRequest()->setParams([
            'log_filename' => AbstractIntegrationTest::SAMPLE_LOG_FILE_NAME
        ]);
        $this->getRequest()->setMethod($this->httpMethod);
        $this->dispatch($this->uri);
        $body = $this->getResponse()->getBody();
        $this->assertPageTitleGivenBody($body, __('Logs '));
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        DataFixture(
            LogFile::class,
            [
                'log_file_name' => AbstractIntegrationTest::SAMPLE_LOG_FILE_NAME,
                'len' => 5000010
            ]
        ),
    ]
    public function testExecuteBigFile(): void
    {
        $this->getRequest()->setParams([
            'log_filename' => AbstractIntegrationTest::SAMPLE_LOG_FILE_NAME
        ]);
        $this->getRequest()->setMethod($this->httpMethod);
        $this->dispatch($this->uri);
        $body = $this->getResponse()->getBody();
        $this->assertPageTitleGivenBody($body, __('Logs '));

        $this->assertMsgGivenBody($body, __('File size is too large to render. Please download the file'));
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        DataFixture(
            LogFile::class,
            [
                'log_file_name' => AbstractIntegrationTest::SAMPLE_LOG_FILE_NAME
            ]
        ),
    ]
    public function testExecuteDownload(): void
    {
        $this->getRequest()->setParams([
            'log_filename' => AbstractIntegrationTest::SAMPLE_LOG_FILE_NAME,
            'submission' => 'Download'
        ]);
        $this->getRequest()->setMethod($this->httpMethod);
        $this->dispatch($this->uri);
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        DataFixture(
            LogFile::class,
            [
                'log_file_name' => AbstractIntegrationTest::SAMPLE_LOG_FILE_NAME
            ]
        ),
    ]
    public function testExecuteClear(): void
    {
        $this->getRequest()->setParams([
            'log_filename' => AbstractIntegrationTest::SAMPLE_LOG_FILE_NAME,
            'submission' => 'Clear'
        ]);
        $this->getRequest()->setMethod($this->httpMethod);
        $this->dispatch($this->uri);
        $body = $this->getResponse()->getBody();

        $this->assertPageTitleGivenBody($body, __('Logs '));
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        DataFixture(
            LogFile::class,
            [
                'log_file_name' => AbstractIntegrationTest::SAMPLE_LOG_FILE_NAME,
                'len' => 0
            ]
        ),
    ]
    public function testExecuteNoFile(): void
    {
        $this->getRequest()->setParams([
            'log_filename' => AbstractIntegrationTest::SAMPLE_LOG_FILE_NAME
        ]);
        $this->getRequest()->setMethod($this->httpMethod);
        $this->dispatch($this->uri);
        $body = $this->getResponse()->getBody();
        $this->assertPageTitleGivenBody($body, __('Logs '));
        $this->assertMsgGivenBody($body, __('File Not Found.'));
    }

    public function assertPageTitleGivenBody($body, $name)
    {
        $this->assertStringContainsString(
            sprintf('<h1 class="page-title">%s</h1>', (string)$name),
            $body
        );
    }

    public function assertMsgGivenBody($body, $msg)
    {
        $this->assertStringContainsString(
            (string)$msg,
            $body
        );
    }
}
