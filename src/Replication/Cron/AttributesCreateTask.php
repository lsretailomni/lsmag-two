<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Api\Data\ReplUnitOfMeasureInterface;
use \Ls\Replication\Api\ReplAttributeOptionValueRepositoryInterface;
use \Ls\Replication\Api\ReplAttributeRepositoryInterface;
use \Ls\Replication\Api\ReplExtendedVariantValueRepositoryInterface as ReplExtendedVariantValueRepository;
use \Ls\Replication\Api\ReplUnitOfMeasureRepositoryInterface;
use \Ls\Replication\Api\ReplVendorRepositoryInterface;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use \Ls\Replication\Model\ReplAttribute;
use \Ls\Replication\Model\ReplAttributeOptionValue;
use \Ls\Replication\Model\ReplAttributeOptionValueSearchResults;
use \Ls\Replication\Model\ReplAttributeSearchResults;
use \Ls\Replication\Model\ReplExtendedVariantValue;
use \Ls\Replication\Model\ReplExtendedVariantValueSearchResults;
use \Ls\Replication\Model\ReplUnitOfMeasureSearchResults;
use \Ls\Replication\Model\ReplVendor;
use \Ls\Replication\Model\ReplVendorSearchResults;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\OptionManagement;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Class AttributesCreateTask
 * This job creates Soft and Hard Attributes from LS Central
 */
class AttributesCreateTask
{
    /**
     * @var ReplExtendedVariantValueRepository
     */
    public $replExtendedVariantValueRepository;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    public $productAttributeRepository;

    /** @var EavSetupFactory */
    public $eavSetupFactory;

    /** @var ReplAttributeRepositoryInterface */
    public $replAttributeRepositoryInterface;

    /** @var ReplAttributeOptionValueRepositoryInterface */
    public $replAttributeOptionValueRepositoryInterface;

    /** @var Config */
    public $eavConfig;

    /** @var ReplicationHelper */
    public $replicationHelper;

    /** @var LSR */
    public $lsr;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var bool
     */
    public $successCronAttribute = false;

    /**
     * @var bool
     */
    public $successCronAttributeVariant = false;

    /**
     * @var AttributeFactory
     */
    public $eavAttributeFactory;

    /**
     * @var Entity
     */
    public $eavEntity;

    /** @var int */
    public $remainingAttributesCount;

    /** @var int */
    public $remainingVariantsCount;

    /** @var StoreInterface $store */
    public $store;

    /** @var ReplUnitOfMeasureRepositoryInterface $replUnitOfMeasureRepositoryInterface */
    public $replUnitOfMeasureRepositoryInterface;

    /** @var AttributeOptionLabelInterfaceFactory $optionLabelFactory */
    public $optionLabelFactory;

    /**
     * @var AttributeOptionInterfaceFactory
     */
    public $optionFactory;

    /**
     * @var AttributeOptionManagementInterface
     */
    public $attributeOptionManagement;

    /**
     * @var ReplVendorRepositoryInterface
     */
    public $replVendorRepositoryInterface;

    /**
     * @var CollectionFactory
     */
    public $attrOptionCollectionFactory;

    /**
     * @var array
     */
    public $optionCollection;

    /**
     * AttributesCreateTask constructor.
     * @param ReplExtendedVariantValueRepository $replExtendedVariantValueRepository
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param EavSetupFactory $eavSetupFactory
     * @param Logger $logger
     * @param AttributeFactory $eavAttributeFactory
     * @param Entity $eav_entity
     * @param ReplAttributeRepositoryInterface $replAttributeRepositoryInterface
     * @param ReplAttributeOptionValueRepositoryInterface $replAttributeOptionValueRepositoryInterface
     * @param ReplUnitOfMeasureRepositoryInterface $replUnitOfMeasureRepositoryInterface
     * @param ReplVendorRepositoryInterface $replVendorRepositoryInterface
     * @param Config $eavConfig
     * @param ReplicationHelper $replicationHelper
     * @param AttributeOptionLabelInterfaceFactory $optionLabelFactory
     * @param AttributeOptionInterfaceFactory $optionFactory
     * @param OptionManagement $attributeOptionManagement
     * @param LSR $LSR
     * @param CollectionFactory $attrOptionCollectionFactory
     */
    public function __construct(
        ReplExtendedVariantValueRepository $replExtendedVariantValueRepository,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        EavSetupFactory $eavSetupFactory,
        Logger $logger,
        AttributeFactory $eavAttributeFactory,
        Entity $eav_entity,
        ReplAttributeRepositoryInterface $replAttributeRepositoryInterface,
        ReplAttributeOptionValueRepositoryInterface $replAttributeOptionValueRepositoryInterface,
        ReplUnitOfMeasureRepositoryInterface $replUnitOfMeasureRepositoryInterface,
        ReplVendorRepositoryInterface $replVendorRepositoryInterface,
        Config $eavConfig,
        ReplicationHelper $replicationHelper,
        AttributeOptionLabelInterfaceFactory $optionLabelFactory,
        AttributeOptionInterfaceFactory $optionFactory,
        OptionManagement $attributeOptionManagement,
        LSR $LSR,
        CollectionFactory $attrOptionCollectionFactory
    ) {
        $this->replExtendedVariantValueRepository          = $replExtendedVariantValueRepository;
        $this->productAttributeRepository                  = $productAttributeRepository;
        $this->eavSetupFactory                             = $eavSetupFactory;
        $this->logger                                      = $logger;
        $this->eavAttributeFactory                         = $eavAttributeFactory;
        $this->eavEntity                                   = $eav_entity;
        $this->replAttributeRepositoryInterface            = $replAttributeRepositoryInterface;
        $this->replAttributeOptionValueRepositoryInterface = $replAttributeOptionValueRepositoryInterface;
        $this->eavConfig                                   = $eavConfig;
        $this->replicationHelper                           = $replicationHelper;
        $this->lsr                                         = $LSR;
        $this->replUnitOfMeasureRepositoryInterface        = $replUnitOfMeasureRepositoryInterface;
        $this->replVendorRepositoryInterface               = $replVendorRepositoryInterface;
        $this->optionLabelFactory                          = $optionLabelFactory;
        $this->optionFactory                               = $optionFactory;
        $this->attributeOptionManagement                   = $attributeOptionManagement;
        $this->attrOptionCollectionFactory                 = $attrOptionCollectionFactory;
    }

    /**
     * Function for executing attribute replication
     *
     * @param null $storeData
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute($storeData = null)
    {
        /**
         * Get all the available stores config in the Magento system
         */
        if (!empty($storeData) && $storeData instanceof StoreInterface) {
            $stores = [$storeData];
        } else {
            /** @var StoreInterface[] $stores */
            $stores = $this->lsr->getAllStores();
        }
        if (!empty($stores)) {
            foreach ($stores as $store) {
                $this->lsr->setStoreId($store->getId());
                $this->store = $store;
                if ($this->lsr->isLSR($this->store->getId())) {
                    $this->replicationHelper->updateConfigValue(
                        $this->replicationHelper->getDateTime(),
                        LSR::SC_CRON_ATTRIBUTE_CONFIG_PATH_LAST_EXECUTE,
                        $store->getId()
                    );
                    // Process display only attributes which are going to be used for product specification
                    $this->processAttributes($store);
                    // Process variants attributes which are going to be used for configurable product
                    $this->processVariantAttributes($store);
                    //Process Attribute Option Values
                    $this->updateAttributeOptionValues($store);
                    //Process UOM Attribute Options
                    $this->addUomAttributeOptions($store);
                    //Process Vendor Options
                    $this->addVendorAttributeOptions($store);
                    //Convert Attribute to Visual Swatch
                    $this->convertAttributeToVisualSwatch($store);
                    $this->replicationHelper->updateCronStatus(
                        $this->successCronAttribute,
                        LSR::SC_SUCCESS_CRON_ATTRIBUTE,
                        $store->getId()
                    );
                    $this->replicationHelper->updateCronStatus(
                        $this->successCronAttributeVariant,
                        LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT,
                        $store->getId()
                    );
                }
                $this->lsr->setStoreId(null);
            }
        }
        $this->caterAttributesRemoval();
    }

    /**
     * @param null $storeData
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function executeManually($storeData = null)
    {
        $this->execute($storeData);
        $remainingAttributes = (int)$this->getRemainingAttributesToProcess($storeData->getId());
        $remainingVariants   = (int)$this->getRemainingVariantsToProcess($storeData->getId());
        return [$remainingAttributes + $remainingVariants];
    }

    /**
     * Process Attributes
     * @param null $store
     */
    public function processAttributes($store = null)
    {
        $batchSize = $this->replicationHelper->getProductAttributeBatchSize();
        try {
            $criteria = $this->replicationHelper->buildCriteriaForNewItems(
                'scope_id',
                $store->getId(),
                'eq',
                $batchSize,
                true
            );
            /** @var ReplAttributeSearchResults $replAttributes */
            $replAttributes = $this->replAttributeRepositoryInterface->getList($criteria);

            /** Default attribute set id for catalog_product */
            $defaultAttributeSetId = $this->replicationHelper->getDefaultAttributeSetId();

            /** Default group id of general tab for specific product attribute set */
            $defaultGroupId = $this->replicationHelper->getDefaultGroupIdOfAttributeSet($defaultAttributeSetId);

            /** @var ReplAttribute $replAttribute */
            if ($replAttributes->getTotalCount() > 0) {
                foreach ($replAttributes->getItems() as $replAttribute) {
                    $this->createAttributeByObject($replAttribute, $defaultAttributeSetId, $defaultGroupId);
                    if ($replAttribute->getValueType() == '5' || $replAttribute->getValueType() == '7') {
                        $this->addAttributeOptions($replAttribute->getCode());
                    }
                }
                $remainingAttributes = (int)$this->getRemainingAttributesToProcess($store->getId());
                if ($remainingAttributes == 0) {
                    $this->successCronAttribute = true;
                }
            } else {
                $this->successCronAttribute = true;
            }
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * Cater Attributes Removal
     */
    public function caterAttributesRemoval()
    {
        $variantBatchSize = $this->replicationHelper->getVariantBatchSize();
        $criteria         = $this->replicationHelper->buildCriteriaGetDeletedOnly([], $variantBatchSize);
        /** @var ReplAttributeSearchResults $replAttributes */
        $replAttributes = $this->replAttributeRepositoryInterface->getList($criteria);
        /** @var ReplAttribute $replAttribute */
        foreach ($replAttributes->getItems() as $replAttribute) {
            try {
                $formattedCode = $this->replicationHelper->formatAttributeCode($replAttribute->getCode());
                $attribute     = $this->eavConfig->getAttribute(Product::ENTITY, $formattedCode);
                if ($attribute) {
                    $attributeId  = $attribute->getId();
                    $entityTypeId = $this->eavConfig->getEntityType(
                        Product::ENTITY
                    )->getEntityTypeId();
                    $this->eavSetupFactory->create()->updateAttribute(
                        $entityTypeId,
                        $attributeId,
                        'is_visible_on_front',
                        0,
                        null
                    );
                }
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
                $replAttribute->setData('is_failed', 1);
            }
            $replAttribute->setData('processed_at', $this->replicationHelper->getDateTime());
            $replAttribute->setData('processed', 1);
            $replAttribute->setData('is_updated', 0);
            // @codingStandardsIgnoreLine
            $this->replAttributeRepositoryInterface->save($replAttribute);
        }
    }

    /**
     * Process Variant Attributes
     * @param $store
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function processVariantAttributes($store)
    {
        $variantBatchSize = $this->replicationHelper->getProductAttributeBatchSize();
        $this->logger->debug('Running variants create task for store ' . $store->getName());
        $criteria = $this->replicationHelper->buildCriteriaForNewItems(
            'scope_id',
            $store->getId(),
            'eq',
            $variantBatchSize,
            true
        );
        /** @var ReplExtendedVariantValueSearchResults $variants */
        $variants     = $this->replExtendedVariantValueRepository->getList($criteria);
        $variantCodes = [];
        if ($variants->getTotalCount() > 0) {
            /** @var ReplExtendedVariantValue $variant */
            foreach ($variants->getItems() as $variant) {
                if (empty($variantCodes[$variant->getCode()]) ||
                    !array_key_exists($variant->getValue(), $variantCodes[$variant->getCode()])) {
                    $variantCodes[$variant->getCode()][$variant->getValue()][$variant->getLogicalOrder()]
                        = $variant->getValue();
                }
                $variant->setData('processed_at', $this->replicationHelper->getDateTime());
                $variant->setData('processed', 1);
                $variant->setData('is_updated', 0);
                // @codingStandardsIgnoreLine
                $this->replExtendedVariantValueRepository->save($variant);
            }

            foreach ($variantCodes as $code => $value) {
                $formattedCode = $this->replicationHelper->formatAttributeCode($code);
                $attribute     = $this->eavConfig->getAttribute(Product::ENTITY, $formattedCode);
                if (!$attribute || !$attribute->getAttributeId()) {
                    $attributeData = [
                        'attribute_code'                => $formattedCode,
                        'is_global'                     => ScopedAttributeInterface::SCOPE_GLOBAL,
                        'frontend_label'                => ucwords(strtolower($code)),
                        'frontend_input'                => 'multiselect',
                        'source_model'                  => Table::class,
                        'default_value_text'            => '',
                        'default_value_yesno'           => 0,
                        'default_value_date'            => '',
                        'default_value_textarea'        => '',
                        'is_unique'                     => 0,
                        'apply_to'                      => 0,
                        'is_required'                   => 0,
                        'is_configurable'               => 1,
                        'is_searchable'                 => 1,
                        'is_comparable'                 => 1,
                        'is_user_defined'               => 1,
                        'is_visible_in_advanced_search' => 1,
                        'is_used_for_price_rules'       => 0,
                        'is_wysiwyg_enabled'            => 0,
                        'is_html_allowed_on_front'      => 1,
                        'is_visible_on_front'           => 1,
                        'used_in_product_listing'       => 0,
                        'used_for_sort_by'              => 1,
                        'is_filterable'                 => 1,
                        'is_filterable_in_search'       => 1,
                        'backend_type'                  => 'varchar',
                        'is_used_in_grid'               => 1,
                        'is_visible_in_grid'            => 1,
                        'is_filterable_in_grid'         => 1
                    ];
                    try {
                        // @codingStandardsIgnoreStart
                        $this->eavAttributeFactory->create()
                            ->addData($attributeData)
                            ->setEntityTypeId($this->getEntityTypeId(Product::ENTITY))
                            ->save();
                        // @codingStandardsIgnoreEnd
                    } catch (Exception $e) {
                        $this->logger->debug($e->getMessage());
                    }
                }
                $existingOptions = $this->getOptimizedOptionArrayByAttributeCode($formattedCode);
                if (empty($existingOptions)) {
                    foreach ($value as $eachVariantValue) {
                        if (isset($eachVariantValue)) {
                            $this->eavSetupFactory->create()
                                ->addAttributeOption(
                                    [
                                        'values'       => $eachVariantValue,
                                        'attribute_id' => $this->getAttributeIdByCode($formattedCode)
                                    ]
                                );
                        }
                    }
                } elseif (!empty($value)) {
                    foreach ($value as $k => $v) {
                        foreach ($v as $logicalOrder => $optionValue) {
                            if (isset($optionValue)) {
                                if (!in_array($optionValue, $existingOptions, true)) {
                                    $this->eavSetupFactory->create()
                                        ->addAttributeOption(
                                            [
                                                'values'       => $v,
                                                'attribute_id' => $this->getAttributeIdByCode($formattedCode)
                                            ]
                                        );
                                } else {
                                    $this->updateVariantLogicalOrderByLabel($formattedCode, $v);
                                }
                            }
                        }
                    }
                }
            }
            /** fetching the list again to get the remaining records yet to process in order
             * to set the cron job status
             */
            $remainingVariants = (int)$this->getRemainingVariantsToProcess($store->getId());
            if ($remainingVariants == 0) {
                $this->successCronAttributeVariant = true;
            }
        } else {
            $this->successCronAttributeVariant = true;
        }
        $this->logger->debug('Finished variants create task for store ' . $store->getName());
    }

    /**
     * @param $formattedCode
     * @param $updatedOptionArray
     */
    public function updateVariantLogicalOrderByLabel($formattedCode, $updatedOptionArray)
    {
        try {
            $attribute = $this->eavAttributeFactory->create();
            $attribute = $attribute->loadByCode(Product::ENTITY, $formattedCode);
            $options   = $attribute->getOptions();
            foreach ($updatedOptionArray as $sortOrder => $label) {
                foreach ($options as $option) {
                    if (empty($option->getValue())) {
                        continue;
                    }
                    if ($option->getLabel() == $label) {
                        $option->setSortOrder($sortOrder);
                        $attribute->setOptions([$option]);
                        $this->productAttributeRepository->save($attribute);
                        break;
                    }
                }
            }
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * @param ReplAttribute $replAttribute
     * @param $attributeSetId
     * @param $attributeGroupId
     * @throws LocalizedException
     */
    public function createAttributeByObject(ReplAttribute $replAttribute, $attributeSetId, $attributeGroupId)
    {
        $formattedCode = $this->replicationHelper->formatAttributeCode($replAttribute->getCode());
        /** @var AttributeInterface $attribute */
        $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $formattedCode);
        //  create attribute if not exist.
        if (!$attribute || !$attribute->getAttributeId()) {
            $valueTypeArray = $this->getValueTypeArray();
            $frontendInput  = $valueTypeArray[$replAttribute->getValueType()];
            $attributeData  = [
                'attribute_code'                => $formattedCode,
                'is_global'                     => ScopedAttributeInterface::SCOPE_STORE,
                'frontend_label'                => $replAttribute->getDescription() ?: $replAttribute->getCode(),
                'frontend_input'                => $frontendInput,
                'is_unique'                     => 0,
                'apply_to'                      => 0,
                'is_required'                   => 0,
                'is_configurable'               => 0,
                'is_searchable'                 => 1,
                'is_comparable'                 => 1,
                'is_user_defined'               => 1,
                'is_visible_in_advanced_search' => 1,
                'is_used_for_price_rules'       => 0,
                'is_wysiwyg_enabled'            => 0,
                'is_html_allowed_on_front'      => 1,
                'is_visible_on_front'           => 1,
                'used_in_product_listing'       => 0,
                'used_for_sort_by'              => 1,
                'backend_type'                  => 'varchar',
                'backend_model'                 => ArrayBackend::class,
                'is_filterable'                 => ($frontendInput === 'multiselect') ? 1 : 0,
                'is_filterable_in_search'       => ($frontendInput === 'multiselect') ? 1 : 0
            ];

            try {
                $this->eavAttributeFactory->create()
                    ->addData($attributeData)
                    ->setEntityTypeId($this->getEntityTypeId(Product::ENTITY))
                    ->save();
                $this->logger->debug('Successfully created attribute : ' . $formattedCode);
            } catch (Exception $e) {
                $this->logger->debug('Failed with Exception : ' . $e->getMessage());
                $replAttribute->setData('is_failed', 1);
            }
        } else {
            $this->logger->debug('Attribute Code already exist: ' . $formattedCode);
        }
        $replAttribute->setData('processed', 1);
        $replAttribute->setData('processed_at', $this->replicationHelper->getDateTime());
        $replAttribute->setData('is_updated', 0);
        // @codingStandardsIgnoreLine
        $this->replAttributeRepositoryInterface->save($replAttribute);
    }

    /**
     * @param string
     * @return int
     */
    public function getEntityTypeId(
        $type = Product::ENTITY
    ) {
        return $this->eavEntity->setType($type)->getTypeId();
    }

    /**
     * @param string $attribute_code
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function addAttributeOptions($attribute_code = '')
    {
        $option_data = $this->generateOptionValues($attribute_code);
        if (!empty($option_data)) {
            $formattedCode               = $this->replicationHelper->formatAttributeCode($attribute_code);
            $option_data['attribute_id'] = $this->getAttributeIdByCode($formattedCode);
            $this->eavSetupFactory->create()->addAttributeOption($option_data);
        }
    }

    /**
     * @param string $attribute_code
     * @return array
     * @throws LocalizedException
     */
    public function generateOptionValues($attribute_code = '')
    {
        $optionArray = [];
        $criteria    = $this->replicationHelper->buildCriteriaForNewItems('Code', $attribute_code, 'eq', -1, 1);
        /** @var ReplAttributeOptionValueSearchResults $replAttributeOptionValues */
        $replAttributeOptionValues = $this->replAttributeOptionValueRepositoryInterface->getList($criteria);

        // Get existing options array
        $formattedCode   = $this->replicationHelper->formatAttributeCode($attribute_code);
        $existingOptions = $this->getOptimizedOptionArrayByAttributeCode($formattedCode);

        /** @var ReplAttributeOptionValue $item */
        foreach ($replAttributeOptionValues->getItems() as $item) {
            $item->setIsUpdated(0);
            $item->setProcessed(1);
            if (empty($item->getValue())) {
                $item->setIsFailed(1);
            }
            $item->setProcessedAt($this->replicationHelper->getDateTime());
            // @codingStandardsIgnoreLine
            $this->replAttributeOptionValueRepositoryInterface->save($item);
            // If have existing option and current value is a part of existing option then don't do anything
            if (empty($item->getValue()) || (!empty($existingOptions)
                    && in_array($item->getValue(), $existingOptions, true))) {
                continue;
            }
            $optionArray['values'][] = $item->getValue();
            $existingOptions[]       = $item->getValue();
        }
        return $optionArray;
    }

    /**
     * @param $store
     */
    public function updateAttributeOptionValues($store)
    {
        $optionResults = $this->updateOptionValues($store);
        if (!empty($optionResults)) {
            // for inserting processed = 0 values;
            foreach ($optionResults as $attributeCode => $optionData) {
                if (!empty($optionData)) {
                    foreach ($optionData as $data) {
                        try {
                            $this->eavSetupFactory->create()->addAttributeOption($data);
                        } catch (Exception $e) {
                            $this->logger->debug("Update attribute - $attributeCode failed with exception : "
                                . $e->getMessage());
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $store
     * @return array
     */
    public function updateOptionValues($store)
    {
        $optionArray = [];
        $criteria    = $this->replicationHelper->buildCriteriaForNewItems('scope_id', $store->getId(), 'eq', -1, true);
        /** @var ReplAttributeOptionValueSearchResults $replAttributeOptionValues */
        $replAttributeOptionValues = $this->replAttributeOptionValueRepositoryInterface->getList($criteria);
        $optionResults             = [];
        /** @var ReplAttributeOptionValue $item */
        foreach ($replAttributeOptionValues->getItems() as $item) {
            try {
                $formattedCode   = $this->replicationHelper->formatAttributeCode($item->getCode());
                $existingOptions = $this->getOptimizedOptionArrayByAttributeCode($formattedCode);
                $attributeId     = $this->getAttributeIdByCode($formattedCode);
                if (!empty($item->getValue())) {
                    // Just add the option value if it doesn't exist
                    if (!in_array($item->getValue(), $existingOptions, true)) {
                        $optionArray['values'][]         = $item->getValue();
                        $optionArray['attribute_id']     = $attributeId;
                        $optionResults[$formattedCode][] = $optionArray;
                    }
                } else {
                    $item->setIsFailed(1);
                }
            } catch (Exception $e) {
                $item->setIsFailed(1);
                $this->logger->debug($e->getMessage());
            }
            $item->setProcessed(1);
            $item->setIsUpdated(0);
            $item->setProcessedAt($this->replicationHelper->getDatetime());
            // @codingStandardsIgnoreLine
            $this->replAttributeOptionValueRepositoryInterface->save($item);
        }
        return $optionResults;
    }

    /**
     * @param string $attribute_code
     * @return int|null
     * @throws NoSuchEntityException
     */
    public function getAttributeIdByCode($attribute_code = '')
    {
        return $this->productAttributeRepository->get($attribute_code)->getAttributeId();
    }

    /**
     * @return array
     */
    public function getValueTypeArray()
    {
        return [
            '0'   => 'text',
            '1'   => 'text',
            '2'   => 'price',
            '3'   => 'date',
            '4'   => 'text',
            '5'   => 'multiselect',
            '6'   => 'text',
            '7'   => 'multiselect',
            '100' => 'text'
        ];
    }

    /**
     * @param string $attribute_code
     * @return array
     * @throws LocalizedException
     */
    public function getOptimizedOptionArrayByAttributeCode($attribute_code = '')
    {
        $optimizedArray = [];
        if ($attribute_code == '' || $attribute_code == null) {
            return $optimizedArray;
        }
        $existingOptions = $this->eavConfig->getAttribute(Product::ENTITY, $attribute_code)
            ->getSource()
            ->getAllOptions();
        if (!empty($existingOptions)) {
            foreach ($existingOptions as $k) {
                $optimizedArray[] = $k['label'];
            }
        }
        return $optimizedArray;
    }

    /**
     * @param $store
     * @return array
     * @throws LocalizedException
     */
    public function getUomOptions($store)
    {
        $optionArray = [];
        $criteria    = $this->replicationHelper->buildCriteriaForNewItems('scope_id', $store->getId(), 'eq', -1, true);
        /** @var ReplUnitOfMeasureSearchResults $replUomOptionValues */
        $replUomOptionValues = $this->replUnitOfMeasureRepositoryInterface->getList($criteria);

        // Get existing options array
        $formattedCode   = LSR::LS_UOM_ATTRIBUTE;
        $existingOptions = $this->getOptimizedOptionArrayByAttributeCode($formattedCode);

        /** @var ReplUnitOfMeasureInterface $item */
        foreach ($replUomOptionValues->getItems() as $item) {
            $item->setIsUpdated(0);
            $item->setProcessed(1);
            if (empty($item->getNavId())) {
                $item->setIsFailed(1);
            }
            $item->setProcessedAt($this->replicationHelper->getDateTime());
            // @codingStandardsIgnoreLine
            $this->replUnitOfMeasureRepositoryInterface->save($item);
            // If have existing option and current value is a part of existing option then don't do anything
            if (empty($item->getDescription()) || (!empty($existingOptions)
                    && in_array($item->getDescription(), $existingOptions, true))) {
                continue;
            }
            $optionArray[$item->getNavId()] = $item->getDescription();
            $existingOptions[]              = $item->getDescription();
        }
        return $optionArray;
    }

    /**
     * @param $store
     * @return array
     * @throws LocalizedException
     */
    public function getVendorOptions($store)
    {
        $optionArray = [];
        $criteria    = $this->replicationHelper->buildCriteriaForNewItems('scope_id', $store->getId(), 'eq', -1, true);
        /** @var ReplVendorSearchResults $replVendorOptionValues */
        $replVendorOptionValues = $this->replVendorRepositoryInterface->getList($criteria);

        // Get existing options array
        $formattedCode   = LSR::LS_VENDOR_ATTRIBUTE;
        $existingOptions = $this->getOptimizedOptionArrayByAttributeCode($formattedCode);

        /** @var ReplVendor $item */
        foreach ($replVendorOptionValues->getItems() as $item) {
            $item->setIsUpdated(0);
            $item->setProcessed(1);
            if (empty($item->getNavId())) {
                $item->setIsFailed(1);
            }
            $item->setProcessedAt($this->replicationHelper->getDateTime());
            // @codingStandardsIgnoreLine
            $this->replVendorRepositoryInterface->save($item);
            // If have existing option and current value is a part of existing option then don't do anything
            if (empty($item->getName()) || (!empty($existingOptions)
                    && in_array($item->getName(), $existingOptions, true))) {
                continue;
            }
            $optionArray[$item->getNavId()] = $item->getName();
            $existingOptions[]              = $item->getName();
        }
        return $optionArray;
    }

    /**
     * @param $store
     * @throws LocalizedException
     */
    public function addUomAttributeOptions($store)
    {
        $optionData = $this->getUomOptions($store);
        foreach ($optionData as $value => $label) {
            $optionLabel = $this->optionLabelFactory->create();
            $optionLabel->setStoreId(0);
            $optionLabel->setLabel($label);

            $option = $this->optionFactory->create();
            $option->setLabel($label);
            $option->setValue($value);
            $option->setStoreLabels([$optionLabel]);
            $option->setSortOrder(0);
            $option->setIsDefault(false);

            try {
                $this->attributeOptionManagement->add(
                    LSR::LS_UOM_ATTRIBUTE,
                    $option
                );
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
    }

    /**
     * @param $store
     * @throws LocalizedException
     */
    public function addVendorAttributeOptions($store)
    {
        $optionData = $this->getVendorOptions($store);
        foreach ($optionData as $value => $label) {
            $optionLabel = $this->optionLabelFactory->create();
            $optionLabel->setStoreId(0);
            $optionLabel->setLabel($label);

            $option = $this->optionFactory->create();
            $option->setLabel($label);
            $option->setValue($value);
            $option->setStoreLabels([$optionLabel]);
            $option->setSortOrder(0);
            $option->setIsDefault(false);

            $this->attributeOptionManagement->add(
                LSR::LS_VENDOR_ATTRIBUTE,
                $option
            );
        }

        $this->replicationHelper->updateCronStatus(
            true,
            LSR::SC_SUCCESS_CRON_VENDOR,
            $this->store->getId()
        );
    }

    /**
     * @param $storeId
     * @return int
     */
    public function getRemainingAttributesToProcess($storeId)
    {
        if (!$this->remainingAttributesCount) {
            $criteria                       = $this->replicationHelper->buildCriteriaForNewItems(
                'scope_id',
                $storeId,
                'eq'
            );
            $this->remainingAttributesCount = $this->replAttributeRepositoryInterface->getList($criteria)
                ->getTotalCount();
        }
        return $this->remainingAttributesCount;
    }

    /**
     * @param $storeId
     * @return int
     */
    public function getRemainingVariantsToProcess($storeId)
    {
        if (!$this->remainingVariantsCount) {
            $criteria                     = $this->replicationHelper->buildCriteriaForNewItems(
                'scope_id',
                $storeId,
                'eq'
            );
            $this->remainingVariantsCount = $this->replExtendedVariantValueRepository->getList($criteria)
                ->getTotalCount();
        }
        return $this->remainingVariantsCount;
    }

    /**
     * Add visual swatch type options
     *
     * @param $formattedCode
     * @return void
     * @throws LocalizedException
     */
    public function addVisualSwatchTypeOptions($formattedCode)
    {
        $attribute = $this->eavConfig->getAttribute('catalog_product', $formattedCode);
        if (!$attribute) {
            return;
        }
        $attributeData['option']                       = $this->addExistingOptions($attribute);
        $attributeData['frontend_input']               = 'select';
        $attributeData['swatch_input_type']            = 'visual';
        $attributeData['update_product_preview_image'] = 1;
        $attributeData['use_product_image_for_swatch'] = 0;
        $attributeData['optionvisual']                 = $this->getOptionSwatch($attributeData);
        $attributeData['defaultvisual']                = $this->getOptionDefaultVisual($attributeData);
        $attributeData['swatchvisual']                 = $this->getOptionSwatchVisual($attributeData);
        $attribute->addData($attributeData);
        // @codingStandardsIgnoreLine
        $attribute->save();
    }

    /**
     * Arrange the option value
     *
     * @param array $attributeData
     * @return array
     */
    public function getOptionSwatch(array $attributeData)
    {
        $optionSwatch = ['order' => [], 'value' => [], 'delete' => []];
        $count        = 0;
        foreach ($attributeData['option'] as $optionKey => $optionValue) {
            $optionSwatch['delete'][$optionKey] = '';
            $optionSwatch['order'][$optionKey]  = (string)$count++;
            $optionSwatch['value'][$optionKey]  = [$optionValue, ''];
        }
        return $optionSwatch;
    }

    /**
     * Add exisitng option value
     *
     * @param $attribute
     * @return array
     */
    public function addExistingOptions($attribute)
    {
        $options     = [];
        $attributeId = $attribute->getId();
        if ($attributeId) {
            $this->loadOptionCollection($attributeId);
            /** @var \Magento\Eav\Model\Entity\Attribute\Option $option */
            foreach ($this->optionCollection[$attributeId] as $option) {
                $options[$option->getId()] = $option->getValue();
            }
        }
        return $options;
    }

    /**
     * Load option collection
     *
     * @param $attributeId
     * @return void
     */
    public function loadOptionCollection($attributeId)
    {
        if (empty($this->optionCollection[$attributeId])) {
            $this->optionCollection[$attributeId] = $this->attrOptionCollectionFactory->create()
                ->setAttributeFilter($attributeId)
                ->setPositionOrder('asc', true)
                ->load();
        }
    }

    /**
     * Get the option value from the color mapping
     *
     * @param array $attributeData
     * @return array
     */
    public function getOptionSwatchVisual(array $attributeData)
    {
        $optionSwatch = ['value' => []];
        foreach ($attributeData['option'] as $optionKey => $optionValue) {
            $optionValue = strtoupper($optionValue);
            if (substr($optionValue, 0, 1) == '#' && strlen($optionValue) == 7) {
                $optionSwatch['value'][$optionKey] = $optionValue;
            } elseif (!empty($this->lsr->getColorCodes()[$optionValue])) {
                $optionSwatch['value'][$optionKey] = $this->lsr->getColorCodes()[$optionValue];
            } else {
                $optionSwatch['value'][$optionKey] = $this->lsr->getColorCodes()['WHITE'];
            }
        }

        return $optionSwatch;
    }

    /**
     * Get the default value for swatch
     *
     * @param array $attributeData
     * @return array
     */
    public function getOptionDefaultVisual(array $attributeData)
    {
        $optionSwatch = $this->getOptionSwatchVisual($attributeData);
        return [array_keys($optionSwatch['value'])[0]];
    }

    /**
     * Function to convert attribute to visual swatch
     *
     * @param $store
     * @return void
     * @throws LocalizedException
     */
    public function convertAttributeToVisualSwatch($store)
    {
        $swatchTypeAttributes     = $this->replicationHelper->getVisualSwatchAttributes($store->getId());
        $isVisualSwatchAttributes = $this->replicationHelper->isVisualSwatchAttributes($store->getId());
        if ($isVisualSwatchAttributes) {
            $swatchTypeAttributes = explode(",", $swatchTypeAttributes);
            foreach ($swatchTypeAttributes as $swatchTypeAttribute) {
                $this->addVisualSwatchTypeOptions($swatchTypeAttribute);
            }
        }
    }
}
