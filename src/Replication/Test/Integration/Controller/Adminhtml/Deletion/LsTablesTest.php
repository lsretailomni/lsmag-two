<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Controller\Adminhtml\Deletion;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Controller\Adminhtml\Deletion\LsTables;
use \Ls\Replication\Cron\ReplEcommBarcodesTask;
use \Ls\Replication\Cron\ReplEcommExtendedVariantsTask;
use \Ls\Replication\Cron\ReplEcommImageLinksTask;
use \Ls\Replication\Cron\ReplEcommInventoryStatusTask;
use \Ls\Replication\Cron\ReplEcommItemsTask;
use \Ls\Replication\Cron\ReplEcommItemUnitOfMeasuresTask;
use \Ls\Replication\Cron\ReplEcommItemVariantRegistrationsTask;
use \Ls\Replication\Cron\ReplEcommPricesTask;
use \Ls\Replication\Cron\ReplEcommUnitOfMeasuresTask;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Test\Fixture\FlatDataReplication;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\Manager;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class LsTablesTest extends AbstractBackendController
{
    public const CRON_GRID_URI = 'ls_repl/cron/grid';
    public const ITEM_GRID_NAMESPACE = 'ls_repl_grids_item_listing';
    public const SYSTEM_CONFIG_URI = 'system_config/edit/section/ls_mag';
    public $objectManager;
    public $messageManager;
    public $urlBuilder;

    public $replicationHelper;

    public $storeManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resource   = 'Magento_Backend::admin';
        $this->uri        = 'backend/ls_repl/deletion/lstables';
        $this->httpMethod = HttpRequest::METHOD_GET;
        parent::setUp();

        $this->objectManager     = Bootstrap::getObjectManager();
        $this->messageManager    = $this->objectManager->get(Manager::class);
        $this->urlBuilder        = $this->objectManager->get(UrlInterface::class);
        $this->replicationHelper = $this->objectManager->get(ReplicationHelper::class);
        $this->storeManager      = $this->objectManager->get(StoreManagerInterface::class);
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
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommItemsTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        )
    ]
    public function testExecuteSpecificCron(): void
    {
        $scopeId  = $this->storeManager->getWebsite()->getId();
        $this->getRequest()->setMethod($this->httpMethod);
        $this->setRequiredParams(
            $scopeId,
            ScopeInterface::SCOPE_WEBSITES,
            $scopeId,
            AbstractIntegrationTest::SAMPLE_FLAT_REPLICATION_CRON_NAME
        );

        $this->setReferalUrl(self::CRON_GRID_URI);
        $this->dispatch($this->uri);

        $this->assertRedirectAndMessages(self::CRON_GRID_URI, 1);
    }

    /**
     * @magentoDbIsolation disabled
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
                'job_url' => ReplEcommItemsTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommItemVariantRegistrationsTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommExtendedVariantsTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommItemUnitOfMeasuresTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommUnitOfMeasuresTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommBarcodesTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommImageLinksTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommPricesTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommInventoryStatusTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
    ]
    public function testExecuteAllCron(): void
    {
        $scopeId  = $this->storeManager->getWebsite()->getId();
        $this->getRequest()->setMethod($this->httpMethod);
        $this->setRequiredParams(
            $scopeId,
            ScopeInterface::SCOPE_WEBSITES,
            $scopeId,
            null
        );

        $this->setReferalUrl(self::SYSTEM_CONFIG_URI);
        $this->dispatch($this->uri);

        $mergedTables = array_merge(
            LsTables::LS_DISCOUNT_RELATED_TABLES,
            LsTables::LS_TAX_RELATED_TABLES,
            LsTables::LS_ATTRIBUTE_RELATED_TABLES,
            LsTables::LS_CATEGORY_RELATED_TABLES,
            LsTables::LS_ITEM_RELATED_TABLES,
            LsTables::LS_TABLES,
            LsTables::LS_TRANSLATION_TABLES
        );
        foreach ($mergedTables as $lsTable) {
            $tableName = $this->replicationHelper->getGivenTableName($lsTable);

            if ($scopeId) {
                $websiteId = $this->replicationHelper->getWebsiteIdGivenStoreId($scopeId);
                $query = sprintf("select * from %s where scope_id = '%s'", $tableName, $websiteId);
                $results = $this->replicationHelper->executeGivenQuery($query);
                $this->assertEmpty($results);
            }
        }
        $this->assertRedirectAndMessages(self::SYSTEM_CONFIG_URI, 1);
    }

    public function assertRedirectAndMessages($redirect, $msgCount = 0)
    {
        $this->assertRedirect(
            $this->stringContains($redirect)
        );

        $messages = $this->messageManager->getMessages(false)->getItems();
        if ($msgCount) {
            $this->assertTrue(count($messages) > 0);
        } else {
            $this->assertTrue(count($messages) == 0);
        }
    }

    public function setReferalUrl($uri)
    {
        $redirectUrl            = $this->urlBuilder->getUrl($uri);
        $server                 = $this->getRequest()->getServer();
        $server['HTTP_REFERER'] = $redirectUrl;
        $this->getRequest()->setServer($server);
    }

    public function setRequiredParams($scopeId, $scope, $websiteId, $jobName)
    {
        $this->getRequest()->setParams([
            'scope_id' => $scopeId,
            'scope'    => $scope,
            'website'  => $websiteId,
            'jobname'  => $jobName,
        ]);
    }
}
