<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Controller\Adminhtml\Reset;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Api\ReplItemRepositoryInterface as ReplItemRepository;
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
use \Ls\Replication\Model\ReplItem;
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
class MassResetTest extends AbstractBackendController
{
    public const ITEM_GRID_URI = 'ls_repl/grids/item';
    public const ITEM_GRID_NAMESPACE = 'ls_repl_grids_item_listing';
    public $objectManager;
    public $messageManager;
    public $urlBuilder;
    public $itemRepository;

    public $replicationHelper;

    public $storeManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resource   = 'Magento_Backend::admin';
        $this->uri        = 'backend/ls_repl/reset/massreset';
        $this->httpMethod = HttpRequest::METHOD_POST;
        parent::setUp();

        $this->objectManager     = Bootstrap::getObjectManager();
        $this->messageManager    = $this->objectManager->get(Manager::class);
        $this->urlBuilder        = $this->objectManager->get(UrlInterface::class);
        $this->itemRepository    = $this->objectManager->get(ReplItemRepository::class);
        $this->replicationHelper = $this->objectManager->get(ReplicationHelper::class);
        $this->storeManager      = $this->objectManager->get(StoreManagerInterface::class);
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
    public function testExecute(): void
    {
        $scopeId  = $this->storeManager->getWebsite()->getId();
        $replItem = $this->getItem(AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID, $scopeId);
        if ($replItem) {
            $productIds = [$replItem->getId()];
            $this->getRequest()->setMethod($this->httpMethod);
            $this->getRequest()->setParams(
                [
                    'selected'  => $productIds,
                    'filters'   => ['nav_id' => AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID],
                    'namespace' => self::ITEM_GRID_NAMESPACE
                ]
            );

            $this->setReferalUrl();
            $this->dispatch($this->uri);

            $this->assertRedirectAndMessages(1);
        }
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
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE)
    ]
    public function testExecuteWithoutFilter(): void
    {
        $this->getRequest()->setMethod($this->httpMethod);
        $this->getRequest()->setParams(
            [
                'selected'  => null,
                'filters'   => ['nav_id' => AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID],
                'namespace' => self::ITEM_GRID_NAMESPACE
            ]
        );

        $this->setReferalUrl();
        $this->dispatch($this->uri);

        $this->assertRedirectAndMessages();
    }

    public function assertRedirectAndMessages($msgCount = 0)
    {
        $this->assertRedirect(
            $this->stringContains(self::ITEM_GRID_URI)
        );

        $messages = $this->messageManager->getMessages(false)->getItems();
        if ($msgCount) {
            $this->assertTrue(count($messages) > 0);
        } else {
            $this->assertTrue(count($messages) == 0);
        }
    }

    public function setReferalUrl()
    {
        $redirectUrl            = $this->urlBuilder->getUrl(self::ITEM_GRID_URI);
        $server                 = $this->getRequest()->getServer();
        $server['HTTP_REFERER'] = $redirectUrl;
        $this->getRequest()->setServer($server);
    }

    /**
     * @param $itemId
     * @param $scopeId
     * @return ReplItem|null
     */
    public function getItem($itemId, $scopeId)
    {
        $filters = [
            ['field' => 'nav_id', 'value' => $itemId, 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $scopeId, 'condition_type' => 'eq']
        ];

        $searchCriteria = $this->replicationHelper->buildCriteriaForDirect($filters, 1);
        /** @var ReplItem $item */
        $item = current($this->itemRepository->getList($searchCriteria)->getItems());

        return $item ?? null;
    }
}
