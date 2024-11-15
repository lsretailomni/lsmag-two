<?php

namespace Ls\Omni\Test\Integration\Controller\Stock;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Test\Fixture\FlatDataReplication;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use \Ls\Replication\Cron\ReplEcommStoresTask;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\Store\Model\ScopeInterface;

class ProductTest extends AbstractController
{
    /**
     * @var DataFixtureStorageManager
     */
    public $fixtures;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->fixtures      = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, AbstractIntegrationTest::RETAIL_INDUSTRY, 'store', 'default'),

        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'website'),
        DataFixture(
            CreateSimpleProductFixture::class,
            [
                LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180',
            ],
            as: 'p1'
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommStoresTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ],
            as: 'stores'
        )
    ]
    public function testExecute()
    {
        $prod   = $this->fixtures->get('p1');
        $stores = $this->fixtures->get('stores');
        $params = [
            'sku' => $prod->getSku(),
            'id'  => ''
        ];
        $this->getRequest()->setParams($params);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->getHeaders()
            ->addHeaderLine('X_REQUESTED_WITH', 'XMLHttpRequest');

        $this->dispatch('omni/stock/Product');
        $content = json_decode($this->getResponse()->getBody());
        $this->assertNotNull($content->content);
        $this->assertNotNull($content->stocks);
        $this->assertStringContainsString("totalRecords", $content->stocks);
    }
}
