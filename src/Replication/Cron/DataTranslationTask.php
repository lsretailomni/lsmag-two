<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Api\ReplAttributeOptionValueRepositoryInterface;
use \Ls\Replication\Model\ReplAttributeOptionValue;
use \Ls\Replication\Model\ReplAttributeOptionValueSearchResults;
use \Ls\Replication\Model\ReplDataTranslation;
use \Ls\Replication\Api\ReplDataTranslationRepositoryInterface;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use \Ls\Replication\Model\ReplDataTranslationSearchResults;
use \Ls\Replication\Model\ResourceModel\ReplDataTranslation\CollectionFactory as ReplDataTranslationCollectionFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeFrontendLabelInterfaceFactory;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class DataTranslationTask
 * Data Translation Job for language localization
 */
class DataTranslationTask
{
    /**
     * @var ReplicationHelper
     */
    public $replicationHelper;

    /**
     * @var ReplDataTranslationRepositoryInterface
     */
    public $dataTranslationRepository;

    /**
     * @var CategoryCollectionFactory
     */
    public $categoryCollectionFactory;

    /**
     * @var CategoryRepositoryInterface
     */
    public $categoryRepository;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var StoreInterface $store
     */
    public $store;

    /**
     * @var bool
     */
    public $cronStatus = false;

    /**
     * @var Attribute
     */
    public $eavAttribute;

    /**
     * @var ReplDataTranslationCollectionFactory
     */
    public $replDataTranslationCollectionFactory;

    /**
     * @var Product
     */
    public $productResourceModel;

    /**
     * @var ProductRepositoryInterface
     */
    public $productRepository;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    public $productAttributeRepository;

    /**
     * @var AttributeFactory
     */
    public $eavAttributeFactory;

    /**
     * @var AttributeOptionManagementInterface
     */
    public $attributeOptionManagement;

    /**
     * @var AttributeOptionLabelInterfaceFactory
     */
    public $optionLabelFactory;

    /**
     * @var ReplAttributeOptionValueRepositoryInterface
     */
    public $replAttributeOptionValueRepositoryInterface;

    /**
     * @var AttributeFrontendLabelInterfaceFactory
     */
    public $frontendLabelInterfaceFactory;

    /**
     * @var CollectionFactory
     */
    public $attrOptionCollectionFactory;

    /**
     * @param ReplicationHelper $replicationHelper
     * @param ReplDataTranslationRepositoryInterface $dataTranslationRepository
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param LSR $LSR
     * @param Logger $logger
     * @param ReplDataTranslationCollectionFactory $replDataTranslationCollectionFactory
     * @param Attribute $eavAttribute
     * @param Product $productResourceModel
     * @param ProductRepositoryInterface $productRepository
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param AttributeFactory $eavAttributeFactory
     * @param AttributeOptionManagementInterface $attributeOptionManagement
     * @param AttributeOptionLabelInterfaceFactory $optionLabelFactory
     * @param ReplAttributeOptionValueRepositoryInterface $replAttributeOptionValueRepositoryInterface
     * @param AttributeFrontendLabelInterfaceFactory $frontendLabelInterfaceFactory
     * @param CollectionFactory $_attrOptionCollectionFactory
     */
    public function __construct(
        ReplicationHelper $replicationHelper,
        ReplDataTranslationRepositoryInterface $dataTranslationRepository,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryRepositoryInterface $categoryRepository,
        LSR $LSR,
        Logger $logger,
        ReplDataTranslationCollectionFactory $replDataTranslationCollectionFactory,
        Attribute $eavAttribute,
        Product $productResourceModel,
        ProductRepositoryInterface $productRepository,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        AttributeFactory $eavAttributeFactory,
        AttributeOptionManagementInterface $attributeOptionManagement,
        AttributeOptionLabelInterfaceFactory $optionLabelFactory,
        ReplAttributeOptionValueRepositoryInterface $replAttributeOptionValueRepositoryInterface,
        AttributeFrontendLabelInterfaceFactory $frontendLabelInterfaceFactory,
        CollectionFactory $_attrOptionCollectionFactory
    ) {
        $this->replicationHelper                           = $replicationHelper;
        $this->dataTranslationRepository                   = $dataTranslationRepository;
        $this->categoryCollectionFactory                   = $categoryCollectionFactory;
        $this->categoryRepository                          = $categoryRepository;
        $this->lsr                                         = $LSR;
        $this->logger                                      = $logger;
        $this->replDataTranslationCollectionFactory        = $replDataTranslationCollectionFactory;
        $this->eavAttribute                                = $eavAttribute;
        $this->productResourceModel                        = $productResourceModel;
        $this->productRepository                           = $productRepository;
        $this->productAttributeRepository                  = $productAttributeRepository;
        $this->eavAttributeFactory                         = $eavAttributeFactory;
        $this->attributeOptionManagement                   = $attributeOptionManagement;
        $this->optionLabelFactory                          = $optionLabelFactory;
        $this->replAttributeOptionValueRepositoryInterface = $replAttributeOptionValueRepositoryInterface;
        $this->frontendLabelInterfaceFactory               = $frontendLabelInterfaceFactory;
        $this->attrOptionCollectionFactory                 = $_attrOptionCollectionFactory;
    }

    /**
     * Entry point for cron running automatically
     *
     * @param mixed $storeData
     * @return void
     * @throws NoSuchEntityException
     */
    public function execute($storeData = null)
    {
        if (!empty($storeData) && $storeData instanceof StoreInterface) {
            $stores = [$storeData];
        } else {
            $stores = $this->lsr->getAllStores();
        }
        if (!empty($stores)) {
            foreach ($stores as $store) {
                $this->lsr->setStoreId($store->getId());
                $this->store = $store;
                if ($this->lsr->isLSR($this->store->getId())) {
                    $cronAttributeVariantCheck = $this->lsr->getConfigValueFromDb(
                        LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT,
                        ScopeInterface::SCOPE_STORES,
                        $store->getId()
                    );
                    $langCode                  = $this->lsr->getStoreConfig(
                        LSR::SC_STORE_DATA_TRANSLATION_LANG_CODE,
                        $store->getId()
                    );
                    $this->logger->debug('DataTranslationTask Started for Store ' . $store->getName());
                    if ($langCode != "Default") {
                        $configurableAttributesValuesStatus    = $configurableAttributesStatus = false;
                        $hierarchyNodesStatus                  = $this->updateHierarchyNode(
                            $store->getId(),
                            $langCode,
                            $store->getWebsiteId()
                        );
                        $itemsStatus                           = $this->updateItem($store->getId(), $langCode);
                        $attributesStatus                      = $this->updateAttributes($store->getId(), $langCode);
                        $nonConfigurableAttributesValuesStatus = $this->updateAttributeOptionValue(
                            $store->getId(),
                            $langCode
                        );
                        $textBasedAttributesValuesStatus       = $this->updateProductAttributesValues(
                            $store->getId(),
                            $langCode
                        );

                        if ($cronAttributeVariantCheck) {
                            $configurableAttributesStatus       = $this->updateExtendedVariantAttributes(
                                $store->getId(),
                                $langCode
                            );
                            $configurableAttributesValuesStatus = $this->updateExtendedVariantAttributesValues(
                                $store->getId(),
                                $langCode
                            );
                        }

                        $this->cronStatus = $hierarchyNodesStatus &&
                            $itemsStatus &&
                            $attributesStatus &&
                            $nonConfigurableAttributesValuesStatus &&
                            $textBasedAttributesValuesStatus &&
                            $configurableAttributesStatus &&
                            $configurableAttributesValuesStatus;

                    } else {
                        $this->cronStatus = true;
                    }
                    $this->replicationHelper->updateConfigValue(
                        $this->replicationHelper->getDateTime(),
                        LSR::SC_CRON_DATA_TRANSLATION_TO_MAGENTO_CONFIG_PATH_LAST_EXECUTE,
                        $store->getId()
                    );
                    $this->replicationHelper->updateCronStatus(
                        $this->cronStatus,
                        LSR::SC_SUCCESS_CRON_DATA_TRANSLATION_TO_MAGENTO,
                        $store->getId()
                    );
                    $this->logger->debug('DataTranslationTask Completed for Store ' . $store->getName());
                }

                $this->lsr->setStoreId(null);
            }
        }
    }

    /**
     * Cater translation of multiselect non-configurable attributes
     *
     * @param int $storeId
     * @param string $langCode
     * @return bool
     */
    public function updateAttributeOptionValue($storeId, $langCode)
    {
        $filters = $this->getFiltersGivenValues(
            $storeId,
            $langCode,
            LSR::SC_TRANSLATION_ID_ATTRIBUTE_OPTION_VALUE,
        );
        $criteria = $this->replicationHelper->buildCriteriaForArrayWithAlias($filters, -1);
        /** @var ReplDataTranslationSearchResults $dataTranslationItems */
        $dataTranslationItems = $this->dataTranslationRepository->getList($criteria)->getItems();
        /** @var ReplDataTranslation $dataTranslation */
        foreach ($dataTranslationItems as $dataTranslation) {
            try {
                $keyArray = explode(';', $dataTranslation->getKey());

                if (count($keyArray) == 2 && !empty($keyArray[0]) && !empty($keyArray[1])) {
                    $originalOptionValue = $this->getOriginalOptionLabel($keyArray, $storeId);
                    $this->searchAndSetAttributeValueLabel($keyArray[0], $originalOptionValue, $dataTranslation);
                } else {
                    $this->logger->debug('Translation Key is not valid for ' . $dataTranslation->getKey());
                    $dataTranslation->setData('is_failed', 1);
                }
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
                $this->logger->debug('Error while saving data translation ' . $dataTranslation->getKey());
                $dataTranslation->setData('is_failed', 1);
            }
            // @codingStandardsIgnoreLine
            $this->dataTranslationRepository->save($dataTranslation);
        }

        return count($dataTranslationItems) == 0;
    }

    /**
     * Cater translation of configurable attribute values
     *
     * @param string $storeId
     * @param string $langCode
     */
    public function updateExtendedVariantAttributesValues($storeId, $langCode)
    {
        $filters = $this->getFiltersGivenValues(
            $storeId,
            $langCode,
            LSR::SC_TRANSLATION_ID_EXTENDED_VARIANT_VALUE,
        );
        $criteria = $this->replicationHelper->buildCriteriaForArrayWithAlias($filters, -1);
        /** @var ReplDataTranslationSearchResults $dataTranslationItems */
        $dataTranslationItems = $this->dataTranslationRepository->getList($criteria)->getItems();
        /** @var ReplDataTranslation $dataTranslation */
        foreach ($dataTranslationItems as $dataTranslation) {
            try {
                $keyArray = explode(';', $dataTranslation->getKey());

                if (!empty($keyArray[2]) && !empty($keyArray[3])) {
                    $this->searchAndSetAttributeValueLabel($keyArray[2], $keyArray[3], $dataTranslation);
                } else {
                    $this->logger->debug('Translation Key is not valid for ' . $dataTranslation->getKey());
                    $dataTranslation->setData('is_failed', 1);
                }
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
                $this->logger->debug('Error while saving data translation ' . $dataTranslation->getKey());
                $dataTranslation->setData('is_failed', 1);
            }
            // @codingStandardsIgnoreLine
            $this->dataTranslationRepository->save($dataTranslation);
        }

        return count($dataTranslationItems) == 0;
    }

    /**
     * Cater translation of configurable attribute values
     *
     * @param string $storeId
     * @param string $langCode
     */
    public function updateExtendedVariantAttributes($storeId, $langCode)
    {
        $filters = $this->getFiltersGivenValues(
            $storeId,
            $langCode,
            LSR::SC_TRANSLATION_ID_EXTENDED_VARIANT,
        );
        $criteria = $this->replicationHelper->buildCriteriaForArrayWithAlias($filters, -1);
        /** @var ReplDataTranslationSearchResults $dataTranslationItems */
        $dataTranslationItems = $this->dataTranslationRepository->getList($criteria)->getItems();
        /** @var ReplDataTranslation $dataTranslation */
        foreach ($dataTranslationItems as $dataTranslation) {
            try {
                $keyArray = explode(';', $dataTranslation->getKey());

                if (!empty($keyArray[2])) {
                    $this->updateAttributeLabel($dataTranslation, $keyArray[2], $storeId);
                } else {
                    $this->logger->debug('Translation Key is not valid for ' . $dataTranslation->getKey());
                    $dataTranslation->setData('is_failed', 1);
                }
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
                $this->logger->debug('Error while saving data translation ' . $dataTranslation->getKey());
                $dataTranslation->setData('is_failed', 1);
            }
            // @codingStandardsIgnoreLine
            $this->dataTranslationRepository->save($dataTranslation);
        }

        return count($dataTranslationItems) == 0;
    }

    /**
     * Cater translations of text based attribute values in products
     *
     * @param int $storeId
     * @param string $langCode
     * @return bool
     */
    public function updateProductAttributesValues($storeId, $langCode)
    {
        $filters = $this->getFiltersGivenValues(
            $storeId,
            $langCode,
            LSR::SC_TRANSLATION_ID_PRODUCT_ATTRIBUTE_VALUE
        );
        $criteria   = $this->replicationHelper->buildCriteriaForArrayWithAlias($filters, -1);
        $collection = $this->replDataTranslationCollectionFactory->create();
        $this->replicationHelper->setCollectionPropertiesPlusJoinsForProductAttributeValuesDataTranslation(
            $collection,
            $criteria
        );
        /** @var ReplDataTranslation $dataTranslation */
        foreach ($collection as $dataTranslation) {
            try {
                $keys          = explode(';', $dataTranslation->getKey());
                $sku           = $keys[0] == 'Item' ? $keys[1] : $keys[1] . '-' . $keys[2];
                $productData   = $this->productRepository->get($sku, true, $storeId);
                $formattedCode = $this->replicationHelper->formatAttributeCode($keys[4]);

                if (isset($productData)) {
                    $productData->addAttributeUpdate($formattedCode, $dataTranslation->getText(), $storeId);
                }
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
                $this->logger->debug('Error while saving data translation ' . $dataTranslation->getKey());
                $dataTranslation->setData('is_failed', 1);
            }
            $dataTranslation->setData('processed_at', $this->replicationHelper->getDateTime());
            $dataTranslation->setData('processed', 1);
            $dataTranslation->setData('is_updated', 0);
            $this->dataTranslationRepository->save($dataTranslation);
        }

        return $collection->getSize() == 0;
    }

    /**
     * Cater translation for non-configurable attribute labels
     *
     * @param int $storeId
     * @param string $langCode
     * @return bool
     */
    public function updateAttributes($storeId, $langCode)
    {
        $filters = $this->getFiltersGivenValues(
            $storeId,
            $langCode,
            LSR::SC_TRANSLATION_ID_ATTRIBUTE
        );
        $criteria = $this->replicationHelper->buildCriteriaForArrayWithAlias($filters, -1);
        /** @var ReplDataTranslationSearchResults $dataTranslationItems */
        $dataTranslationItems = $this->dataTranslationRepository->getList($criteria)->getItems();
        /** @var ReplDataTranslation $dataTranslation */
        foreach ($dataTranslationItems as $dataTranslation) {
            try {
                $this->updateAttributeLabel($dataTranslation, $dataTranslation->getKey(), $storeId);
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
                $this->logger->debug('Error while saving data translation ' . $dataTranslation->getKey());
                $dataTranslation->setData('is_failed', 1);
            }
            // @codingStandardsIgnoreLine
            $this->dataTranslationRepository->save($dataTranslation);
        }

        return count($dataTranslationItems) == 0;
    }

    /**
     * Cater translation of products name and description
     *
     * @param int $storeId
     * @param string $langCode
     * @return bool
     */
    public function updateItem($storeId, $langCode)
    {
        $filters = $this->getFiltersGivenValues(
            $storeId,
            $langCode,
            LSR::SC_TRANSLATION_ID_ITEM_HTML . ',' . LSR::SC_TRANSLATION_ID_ITEM_DESCRIPTION
        );
        $criteria   = $this->replicationHelper->buildCriteriaForArrayWithAlias($filters, -1);
        $collection = $this->replDataTranslationCollectionFactory->create();
        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'key',
            'catalog_product_entity',
            'sku',
            true
        );
        /** @var ReplDataTranslation $dataTranslation */
        foreach ($collection as $dataTranslation) {
            try {
                $sku         = $dataTranslation->getKey();
                $productData = $this->productRepository->get($sku, true, $storeId);

                if (isset($productData)) {
                    if ($dataTranslation->getTranslationId() == LSR::SC_TRANSLATION_ID_ITEM_HTML) {
                        $productData->setDescription($dataTranslation->getText());
                        $this->productResourceModel->saveAttribute($productData, 'description');
                    } else {
                        $productData->setMetaTitle($dataTranslation->getText());
                        $productData->setName($dataTranslation->getText());
                        // @codingStandardsIgnoreLine
                        $this->productResourceModel->saveAttribute($productData, 'name');
                    }
                }
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
                $this->logger->debug('Error while saving data translation ' . $dataTranslation->getKey());
                $dataTranslation->setData('is_failed', 1);
            }
            $dataTranslation->setData('processed_at', $this->replicationHelper->getDateTime());
            $dataTranslation->setData('processed', 1);
            $dataTranslation->setData('is_updated', 0);
            // @codingStandardsIgnoreLine
            $this->dataTranslationRepository->save($dataTranslation);
        }

        return $collection->getSize() == 0;
    }

    /**
     * Cater translation of category names
     *
     * @param int $storeId
     * @param string $langCode
     * @param int $websiteId
     * @return bool
     */
    public function updateHierarchyNode($storeId, $langCode, $websiteId = null)
    {
        $attribute_id = $this->eavAttribute->getIdByCode(Category::ENTITY, 'nav_id');
        $filters      = [
            ['field' => 'main_table.scope_id', 'value' => $storeId, 'condition_type' => 'eq'],
            ['field' => 'main_table.LanguageCode', 'value' => $langCode, 'condition_type' => 'eq'],
            [
                'field'          => 'main_table.TranslationId',
                'value'          => LSR::SC_TRANSLATION_ID_HIERARCHY_NODE,
                'condition_type' => 'eq'
            ],
            ['field' => 'main_table.key', 'value' => true, 'condition_type' => 'notnull'],
            ['field' => 'second.attribute_id', 'value' => $attribute_id, 'condition_type' => 'eq'],
            ['field' => 'second.store_id', 'value' => $storeId, 'condition_type' => 'eq']
        ];
        $criteria     = $this->replicationHelper->buildCriteriaForArrayWithAlias($filters, -1);
        $collection   = $this->replDataTranslationCollectionFactory->create();
        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'key',
            'catalog_category_entity_varchar',
            'value',
            false,
            false,
            true,
            $websiteId
        );
        /** @var ReplDataTranslation $dataTranslation */
        foreach ($collection as $dataTranslation) {
            try {
                $categoryExistData = $this->isCategoryExist($dataTranslation->getValue(), true);

                if ($categoryExistData) {
                    $categoryExistData->setData('name', $dataTranslation->getText());
                    // @codingStandardsIgnoreLine
                    $this->categoryRepository->save($categoryExistData);
                }
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
                $this->logger->debug('Error while saving data translation ' . $dataTranslation->getKey());
                $dataTranslation->setData('is_failed', 1);
            }
            $dataTranslation->setData('processed_at', $this->replicationHelper->getDateTime());
            $dataTranslation->setData('processed', 1);
            $dataTranslation->setData('is_updated', 0);
            // @codingStandardsIgnoreLine
            $this->dataTranslationRepository->save($dataTranslation);
        }

        return $collection->getSize() == 0;
    }

    /**
     * Execute manually
     *
     * @param mixed $storeData
     * @return int[]
     * @throws NoSuchEntityException
     */
    public function executeManually($storeData = null)
    {
        $this->execute($storeData);
        return [0];
    }

    /**
     * Check if the category already exist or not
     *
     * @param string $navId
     * @param bool $store
     * @return false|DataObject
     * @throws LocalizedException
     */
    public function isCategoryExist($navId, $store = false)
    {
        $collection = $this->categoryCollectionFactory->create()->addAttributeToFilter('nav_id', $navId);

        if ($store) {
            $collection->addPathsFilter('1/' . $this->store->getRootCategoryId() . '/');
        }
        $collection->setPageSize(1);

        if ($collection->getSize()) {
            // @codingStandardsIgnoreStart
            return $collection->getFirstItem();
            // @codingStandardsIgnoreEnd
        }
        return false;
    }

    /**
     * Get original option label
     *
     * @param array $keyArray
     * @param string $storeId
     * @return false|string
     */
    public function getOriginalOptionLabel($keyArray, $storeId)
    {
        $filters  = [
            ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq'],
            ['field' => 'Code', 'value' => $keyArray[0], 'condition_type' => 'eq'],
            ['field' => 'Sequence', 'value' => $keyArray[1], 'condition_type' => 'eq']
        ];
        $criteria = $this->replicationHelper->buildCriteriaForDirect($filters, 1);
        /** @var ReplAttributeOptionValueSearchResults $replAttributeOptionValues */
        $replAttributeOptionValues = $this->replAttributeOptionValueRepositoryInterface->getList($criteria);
        /** @var ReplAttributeOptionValue $item */
        $item = current($replAttributeOptionValues->getItems());

        if (isset($item)) {
            return $item->getValue();
        } else {
            return false;
        }
    }

    /**
     * Search and set attribute value label
     *
     * @param string $attributeCode
     * @param string $originalOptionalValue
     * @param ReplDataTranslation $dataTranslation
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InputException
     * @throws StateException
     */
    public function searchAndSetAttributeValueLabel($attributeCode, $originalOptionalValue, &$dataTranslation)
    {
        $formattedCode                = $this->replicationHelper->formatAttributeCode($attributeCode);
        $defaultScopedAttributeObject = $this->replicationHelper->getProductAttributeGivenCodeAndScope($formattedCode);

        if (!empty($defaultScopedAttributeObject->getId())) {
            $optionId = $defaultScopedAttributeObject->getSource()->getOptionId($originalOptionalValue);

            if (!empty($optionId)) {
                foreach ($defaultScopedAttributeObject->getOptions() as $option) {
                    if ($option->getValue() == $optionId) {
                        $storeLabels = $this->getAllStoresLabelGivenAttributeAndOption(
                            $defaultScopedAttributeObject->getId(),
                            $optionId
                        );

                        foreach ($storeLabels as $storeLabel) {
                            if ($storeLabel->getStoreId() == $this->store->getId()) {
                                $storeLabel->setLabel($dataTranslation->getText());
                            }
                        }
                        $option->setLabel($originalOptionalValue);
                        $option->setStoreLabels($storeLabels);
                        $this->attributeOptionManagement->update(
                            \Magento\Catalog\Model\Product::ENTITY,
                            $formattedCode,
                            $optionId,
                            $option
                        );
                        $dataTranslation->addData(
                            [
                                'is_updated' => 0,
                                'processed_at' => $this->replicationHelper->getDateTime(),
                                'processed' => 1
                            ]
                        );
                        break;
                    }
                }
            }
        }
    }

    /**
     * Get All Stores label given attribute value
     *
     * @param string $attributeId
     * @param string $optionId
     * @return array
     */
    public function getAllStoresLabelGivenAttributeAndOption($attributeId, $optionId)
    {
        $storeLabels = [];

        foreach ($this->replicationHelper->storeManager->getStores() as $store) {
            $valuesCollection = $this->attrOptionCollectionFactory->create()
                ->setAttributeFilter($attributeId)
                ->setStoreFilter($store->getId())
                ->setIdFilter($optionId);

            if ($valuesCollection->getItems()) {
                $label = $valuesCollection->getFirstItem()->getValue();
                $optionLabel = $this->optionLabelFactory->create();
                $optionLabel->setStoreId($store->getId());
                $optionLabel->setLabel($label);
                $storeLabels[] = $optionLabel;
            }
        }

        return $storeLabels;
    }

    /**
     * Get filter given values
     *
     * @param string $scopeId
     * @param string $langCode
     * @param string $translationId
     * @return array[]
     */
    public function getFiltersGivenValues($scopeId, $langCode, $translationId)
    {
        return [
            ['field' => 'main_table.scope_id', 'value' => $scopeId, 'condition_type' => 'eq'],
            ['field' => 'main_table.LanguageCode', 'value' => $langCode, 'condition_type' => 'eq'],
            [
                'field'          => 'main_table.TranslationId',
                'value'          => $translationId,
                'condition_type' => 'in'
            ],
            ['field' => 'main_table.text', 'value' => true, 'condition_type' => 'notnull'],
            ['field' => 'main_table.key', 'value' => true, 'condition_type' => 'notnull']
        ];
    }

    /**
     * Cater attributes label
     *
     * @param mixed $dataTranslation
     * @param string $code
     * @param int $storeId
     * @return void
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function updateAttributeLabel($dataTranslation, $code, $storeId)
    {
        $formattedCode   = $this->replicationHelper->formatAttributeCode($code);
        $attribute       = $this->eavAttributeFactory->create();
        $attributeObject = $attribute->loadByCode(\Magento\Catalog\Model\Product::ENTITY, $formattedCode);

        if (!empty($attributeObject->getId())) {
            $flag           = false;
            $frontendLabels = $attributeObject->getFrontendLabels();

            foreach ($frontendLabels as &$frontendLabel) {
                if ($frontendLabel->getStoreId() == $storeId) {
                    $frontendLabel->setLabel($dataTranslation->getText());
                    $flag = true;
                    break;
                }
            }

            if (!$flag) {
                $frontendLabels[] = $this->frontendLabelInterfaceFactory->create()
                    ->setStoreId($storeId)
                    ->setLabel($dataTranslation->getText());
            }
            $attributeObject->setData('frontend_labels', $frontendLabels);
            $this->productAttributeRepository->save($attributeObject);
            $dataTranslation->addData(
                [
                    'is_updated' => 0,
                    'processed_at' => $this->replicationHelper->getDateTime(),
                    'processed' => 1
                ]
            );
        }
    }
}
