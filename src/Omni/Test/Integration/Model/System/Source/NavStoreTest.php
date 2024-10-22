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
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, AbstractIntegrationTest::RETAIL_INDUSTRY, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION),
        Config(LSR::LS_INDUSTRY_VALUE, AbstractIntegrationTest::RETAIL_INDUSTRY),
    ]
    public function testGetNavStores()
    {
        $this->request->setParams(['website', 1]);
        $result = $this->navStore->getNavStores();
        $this->assertNotEquals([], $result);
        $this->assertGreaterThan('0', count($result->getStore()));
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, AbstractIntegrationTest::RETAIL_INDUSTRY, 'store', 'default')

    ]
    public function testGetNavStoresNull()
    {
        $this->request->setParams(['website', 1]);
        $result = $this->navStore->getNavStores();
        $this->assertEquals([], $result);
    }
}
