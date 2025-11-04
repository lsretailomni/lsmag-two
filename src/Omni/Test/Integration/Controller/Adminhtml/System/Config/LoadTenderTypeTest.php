<?php

namespace Ls\Omni\Test\Integration\Controller\Adminhtml\System\Config;

use Ls\Core\Model\LSR;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\TestCase\AbstractBackendController;

class LoadTenderTypeTest extends AbstractBackendController
{
    /**
     * @var string[]
     */
    public $resource;

    /**
     * @var string
     */
    public $uri;

    /**
     * @var string
     */
    public $httpMethod;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resource   = ['Magento_Backend::admin'];
        $this->uri        = 'backend/omni/system_config/loadStore';
        $this->httpMethod = HttpRequest::METHOD_POST;
        parent::setUp();
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website')    
    ]
    public function testExecute()
    {
        $this->getRequest()->setParam("baseUrl", AbstractIntegrationTest::BASE_URL);
        $this->getRequest()->setParam("tenant", AbstractIntegrationTest::SC_TENANT);
        $this->getRequest()->setParam("client_id", AbstractIntegrationTest::SC_CLIENT_ID);
        $this->getRequest()->setParam("client_secret", AbstractIntegrationTest::SC_CLIENT_SECRET);
        $this->getRequest()->setParam("company_name", AbstractIntegrationTest::SC_COMPANY_NAME);
        $this->getRequest()->setParam("environment_name", AbstractIntegrationTest::SC_ENVIRONMENT_NAME);
        $this->getRequest()->setParam("storeId", AbstractIntegrationTest::CS_STORE);
        $this->getRequest()->setParam("lsKey", '');
        $this->getRequest()->setParam("scopeId", '1');
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('backend/omni/system_config/loadTenderType');

        $content = json_decode($this->getResponse()->getBody());
        $this->assertEquals('true', $content->success);
        $this->assertNotNull($content->storeTenderTypes);
        $this->assertNotEquals(1, count($content->storeTenderTypes));
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_SERVICE_STORE, 'S0000', 'store', 'default'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, 'S0000', 'website')
    ]
    public function testExecuteNullResult()
    {
        $this->getRequest()->setParam("baseUrl", AbstractIntegrationTest::BASE_URL);
        $this->getRequest()->setParam("tenant", AbstractIntegrationTest::SC_TENANT);
        $this->getRequest()->setParam("client_id", AbstractIntegrationTest::SC_CLIENT_ID);
        $this->getRequest()->setParam("client_secret", AbstractIntegrationTest::SC_CLIENT_SECRET);
        $this->getRequest()->setParam("company_name", AbstractIntegrationTest::SC_COMPANY_NAME);
        $this->getRequest()->setParam("environment_name", AbstractIntegrationTest::SC_ENVIRONMENT_NAME);
        $this->getRequest()->setParam("storeId", 'S0000');
        $this->getRequest()->setParam("lsKey", '');
        $this->getRequest()->setParam("scopeId", '1');
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('backend/omni/system_config/loadTenderType');

        $content = json_decode($this->getResponse()->getBody());
        $this->assertEquals('true', $content->success);
        $this->assertNotNull($content->storeTenderTypes);
        $this->assertEquals(1, count($content->storeTenderTypes));
    }
}
