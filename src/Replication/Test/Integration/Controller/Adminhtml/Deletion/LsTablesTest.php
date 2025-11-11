<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Controller\Adminhtml\Deletion;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Controller\Adminhtml\Deletion\LsTables;
use \Ls\Replication\Cron\ReplLscBarcodesTask;
use \Ls\Replication\Cron\ReplLscWiExtdVariantValuesTask;
use \Ls\Replication\Cron\ReplLscRetailImageLinkTask;
use \Ls\Replication\Cron\ReplLscInventoryLookupTableTask;
use \Ls\Replication\Cron\ReplLscWiItemBufferTask;
use \Ls\Replication\Cron\ReplLscItemuomupdviewTask;
use \Ls\Replication\Cron\ReplLscVariantregviewTask;
use \Ls\Replication\Cron\ReplLscWiPriceTask;
use \Ls\Replication\Cron\ReplLscUnitOfMeasureTask;
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
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'store', 'default'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'store', 'default'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'store', 'default'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'store', 'default'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'store', 'default'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'store', 'default'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE, 'website'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE, 'store', 'default'),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplLscWiItemBufferTask::class,
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
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'store', 'default'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'store', 'default'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'store', 'default'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'store', 'default'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'store', 'default'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'store', 'default'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE, 'website'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE, 'store', 'default'),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplLscWiItemBufferTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplLscVariantregviewTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplLscWiExtdVariantValuesTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplLscItemuomupdviewTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplLscUnitOfMeasureTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplLscBarcodesTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplLscRetailImageLinkTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplLscWiPriceTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplLscInventoryLookupTableTask::class,
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
