<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Cron\DataTranslationTask;
use \Ls\Replication\Cron\ProductCreateTask;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Catalog\Model\Product;
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
        $this->dataTranslationCron->store    = $this->storeManager->getStore();
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

        $this->assertHierarchyNode();
        $this->assertItem();
        $this->assertAttribute();
        $this->assertVariantAttribute();
        $this->assertStandardVariantAttribute();
    }

    public function assertHierarchyNode()
    {
        $storeId             = $this->storeManager->getStore()->getId();
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

    public function assertItem()
    {
        $storeId              = $this->storeManager->getStore()->getId();
        $replDataTranslation1 = $this->getDataTranslationBasedOnParam(
            [
                'scope_id' => $storeId,
                'Key' => AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
                'TranslationId' => LSR::SC_TRANSLATION_ID_ITEM_DESCRIPTION
            ]
        );

        $replDataTranslation2 = $this->getDataTranslationBasedOnParam(
            [
                'scope_id' => $storeId,
                'Key' => AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
                'TranslationId' => LSR::SC_TRANSLATION_ID_ITEM_HTML
            ]
        );

        $productData = $this->replicationHelper->getProductDataByIdentificationAttributes(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            '',
            '',
            $storeId
        );

        if ($productData && $replDataTranslation1) {
            $this->assertTrue($productData->getData('name') === $replDataTranslation1->getText());
            $this->assertTrue($productData->getData('description') === $replDataTranslation2->getText());
        }
    }

    public function assertAttribute()
    {
        $storeId = $this->storeManager->getStore()->getId();
        $this->eavConfig->clear();
        $attributeObject      = $this->eavConfig->getAttribute(
            Product::ENTITY,
            $this->replicationHelper->formatAttributeCode(AbstractIntegrationTest::SAMPLE_ATTRIBUTE_CODE)
        );
        $replDataTranslation1 = $this->getDataTranslationBasedOnParam(
            [
                'scope_id' => $storeId,
                'Key' => AbstractIntegrationTest::SAMPLE_ATTRIBUTE_CODE,
                'TranslationId' => LSR::SC_TRANSLATION_ID_ATTRIBUTE
            ]
        );
        if (!empty($attributeObject->getId()) && $replDataTranslation1) {
            $frontendLabels = $attributeObject->getFrontendLabels();

            foreach ($frontendLabels as &$frontendLabel) {
                if ($frontendLabel->getStoreId() == $storeId) {
                    $this->assertTrue($frontendLabel->getLabel() == $replDataTranslation1->getText());
                }
            }
        }

        $replDataTranslation2 = $this->getDataTranslationBasedOnParam(
            [
                'scope_id' => $storeId,
                'Key' => AbstractIntegrationTest::SAMPLE_ATTRIBUTE_CODE . ';10000',
                'TranslationId' => LSR::SC_TRANSLATION_ID_ATTRIBUTE_OPTION_VALUE
            ]
        );

        $defaultScopedAttributeObject = $this->replicationHelper->getProductAttributeGivenCodeAndScope(
            $this->replicationHelper->formatAttributeCode(AbstractIntegrationTest::SAMPLE_ATTRIBUTE_CODE)
        );

        if (!empty($defaultScopedAttributeObject->getId()) && !empty($replDataTranslation2)) {
            $keyArray = explode(';', $replDataTranslation2->getKey());

            if (count($keyArray) == 2 && !empty($keyArray[0]) && !empty($keyArray[1])) {
                $originalOptionalValue = $this->dataTranslationCron->getOriginalOptionLabel($keyArray, $storeId);
                $this->searchAndCheckAttributeOptionValue(
                    $replDataTranslation2,
                    $defaultScopedAttributeObject,
                    $originalOptionalValue,
                    $storeId
                );
            }
        }
    }

    public function assertVariantAttribute()
    {
        $storeId = $this->storeManager->getStore()->getId();
        $this->eavConfig->clear();
        $attributeObject      = $this->eavConfig->getAttribute(
            Product::ENTITY,
            $this->replicationHelper->formatAttributeCode(AbstractIntegrationTest::SAMPLE_VARIANT_ATTRIBUTE)
        );
        $replDataTranslation1 = $this->getDataTranslationBasedOnParam(
            [
                'scope_id' => $storeId,
                'Key' => 'WOMEN;40020;' . AbstractIntegrationTest::SAMPLE_VARIANT_ATTRIBUTE . ';',
                'TranslationId' => LSR::SC_TRANSLATION_ID_EXTENDED_VARIANT
            ]
        );
        if (!empty($attributeObject->getId()) && $replDataTranslation1) {
            $frontendLabels = $attributeObject->getFrontendLabels();

            foreach ($frontendLabels as &$frontendLabel) {
                if ($frontendLabel->getStoreId() == $storeId) {
                    $this->assertTrue($frontendLabel->getLabel() == $replDataTranslation1->getText());
                }
            }
        }

        $replDataTranslation2 = $this->getDataTranslationBasedOnParam(
            [
                'scope_id' => $storeId,
                'Key' => 'WOMEN;40020;' . AbstractIntegrationTest::SAMPLE_VARIANT_ATTRIBUTE . ';RED;',
                'TranslationId' => LSR::SC_TRANSLATION_ID_EXTENDED_VARIANT_VALUE
            ]
        );

        $defaultScopedAttributeObject = $this->replicationHelper->getProductAttributeGivenCodeAndScope(
            $this->replicationHelper->formatAttributeCode(AbstractIntegrationTest::SAMPLE_VARIANT_ATTRIBUTE)
        );

        if (!empty($defaultScopedAttributeObject->getId()) && !empty($replDataTranslation2)) {
            $keyArray = explode(';', $replDataTranslation2->getKey());

            if (!empty($keyArray[2]) && !empty($keyArray[3])) {
                $this->searchAndCheckAttributeOptionValue(
                    $replDataTranslation2,
                    $defaultScopedAttributeObject,
                    $keyArray[3],
                    $storeId
                );
            }
        }
    }

    public function assertStandardVariantAttribute()
    {
        $storeId = $this->storeManager->getStore()->getId();
        $this->eavConfig->clear();

        $replDataTranslation2 = $this->getDataTranslationBasedOnParam(
            [
                'scope_id' => $storeId,
                'Key' => AbstractIntegrationTest::SAMPLE_STANDARD_VARIANT_ITEM_ID .
                    ';' . AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ID,
                'TranslationId' => LSR::SC_TRANSLATION_ID_STANDARD_VARIANT_ATTRIBUTE_OPTION_VALUE
            ]
        );

        $defaultScopedAttributeObject = $this->replicationHelper->getProductAttributeGivenCodeAndScope(
            $this->replicationHelper->formatAttributeCode(LSR::LS_STANDARD_VARIANT_ATTRIBUTE_CODE)
        );

        if (!empty($defaultScopedAttributeObject->getId()) && !empty($replDataTranslation2)) {
            $keyArray = explode(';', $replDataTranslation2->getKey());

            if (count($keyArray) == 2 && !empty($keyArray[0]) && !empty($keyArray[1])) {
                $originalOptionValue = $this->dataTranslationCron->getStandardVariantOriginalOptionLabel(
                    $keyArray,
                    $storeId
                );

                $this->searchAndCheckAttributeOptionValue(
                    $replDataTranslation2,
                    $defaultScopedAttributeObject,
                    $originalOptionValue,
                    $storeId
                );
            }
        }
    }

    public function searchAndCheckAttributeOptionValue(
        $replDataTranslation2,
        $defaultScopedAttributeObject,
        $originalOptionValue,
        $storeId
    ) {
        $optionId = $defaultScopedAttributeObject->getSource()->getOptionId($originalOptionValue);

        if (!empty($optionId)) {
            $storeLabels = [];
            foreach ($defaultScopedAttributeObject->getOptions() as $option) {
                if ($option->getValue() == $optionId) {
                    $storeLabels = $this->dataTranslationCron->getAllStoresLabelGivenAttributeAndOption(
                        $defaultScopedAttributeObject->getId(),
                        $optionId
                    );

                    break;
                }
            }

            foreach ($storeLabels as $storeLabel) {
                if ($storeLabel->getStoreId() == $storeId) {
                    $this->assertTrue($storeLabel->getLabel() == $replDataTranslation2->getText());
                }
            }
        }
    }

    public function addDummyData()
    {
        parent::addDummyData();
        $this->addDummyTranslationRecord(
            [
                'IsDeleted' => 0,
                'Key' => AbstractIntegrationTest::SAMPLE_HIERARCHY_NAV_ID . ';' . AbstractIntegrationTest::SAMPLE_HIERARCHY_NODE_NAV_ID_2,
                'LanguageCode' => AbstractIntegrationTest::SAMPLE_LANGUAGE_CODE,
                'Text' => 'Translated' . AbstractIntegrationTest::SAMPLE_HIERARCHY_NODE_NAV_ID_2,
                'TranslationId' => LSR::SC_TRANSLATION_ID_HIERARCHY_NODE,
                'scope' => ScopeInterface::SCOPE_STORES,
                'scope_id' => $this->storeManager->getStore()->getId(),
                'is_updated' => 0,
                'processed' => 0
            ]
        );

        $this->addDummyTranslationRecord(
            [
                'IsDeleted' => 0,
                'Key' => AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
                'LanguageCode' => AbstractIntegrationTest::SAMPLE_LANGUAGE_CODE,
                'Text' => 'Translated Skirt Linda professional wear demo',
                'TranslationId' => LSR::SC_TRANSLATION_ID_ITEM_DESCRIPTION,
                'scope' => ScopeInterface::SCOPE_STORES,
                'scope_id' => $this->storeManager->getStore()->getId(),
                'is_updated' => 0,
                'processed' => 0
            ]
        );
        $html = <<<EOF
<html>
<body>
<h1 style="font-family:Segoe UI;color:grey">
Translated Skirt Linda professional wear demo
</h1>
</body>
</html>
EOF;

        $this->addDummyTranslationRecord(
            [
                'IsDeleted' => 0,
                'Key' => AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
                'LanguageCode' => AbstractIntegrationTest::SAMPLE_LANGUAGE_CODE,
                'Text' => $html,
                'TranslationId' => LSR::SC_TRANSLATION_ID_ITEM_HTML,
                'scope' => ScopeInterface::SCOPE_STORES,
                'scope_id' => $this->storeManager->getStore()->getId(),
                'is_updated' => 0,
                'processed' => 0
            ]
        );

        $this->addDummyTranslationRecord(
            [
                'IsDeleted' => 0,
                'Key' => AbstractIntegrationTest::SAMPLE_ATTRIBUTE_CODE,
                'LanguageCode' => AbstractIntegrationTest::SAMPLE_LANGUAGE_CODE,
                'Text' => 'Translated Fabric type',
                'TranslationId' => LSR::SC_TRANSLATION_ID_ATTRIBUTE,
                'scope' => ScopeInterface::SCOPE_STORES,
                'scope_id' => $this->storeManager->getStore()->getId(),
                'is_updated' => 0,
                'processed' => 0
            ]
        );

        $this->addDummyTranslationRecord(
            [
                'IsDeleted' => 0,
                'Key' => AbstractIntegrationTest::SAMPLE_ATTRIBUTE_CODE . ';10000',
                'LanguageCode' => AbstractIntegrationTest::SAMPLE_LANGUAGE_CODE,
                'Text' => 'Translated Wool',
                'TranslationId' => LSR::SC_TRANSLATION_ID_ATTRIBUTE_OPTION_VALUE,
                'scope' => ScopeInterface::SCOPE_STORES,
                'scope_id' => $this->storeManager->getStore()->getId(),
                'is_updated' => 0,
                'processed' => 0
            ]
        );

        $this->addDummyTranslationRecord(
            [
                'IsDeleted' => 0,
                'Key' => 'WOMEN;40020;' . AbstractIntegrationTest::SAMPLE_VARIANT_ATTRIBUTE . ';',
                'LanguageCode' => AbstractIntegrationTest::SAMPLE_LANGUAGE_CODE,
                'Text' => 'Translated COLOUR',
                'TranslationId' => LSR::SC_TRANSLATION_ID_EXTENDED_VARIANT,
                'scope' => ScopeInterface::SCOPE_STORES,
                'scope_id' => $this->storeManager->getStore()->getId(),
                'is_updated' => 0,
                'processed' => 0
            ]
        );

        $this->addDummyTranslationRecord(
            [
                'IsDeleted' => 0,
                'Key' => 'WOMEN;40020;' . AbstractIntegrationTest::SAMPLE_VARIANT_ATTRIBUTE . ';RED;',
                'LanguageCode' => AbstractIntegrationTest::SAMPLE_LANGUAGE_CODE,
                'Text' => 'Translated RED',
                'TranslationId' => LSR::SC_TRANSLATION_ID_EXTENDED_VARIANT_VALUE,
                'scope' => ScopeInterface::SCOPE_STORES,
                'scope_id' => $this->storeManager->getStore()->getId(),
                'is_updated' => 0,
                'processed' => 0
            ]
        );

        $this->addDummyTranslationRecord(
            [
                'IsDeleted' => 0,
                'Key' => AbstractIntegrationTest::SAMPLE_STANDARD_VARIANT_ITEM_ID .
                    ';' . AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ID,
                'LanguageCode' => AbstractIntegrationTest::SAMPLE_LANGUAGE_CODE,
                'Text' => 'Translated SMALL',
                'TranslationId' => LSR::SC_TRANSLATION_ID_STANDARD_VARIANT_ATTRIBUTE_OPTION_VALUE,
                'scope' => ScopeInterface::SCOPE_STORES,
                'scope_id' => $this->storeManager->getStore()->getId(),
                'is_updated' => 0,
                'processed' => 0
            ]
        );
    }

    public function addDummyTranslationRecord($params)
    {
        $replDataTranslation = $this->getDataTranslationBasedOnParam([
            'Key' => $params['Key'],
            'LanguageCode' => $params['LanguageCode'],
            'TranslationId' => $params['TranslationId']
        ]);

        if (!empty($replDataTranslation)) {
            $replDataTranslation->addData($params);
        } else {
            $replDataTranslation = $this->replDataTranslationInterfaceFactory->create();
            $replDataTranslation->addData($params);
        }

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
        return current($this->replDataTranslationRepository->getList($criteria)->getItems());
    }
}
