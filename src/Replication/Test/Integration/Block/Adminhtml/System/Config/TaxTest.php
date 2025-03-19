<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Block\Adminhtml\System\Config;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Block\Adminhtml\System\Config\Tax;
use \Ls\Replication\Cron\ReplEcommTaxSetupTask;
use \Ls\Replication\Test\Fixture\FlatDataReplication;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class TaxTest extends TestCase
{
    public $objectManager;
    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
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
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommTaxSetupTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
    ]
    public function testToOptionArray(): void
    {
        /** @var $model Tax */
        $model = $this->objectManager->create(
            Tax::class
        );
        $result = $model->toOptionArray();
        $this->assertGreaterThan(1, count($result));
    }
}
