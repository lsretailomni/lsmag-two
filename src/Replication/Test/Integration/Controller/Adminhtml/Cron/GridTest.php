<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Controller\Adminhtml\Cron;

use Ls\Core\Model\LSR;
use Ls\Customer\Test\Integration\AbstractIntegrationTest;
use Ls\Omni\Helper\ContactHelper;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\Manager;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;

class GridTest extends AbstractBackendController
{
    public $objectManager;
    public $contactHelper;
    public $fixtures;
    public $messageManager;
    public $gridController;

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
        $this->contactHelper  = $this->objectManager->get(ContactHelper::class);
        $this->fixtures       = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->messageManager = $this->objectManager->get(Manager::class);
        $this->gridController = $this->objectManager->get(\Ls\Replication\Controller\Adminhtml\Cron\Grid::class);
    }

//    /**
//     * @magentoAppIsolation enabled
//     */
//    #[
//        AppArea('adminhtml'),
//        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
//        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
//        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
//        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
//        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
//        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
//        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
//        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
//        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
//    ]
//    public function testExecuteRedirect(): void
//    {
//        $this->getRequest()->setMethod(HttpRequest::METHOD_GET);
//        $this->dispatch('backend/ls_repl/cron/grid');
//
//        $this->assertRedirect(
//            $this->stringContains('ls_repl/cron/grid')
//        );
//    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('adminhtml'),
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
        $this->getRequest()->setMethod(HttpRequest::METHOD_GET);
        $scopeId = $this->gridController->getDefaultWebsiteId();
        $this->getRequest()->setParams([
            'scope_id' => $scopeId,
            'scope'    => ScopeInterface::SCOPE_WEBSITES,
            'website'  => $scopeId,
            'jobname'  => 'repl_item',
            'joburl'   => 'Ls\Replication\Cron\ReplEcommItemsTask'
        ]);

        $this->dispatch('backend/ls_repl/cron/grid');

//        $this->assertRedirect(
//            $this->stringContains('ls_repl/cron/grid')
//        );

        $messages = $this->messageManager->getMessages(false)->getItems();
        $this->assertTrue(count($messages) > 0);
    }
}
