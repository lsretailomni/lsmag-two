<?php

namespace Ls\Omni\Test\Integration\Controller\Adminhtml\System\Config;

use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\TestFramework\TestCase\AbstractBackendController;

class LoadHierarchyTest extends AbstractBackendController
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
        $this->uri        = 'backend/omni/system_config/loadHierarchy';
        $this->httpMethod = HttpRequest::METHOD_POST;
        parent::setUp();
    }

    public function testExecute()
    {
        $this->getRequest()->setParam("baseUrl", AbstractIntegrationTest::CS_URL);
        $this->getRequest()->setParam("storeId", AbstractIntegrationTest::CS_STORE);
        $this->getRequest()->setParam("lsKey", '');
        $this->getRequest()->setParam("scopeId", '1');
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('backend/omni/system_config/loadHierarchy');

        $content = json_decode($this->getResponse()->getBody());
        $this->assertEquals('true', $content->success);
        $this->assertNotNull($content->hierarchy);
        $this->assertNotEquals(1, count($content->hierarchy));
    }

    public function testExecuteNullResult()
    {
        $this->getRequest()->setParam("baseUrl", AbstractIntegrationTest::CS_URL);
        $this->getRequest()->setParam("storeId", 'S0000');
        $this->getRequest()->setParam("lsKey", '');
        $this->getRequest()->setParam("scopeId", '5');
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('backend/omni/system_config/loadHierarchy');

        $content = json_decode($this->getResponse()->getBody());
        $this->assertEquals('true', $content->success);
        $this->assertNotNull($content->hierarchy);
        $this->assertEquals(1, count($content->hierarchy));
    }
}
