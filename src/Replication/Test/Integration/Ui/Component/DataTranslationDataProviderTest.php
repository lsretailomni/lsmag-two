<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Ui\Component;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Cron\ReplEcommDataTranslationTask;
use \Ls\Replication\Test\Fixture\FlatDataReplication;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class DataTranslationDataProviderTest extends AbstractDataProvider
{
    public const DATA_SOURCE_NAME = 'ls_repl_grids_data_translation_data_source';
    public const PRIMARY_FIELD_NAME = 'repl_data_translation_id';
    public const REQUEST_FIELD_NAME = 'id';
    public const SEARCH_FIELD_NAME = 'Key';

    public function getSearchFieldName()
    {
        return self::SEARCH_FIELD_NAME;
    }

    public function getSearchFieldValue()
    {
        return AbstractIntegrationTest::SAMPLE_SIMPLE_ITEM_ID;
    }

    public function getDataSourceName()
    {
        return self::DATA_SOURCE_NAME;
    }

    public function getPrimaryFieldName()
    {
        return self::PRIMARY_FIELD_NAME;
    }

    public function getRequestFieldName()
    {
        return self::REQUEST_FIELD_NAME;
    }

//    #[
//        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
//        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
//        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
//        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
//        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
//        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
//        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
//        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'website'),
//        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE),
//        DataFixture(
//            FlatDataReplication::class,
//            [
//                'job_url' => ReplEcommDataTranslationTask::class,
//                'scope'   => ScopeInterface::SCOPE_WEBSITE
//            ]
//        )
//    ]
//    public function testData()
//    {
//        $this->assertDataExists();
//    }

    /**
     * @dataProvider getDataByIdProvider
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'website'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommDataTranslationTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        )
    ]
    public function testFilteredData(array $filterData)
    {
        $data = $this->applyFilterToData($filterData);
        $this->assertArrayHasKey('totalRecords', $data);
    }
}
