<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Api\ReplAttributeOptionValueRepositoryInterface;
use \Ls\Replication\Api\ReplAttributeRepositoryInterface;
use \Ls\Replication\Api\ReplExtendedVariantValueRepositoryInterface as ReplExtendedVariantValueRepository;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use \Ls\Replication\Model\ReplAttribute;
use \Ls\Replication\Model\ReplAttributeOptionValue;
use \Ls\Replication\Model\ReplAttributeOptionValueSearchResults;
use \Ls\Replication\Model\ReplAttributeSearchResults;
use \Ls\Replication\Model\ReplExtendedVariantValue;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Api\AttributeManagementInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class AttributesCreateTask
 * @package Ls\Replication\Cron
 */
class AttributesCreateTask
{

    const CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_attributes';

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
     * @var AttributeManagementInterface
     */
    public $attributeManagement;

    /**
     * @var AttributeFactory
     */
    public $eavAttributeFactory;

    /**
     * @var Entity
     */
    public $eavEntity;

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
     * @param Config $eavConfig
     * @param ReplicationHelper $replicationHelper
     * @param LSR $LSR
     * @param AttributeManagementInterface $attributeManagement
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
        Config $eavConfig,
        ReplicationHelper $replicationHelper,
        LSR $LSR,
        AttributeManagementInterface $attributeManagement
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
        $this->attributeManagement                         = $attributeManagement;
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $this->replicationHelper->updateConfigValue(
            $this->replicationHelper->getDateTime(),
            self::CONFIG_PATH_LAST_EXECUTE
        );
        // Process display only attributes which are going to be used for product specification
        $this->processAttributes();
        // Process variants attributes which are going to be used for configurable product
        $this->processVariantAttributes();
        //Process Attribute Option Values
        $this->updateAttributeOptionValues();

        $this->replicationHelper->updateCronStatus($this->successCronAttribute, LSR::SC_SUCCESS_CRON_ATTRIBUTE);
        $this->replicationHelper->updateCronStatus(
            $this->successCronAttributeVariant,
            LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT
        );
        $this->caterAttributesRemoval();
    }

    /**
     * For Manual Cron
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function executeManually()
    {
        $this->execute();
        $criteria = $this->replicationHelper->buildCriteriaForNewItems();
        /** @var ReplAttributeSearchResults $replAttributes */
        $replAttributes     = $this->replAttributeRepositoryInterface->getList($criteria);
        $itemsLeftToProcess = count($replAttributes->getItems());
        return [$itemsLeftToProcess];
    }

    /**
     * Process Attributes
     */
    public function processAttributes()
    {
        try {
            $criteria = $this->replicationHelper->buildCriteriaForNewItems('', '', '', -1, 1);

            /** @var ReplAttributeSearchResults $replAttributes */
            $replAttributes = $this->replAttributeRepositoryInterface->getList($criteria);

            /** @var defualt attribute set if for catalog_product $defaultAttributeSetId */
            $defaultAttributeSetId = $this->replicationHelper->getDefaultAttributeSetId();

            /** @var default group if of general tab for specific product attribute set $defaultGroupId */
            $defaultGroupId = $this->replicationHelper->getDefaultGroupIdOfAttributeSet($defaultAttributeSetId);

            /** @var ReplAttribute $replAttribute */
            foreach ($replAttributes->getItems() as $replAttribute) {
                $this->createAttributeByObject($replAttribute, $defaultAttributeSetId, $defaultGroupId);
                if ($replAttribute->getValueType() == '5' || $replAttribute->getValueType() == '7') {
                    $this->addAttributeOptions($replAttribute->getCode());
                }
            }
            if (count($replAttributes->getItems()) == 0) {
                $this->successCronAttribute = true;
            }
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * @return int
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
            $replAttribute->setData('processed', 1);
            $replAttribute->setData('IsDeleted', 0);
            $replAttribute->setData('is_updated', 0);
            // @codingStandardsIgnoreLine
            $this->replAttributeRepositoryInterface->save($replAttribute);
        }
    }

    /**
     * Create Variants Attribute
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function processVariantAttributes()
    {
        $variantBatchSize = $this->replicationHelper->getVariantBatchSize();
        $this->logger->debug('Running variants create task');
        /** @var default attribute set id for catalog_product $defaultAttributeSetId */
        $defaultAttributeSetId = $this->replicationHelper->getDefaultAttributeSetId();
        /** @var default group id of general tab for specific product attribute set $defaultGroupId */
        $defaultGroupId = $this->replicationHelper->getDefaultGroupIdOfAttributeSet($defaultAttributeSetId);

        $criteria     = $this->replicationHelper->buildCriteriaForNewItems('', '', '', $variantBatchSize, 1);
        $variants     = $this->replExtendedVariantValueRepository->getList($criteria)->getItems();
        $variantCodes = [];
        /** @var ReplExtendedVariantValue $variant */
        foreach ($variants as $variant) {
            if (empty($variantCodes[$variant->getCode()]) ||
                !in_array($variant->getValue(), $variantCodes[$variant->getCode()], true)
            ) {
                $variantCodes[$variant->getCode()][] = $variant->getValue();
            }
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
                    'is_global'                     => 1,
                    'frontend_label'                => ucwords(strtolower($code)),
                    'frontend_input'                => 'select',
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
                    'is_filterable_in_grid'         => 1,
                    'attribute_set_id'              => $defaultAttributeSetId,
                    'attribute_group_id'            => $defaultGroupId
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
        }
        if (count($variants) === 0) {
            $this->successCronAttributeVariant = true;
        }
        $this->logger->debug('Finished variants create task.');
    }

    /**
     * Update the order of the options in ascending order
     * @param $formattedCode
     */
    public function updateOptions($formattedCode)
    {
        try {
            $attribute = $this->eavAttributeFactory->create();
            $attribute = $attribute->loadByCode(Product::ENTITY, $formattedCode);
            $options   = $attribute->getOptions();
            $labels    = [];
            foreach ($options as $index => $option) {
                if (!empty($option->getValue())) {
                    $labels[$option->getValue()] = $option->getLabel();
                } else {
                    unset($options[$index]);
                }
            }
            asort($labels);
            foreach ($options as &$option) {
                $sortOrder = array_search($option->getValue(), array_keys($labels));
                $option->setSortOrder($sortOrder);
            }
            $attribute->setOptions($options);
            $this->productAttributeRepository->save($attribute);
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
    public function createAttributeByObject(
        ReplAttribute $replAttribute,
        $attributeSetId,
        $attributeGroupId
    ) {
        $formattedCode = $this->replicationHelper->formatAttributeCode($replAttribute->getCode());
        /** @var AttributeInterface $attribute */
        $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $formattedCode);
        //  create attribute if not exist.
        if (!$attribute || !$attribute->getAttributeId()) {
            $valueTypeArray = $this->getValueTypeArray();
            $frontendInput  = $valueTypeArray[$replAttribute->getValueType()];
            $attributeData  = [
                'attribute_code'                => $formattedCode,
                'is_global'                     => 1,
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
                'attribute_set_id'              => $attributeSetId,
                'attribute_group_id'            => $attributeGroupId,
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
        $replAttribute->setData('is_updated', 0);
        // @codingStandardsIgnoreLine
        $this->replAttributeRepositoryInterface->save($replAttribute);
    }

    /**
     * @param string
     * @return int
     */
    public function getEntityTypeId($type = Product::ENTITY)
    {
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
            $option_data['attribute_id'] = $this->getAttributeIdbyCode($formattedCode);
            $this->addUpdateAttributeOption($option_data);
            $this->updateOptions($formattedCode);
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
        $existingOptions = $this->getOptimizedOptionArrayByAttributeCode($attribute_code);

        /** @var ReplAttributeOptionValue $item */
        foreach ($replAttributeOptionValues->getItems() as $item) {
            $item->setProcessed(1);
            // @codingStandardsIgnoreLine
            $this->replAttributeOptionValueRepositoryInterface->save($item);
            // If have existing option and current value is a part of existing option then don't do anything
            if (!empty($existingOptions) && !empty($item->getValue())
                && in_array($item->getValue(), $existingOptions, true)) {
                continue;
            }
            $optionArray['values'][$item->getId()] = $item->getValue();
        }
        return $optionArray;
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function updateAttributeOptionValues()
    {
        $option_data = $this->updateOptionValues();
        if (!empty($option_data)) {
            $this->addUpdateAttributeOption($option_data);
            $this->updateOptions($option_data['attribute_code']);
        }
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function updateOptionValues()
    {
        $optionArray = [];
        $criteria    = $this->replicationHelper->buildCriteriaForNewItems('', '', '', -1, false);
        /** @var ReplAttributeOptionValueSearchResults $replAttributeOptionValues */
        $replAttributeOptionValues = $this->replAttributeOptionValueRepositoryInterface->getList($criteria);

        /** @var ReplAttributeOptionValue $item */
        foreach ($replAttributeOptionValues->getItems() as $item) {
            $attributeCode = $this->replicationHelper->formatAttributeCode($item->getCode());
            // Get existing options array
            $existingOptions = $this->getOptimizedOptionArrayByAttributeCode($attributeCode);
            $item->setProcessed(1);
            // If have existing option and current value is a part of existing option then don't do anything
            if (!empty($existingOptions) && !empty($item->getValue())
                && in_array($item->getValue(), $existingOptions, true)) {
                continue;
            }
            try {
                $optionArray['attribute_id'] = $this->getAttributeIdbyCode($attributeCode);
            } catch (\Exception $e) {
                $item->setData('is_failed', 1);
            }
            // @codingStandardsIgnoreLine
            $this->replAttributeOptionValueRepositoryInterface->save($item);
            $optionId = $item->getId();
            if (!in_array($item->getValue(), $existingOptions, true)) {
                $optionArray['value'][$optionId]  = $item->getValue();
                $optionArray['update'][$optionId] = $item->getValue();
                $optionArray['attribute_code']    = $attributeCode;
            } elseif ($item->getIsDeleted() == 1 && in_array($item->getValue(), $existingOptions, true)) {
                $optionArray['value'][$optionId]   = $item->getValue();
                $optionArray['deleted'][$optionId] = $item->getValue();
            } else {
                $optionArray['values'][$optionId] = $item->getValue();
                $optionArray['attribute_code']    = $attributeCode;
            }
        }
        return $optionArray;
    }


    /**
     * @param string $attribute_code
     * @return int|null
     * @throws NoSuchEntityException
     */
    public function getAttributeIdbyCode($attribute_code = '')
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
                $optimizedArray[$k['value']] = $k['label'];
            }
        }
        return $optimizedArray;
    }

    /**
     * @param $option
     */
    public function addUpdateAttributeOption($option)
    {
        $eavFactory       = $this->eavSetupFactory->create();
        $optionTable      = $eavFactory->getSetup()->getTable('eav_attribute_option');
        $optionValueTable = $eavFactory->getSetup()->getTable('eav_attribute_option_value');

        if (isset($option['value'])) {
            foreach ($option['value'] as $optionId => $value) {
                $intOptionId = (int)$optionId;
                if (!empty($option['delete'][$optionId])) {
                    if ($intOptionId) {
                        $condition = ['option_id =?' => $intOptionId];
                        $eavFactory->getSetup()->getConnection()->delete($optionTable, $condition);
                    }
                }

                // for updating option values
                if (!empty($option['update'][$optionId])) {
                    $data = ['value' => $value];
                    $eavFactory->getSetup()->getConnection()->update(
                        $optionValueTable,
                        $data,
                        ['option_id=?' => $intOptionId]
                    );
                }
            }
        } elseif (isset($option['values'])) {
            foreach ($option['values'] as $intOptionId => $label) {
                // add option
                $data = [
                    'option_id' => $intOptionId, 'attribute_id' => $option['attribute_id']
                ];
                $eavFactory->getSetup()->getConnection()->insert($optionTable, $data);
                $data = ['option_id' => $intOptionId, 'store_id' => 0, 'value' => $label];
                $eavFactory->getSetup()->getConnection()->insert($optionValueTable, $data);
            }
        }
    }
}
