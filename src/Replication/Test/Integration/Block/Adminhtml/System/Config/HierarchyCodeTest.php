<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Block\Adminhtml\System\Config;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Block\Adminhtml\System\Config\HierarchyCode;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class HierarchyCodeTest extends TestCase
{
    public $objectManager;
    public $request;
    public $storeManager;
    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->request       = $this->objectManager->get(
            RequestInterface::class
        );
        $this->storeManager  = $this->objectManager->get(StoreManagerInterface::class);
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
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
    ]
    public function testToOptionArray(): void
    {
        $this->request->setParams([
            'website' => $this->storeManager->getWebsite()->getId()
        ]);
        /** @var $model HierarchyCode */
        $model = Bootstrap::getObjectManager()->create(
            HierarchyCode::class
        );
        $result = $model->toOptionArray();
        $this->assertGreaterThan(1, count($result));
    }
}
