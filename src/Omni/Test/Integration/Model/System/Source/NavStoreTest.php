<?php

namespace Ls\Omni\Test\Integration\Model\System\Source;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Model\System\Source\NavStore;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class NavStoreTest extends TestCase
{

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var DataFixtureStorageManager
     */
    public $fixtures;

    /**
     * @var HttpRequest
     */
    public $request;

    /**
     * @var NavStore
     */
    public $navStore;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->fixtures      = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->navStore      = $this->objectManager->get(NavStore::class);
        $this->request       = $this->objectManager->get(HttpRequest::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default' ),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website' ),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'store', 'default'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'store', 'default'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'store', 'default'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, AbstractIntegrationTest::RETAIL_INDUSTRY, 'store', 'default'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::LS_MAG_ENABLE, 'website'),
    ]
    public function testGetNavStores()
    {
        $this->request->setParams(['website' => 1]);
        $result = $this->navStore->getNavStores();
        $this->assertNotEquals([], $result);
        $this->assertGreaterThan('0', count($result));
    }   
}
