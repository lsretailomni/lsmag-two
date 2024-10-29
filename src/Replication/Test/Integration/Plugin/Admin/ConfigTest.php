<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Plugin\Admin;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class ConfigTest extends TestCase
{
    /**
     * @var \Magento\Backend\Model\Menu
     */
    private $model;

    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager   = Bootstrap::getObjectManager();
        $this->model = $this->objectManager->create(\Magento\Backend\Model\Auth::class);
        $this->objectManager->get(\Magento\Framework\Config\ScopeInterface::class)
            ->setCurrentScope(\Magento\Backend\App\Area\FrontNameResolver::AREA_CODE);
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, '2023.0.0', 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, '2023.0.0', 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
    ]
    public function testOldAfterGetMenu()
    {
        $menu = $this->objectManager->create(\Magento\Backend\Model\Menu\Config::class)->getMenu();
        $val1 = $menu->get('Ls_Replication::discount_setup_grid');
        $val2 = $menu->get('Ls_Replication::discount_validation_grid');
        $val3 = $menu->get('Ls_Replication::discount_grid');
        $this->assertNull($val1);
        $this->assertNull($val2);
        $this->assertNotNull($val3);
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
    ]
    public function testNewAfterGetMenu()
    {
        $menu = $this->objectManager->create(\Magento\Backend\Model\Menu\Config::class)->getMenu();
        $val1 = $menu->get('Ls_Replication::discount_setup_grid');
        $val2 = $menu->get('Ls_Replication::discount_validation_grid');
        $val3 = $menu->get('Ls_Replication::discount_grid');
        $this->assertNotNull($val1);
        $this->assertNotNull($val2);
        $this->assertNull($val3);
    }
}
