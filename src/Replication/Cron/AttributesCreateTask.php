<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Api\ReplAttributeOptionValueRepositoryInterface;
use \Ls\Replication\Api\ReplAttributeRepositoryInterface;
use \Ls\Replication\Api\ReplExtendedVariantValueRepositoryInterface as ReplExtendedVariantValueRepository;
use \Ls\Replication\Helper\ReplicationHelper;
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
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
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
     * @param LoggerInterface $logger
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
        LoggerInterface $logger,
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
        $this->replicationHelper->updateConfigValue(date('d M,Y h:i:s A'), self::CONFIG_PATH_LAST_EXECUTE);
        // Process display only attributes which are going to be used for product specification
        $this->processAttributes();
        // Process variants attributes which are going to be used for configurable product
        $this->processVariantAttributes();
        $this->replicationHelper->updateCronStatus($this->successCronAttribute, LSR::SC_SUCCESS_CRON_ATTRIBUTE);
        $this->replicationHelper->updateCronStatus(
            $this->successCronAttributeVariant,
            LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT
        );
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
        /*
         * Replicating data from ls_replication_repl_attribute table
         * Getting Data only for those which are not yet processed OR is modified.
         * Technical Structure :- where processed = 0 || is_updated = 1
         */
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
                $replAttribute->setData('processed', '1');
                $replAttribute->setData('is_updated', '0');
                // @codingStandardsIgnoreStart
                $this->replAttributeRepositoryInterface->save($replAttribute);
                // @codingStandardsIgnoreEnd
            }
            $attributesRemovalCounter = $this->caterAttributesRemoval();
            if (count($replAttributes->getItems()) == 0 && $attributesRemovalCounter == 0) {
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
        $criteria = $this->replicationHelper->buildCriteriaGetDeletedOnly([], -1);
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
                $replAttribute->setData('processed', '1');
                $replAttribute->setData('IsDeleted', '0');
                $replAttribute->setData('is_updated', '0');
                // @codingStandardsIgnoreStart
                $this->replAttributeRepositoryInterface->save($replAttribute);
                // @codingStandardsIgnoreEnd
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
        return count($replAttributes->getItems());
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function processVariantAttributes()
    {
        $variantBatchSize = $this->replicationHelper->getVariantBatchSize();
        $this->logger->debug('Running Varients create task...');
        /** @var default attribute set id for catalog_product $defaultAttributeSetId */
        $defaultAttributeSetId = $this->replicationHelper->getDefaultAttributeSetId();

        /** @var default group id of general tab for specific product attribute set $defaultGroupId */
        $defaultGroupId = $this->replicationHelper->getDefaultGroupIdOfAttributeSet($defaultAttributeSetId);

        $criteria     = $this->replicationHelper->buildCriteriaForNewItems('', '', '', $variantBatchSize, 1);
        $variants     = $this->replExtendedVariantValueRepository->getList($criteria)->getItems();
        $variantCodes = [];
        /** @var ReplExtendedVariantValue $variant */
        foreach ($variants as $variant) {
            $variant->setData('processed', '1');
            $variant->setData('is_updated', '0');
            // @codingStandardsIgnoreStart
            $this->replExtendedVariantValueRepository->save($variant);
            // @codingStandardsIgnoreEnd
            if (empty($variantCodes[$variant->getCode()]) ||
                !in_array($variant->getValue(), $variantCodes[$variant->getCode()], true)
            ) {
                $variantCodes[$variant->getCode()][] = $variant->getValue();
            }
        }
        if (count($variants) == 0) {
            $this->successCronAttributeVariant = true;
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

            $existingOptions = $this->getOptimizedOptionArrayByAttributeCode($formattedCode);
            $newoptionsArray = [];
            if (empty($existingOptions)) {
                sort($value);
                $this->eavSetupFactory->create()
                    ->addAttributeOption(
                        [
                            'values'       => $value,
                            'attribute_id' => $this->getAttributeIdbyCode($formattedCode)
                        ]
                    );
            } elseif (!empty($value)) {
                foreach ($value as $k => $v) {
                    if (!in_array($v, $existingOptions, true)) {
                        $newoptionsArray[] = $v;
                    }
                }
                if (!empty($newoptionsArray)) {
                    $this->eavSetupFactory->create()
                        ->addAttributeOption(
                            [
                                'values'       => $newoptionsArray,
                                'attribute_id' => $this->getAttributeIdbyCode($formattedCode)
                            ]
                        );
                    $this->updateOptions($formattedCode);
                }
            }
        }
    }

    /**
     * @param $formattedCode
     * update the order of the options in ascending order
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
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
            }
            $this->logger->debug('Successfully created attribute object for ' . $formattedCode);
        }
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
            $formattedCode = $this->replicationHelper->formatAttributeCode($attribute_code);
            $this->eavSetupFactory->create()
                ->addAttributeOption(
                    [
                        'values'       => $option_data,
                        'attribute_id' => $this->getAttributeIdbyCode($formattedCode)
                    ]
                );
        }
    }

    /**
     * @param string $attribute_code
     * @return array
     * @throws LocalizedException
     */
    public function generateOptionValues($attribute_code = '')
    {
        $optionarray = [];
        $criteria    = $this->replicationHelper->buildCriteriaForNewItems('Code', $attribute_code, 'eq', -1, 1);
        /** @var ReplAttributeOptionValueSearchResults $replAttributeOptionValues */
        $replAttributeOptionValues = $this->replAttributeOptionValueRepositoryInterface->getList($criteria);

        // get existing option array
        $existingOptions = $this->getOptimizedOptionArrayByAttributeCode($attribute_code);

        /** @var ReplAttributeOptionValue $item */
        foreach ($replAttributeOptionValues->getItems() as $item) {
            $item->setProcessed('1');
            // @codingStandardsIgnoreStart
            $this->replAttributeOptionValueRepositoryInterface->save($item);
            // @codingStandardsIgnoreEnd
            // if have existing option and current value is a part of existing option then don't do anything
            if (!empty($existingOptions) and in_array($item->getValue(), $existingOptions, true)) {
                continue;
            }
            $optionarray[] = $item->getValue();
        }
        return $optionarray;
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
        $optimziedArray = [];
        if ($attribute_code == '' || $attribute_code == null) {
            return $optimziedArray;
        }
        $existingOptions = $this->eavConfig->getAttribute(Product::ENTITY, $attribute_code)
            ->getSource()
            ->getAllOptions();
        if (!empty($existingOptions)) {
            foreach ($existingOptions as $k) {
                $optimziedArray[] = $k['label'];
            }
        }
        return $optimziedArray;
    }
}
