<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Ui\Component;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Cron\ReplEcommItemsTask;
use \Ls\Replication\Test\Fixture\FlatDataReplication;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\Api\Filter;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class ItemDataProviderTest extends TestCase
{
    public const DATA_SOURCE_NAME = 'ls_repl_grids_item_data_source';

    public $providerData = [
        'name'             => self::DATA_SOURCE_NAME,
        'primaryFieldName' => 'repl_item_id',
        'requestFieldName' => 'id',
    ];

    /** @var ObjectManagerInterface */
    public $objectManager;

    public $dataProvider;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager    = Bootstrap::getObjectManager();
        $this->dataProvider = $this->objectManager->create(
            DataProvider::class,
            $this->providerData
        );
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommItemsTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        )
    ]
    public function testData()
    {
        $data = $this->dataProvider->getData();

        $this->assertGreaterThanOrEqual(1, $data['items']);
    }

    /**
     * @dataProvider getDataByNavIdProvider
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommItemsTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        )
    ]
    public function testFilteredData(array $filterData)
    {
        $filter = $this->objectManager->create(
            Filter::class,
            ['data' => $filterData]
        );
        $this->dataProvider->addFilter($filter);
        $data = $this->dataProvider->getData();
        $this->assertEquals(1, $data['totalRecords']);
        $this->assertCount(1, $data['items']);
        $this->assertEquals($filterData['value'], $data['items'][0]['nav_id']);
    }

    /**
     * @return array
     */
    public function getDataByNavIdProvider(): array
    {
        return [
            [['condition_type' => 'eq', 'field' => 'nav_id', 'value' => '40180']],
        ];
    }
}
