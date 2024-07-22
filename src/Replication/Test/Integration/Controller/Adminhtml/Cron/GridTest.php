<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Controller\Adminhtml\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\Manager;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManager;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class GridTest extends AbstractBackendController
{
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
        $this->uri        = 'backend/ls_repl/cron/grid';
        $this->httpMethod = HttpRequest::METHOD_GET;
        parent::setUp();

        $this->objectManager  = Bootstrap::getObjectManager();
        $this->messageManager = $this->objectManager->get(Manager::class);
        $this->urlBuilder     = $this->objectManager->get(UrlInterface::class);
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
    ]
    public function testExecuteRedirect(): void
    {
        $this->getRequest()->setMethod($this->httpMethod);
        $this->dispatch($this->uri);

        $this->assertRedirectAndMessages();
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
    ]
    public function testExecuteWithParams(): void
    {
        $this->getRequest()->setMethod($this->httpMethod);
        $scopeId = 1;
        $this->setRequiredParams(
            $scopeId,
            ScopeInterface::SCOPE_WEBSITES,
            $scopeId,
            AbstractIntegrationTest::SAMPLE_REPLICATION_CRON_NAME,
            AbstractIntegrationTest::SAMPLE_REPLICATION_CRON_URL
        );
        $this->setReferalUrl();
        $this->dispatch($this->uri);

        $this->assertRedirectAndMessages(1);
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(StoreManager::XML_PATH_SINGLE_STORE_MODE_ENABLED, AbstractIntegrationTest::ENABLED, 'store', 'default')
    ]
    public function testExecuteWithSingleStoreModeEnabledAndWithParams(): void
    {
        $this->getRequest()->setMethod($this->httpMethod);

        $scopeId = 0;
        $this->setRequiredParams(
            $scopeId,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            null,
            AbstractIntegrationTest::SAMPLE_REPLICATION_CRON_NAME,
            AbstractIntegrationTest::SAMPLE_REPLICATION_CRON_URL
        );
        $this->setReferalUrl();
        $this->dispatch($this->uri);

        $this->assertRedirectAndMessages(1);
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(StoreManager::XML_PATH_SINGLE_STORE_MODE_ENABLED, AbstractIntegrationTest::ENABLED, 'store', 'default')
    ]
    public function testExecuteWithSingleStoreModeEnabledAndWithoutParams(): void
    {
        $this->getRequest()->setMethod($this->httpMethod);

        $this->dispatch($this->uri);
        $this->assertRedirect(
            $this->stringContains('ls_repl/cron/grid')
        );

        foreach ($this->getResponse()->getHeaders() as $header) {
            if ($header->getFieldName() == 'Location') {
                $actualUrl = $header->getFieldValue();
                break;
            }
        }
        if (!empty($actualUrl)) {
            $this->assertStringContainsString('scope_id/0', $actualUrl);
            $this->assertStringContainsString(
                'scope/' . ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $actualUrl
            );
        }
    }

    public function testExecuteWithLsrDown(): void
    {
        $this->getRequest()->setMethod($this->httpMethod);
        $scopeId = 1;
        $this->setRequiredParams(
            $scopeId,
            ScopeInterface::SCOPE_WEBSITES,
            $scopeId,
            AbstractIntegrationTest::SAMPLE_REPLICATION_CRON_NAME,
            AbstractIntegrationTest::SAMPLE_REPLICATION_CRON_URL
        );
        $this->setReferalUrl();
        $this->dispatch($this->uri);
        $this->assertRedirectAndMessages(1);
    }

    public function assertRedirectAndMessages($msgCount = 0)
    {
        $this->assertRedirect(
            $this->stringContains('ls_repl/cron/grid')
        );

        $messages = $this->messageManager->getMessages(false)->getItems();
        if ($msgCount) {
            $this->assertTrue(count($messages) > 0);
        } else {
            $this->assertTrue(count($messages) == 0);
        }
    }

    public function setReferalUrl()
    {
        $redirectUrl            = $this->urlBuilder->getUrl('ls_repl/cron/grid');
        $server                 = $this->getRequest()->getServer();
        $server['HTTP_REFERER'] = $redirectUrl;
        $this->getRequest()->setServer($server);
    }

    public function setRequiredParams($scopeId, $scope, $websiteId, $jobName, $jobUrl)
    {
        $this->getRequest()->setParams([
            'scope_id' => $scopeId,
            'scope'    => $scope,
            'website'  => $websiteId,
            'jobname'  => $jobName,
            'joburl'   => $jobUrl
        ]);
    }
}
