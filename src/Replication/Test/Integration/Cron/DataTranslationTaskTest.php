<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Cron\DataTranslationTask;
use \Ls\Replication\Cron\ProductCreateTask;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Store\Model\ScopeInterface;

class DataTranslationTaskTest extends AbstractTaskTest
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function actualExecute()
    {
        $storeId                             = $this->storeManager->getStore()->getId();
        $this->categoryCreateTaskCron->store = $this->storeManager->getStore();
        $this->updateAllRelevantItemRecords(
            1,
            [
                AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
                AbstractIntegrationTest::SAMPLE_SIMPLE_ITEM_ID,
                AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ITEM_ID,
                AbstractIntegrationTest::SAMPLE_STANDARD_VARIANT_ITEM_ID
            ]
        );

        $this->executeUntilReady(ProductCreateTask::class, [
            LSR::SC_SUCCESS_CRON_PRODUCT
        ]);

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_PRODUCT,
            ],
            $storeId
        );

        $this->executeUntilReady(DataTranslationTask::class, [
            LSR::SC_SUCCESS_CRON_DATA_TRANSLATION_TO_MAGENTO
        ]);

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_DATA_TRANSLATION_TO_MAGENTO,
            ],
            $storeId
        );

        $replDataTranslation = $this->getDataTranslationBasedOnParam(
            [
                'scope_id' => $storeId,
                'Key' => AbstractIntegrationTest::SAMPLE_HIERARCHY_NAV_ID . ';' . AbstractIntegrationTest::SAMPLE_HIERARCHY_NODE_NAV_ID_2,
                'TranslationId' => LSR::SC_TRANSLATION_ID_HIERARCHY_NODE
            ]
        );
        $category            = $this->getCategory(AbstractIntegrationTest::SAMPLE_HIERARCHY_NODE_NAV_ID_2, $storeId);

        if ($category && $replDataTranslation) {
            $category = $this->categoryRepository->get($category->getId());
            $this->assertTrue($category->getData('name') === $replDataTranslation->getText());
        }
    }

    public function addDummyData()
    {
        parent::addDummyData();

        $replDataTranslation = $this->replDataTranslationInterfaceFactory->create();
        $replDataTranslation->addData(
            [
                'IsDeleted' => 0,
                'Key' => AbstractIntegrationTest::SAMPLE_HIERARCHY_NAV_ID . ';' . AbstractIntegrationTest::SAMPLE_HIERARCHY_NODE_NAV_ID_2,
                'LanguageCode' => AbstractIntegrationTest::SAMPLE_LANGUAGE_CODE,
                'Text' => 'TranslatedBAGS',
                'TranslationId' => LSR::SC_TRANSLATION_ID_HIERARCHY_NODE,
                'scope' => ScopeInterface::SCOPE_STORES,
                'scope_id' => $this->storeManager->getStore()->getId()
            ]
        );

        $this->replDataTranslationRepository->save($replDataTranslation);
    }

    public function getDataTranslationBasedOnParam($params)
    {
        $filters = [];

        foreach ($params as $val => $param) {
            if ($param != null) {
                $filters[] = ['field' => $val, 'value' => $param, 'condition_type' => 'eq'];
            }
        }
        $criteria            = $this->replicationHelper->buildCriteriaForDirect($filters, -1, 1);
        $replDataTranslation = current($this->replDataTranslationRepository->getList($criteria)->getItems());

        return $replDataTranslation;
    }
}
