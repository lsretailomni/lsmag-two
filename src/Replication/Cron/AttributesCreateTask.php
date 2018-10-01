<?php

namespace Ls\Replication\Cron;

use Ls\Replication\Model\ReplExtendedVariantValueRepository;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Model\Entity;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Psr\Log\LoggerInterface;
use Ls\Replication\Helper\ReplicationHelper;


use Ls\Replication\Api\ReplAttributeRepositoryInterface;
use Ls\Replication\Api\ReplAttributeOptionValueRepositoryInterface;


class AttributesCreateTask
{

    /**
     * @var ReplExtendedVariantValueRepository
     */

    protected $replExtendedVariantValueRepository;
    /**
     * @var ProductAttributeRepositoryInterface
     */

    protected $productAttributeRepository;
    /**
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;


    /** @var ReplAttributeRepositoryInterface */
    protected $replAttributeRepositoryInterface;

    /** @var ReplAttributeOptionValueRepositoryInterface */
    protected $replAttributeOptionValueRepositoryInterface;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /** @var ReplicationHelper */
    protected $replicationHelper;


    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * AttributesCreateTask constructor.
     * @param ReplExtendedVariantValueRepository $replExtendedVariantValueRepository
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param EavSetupFactory $eavSetupFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LoggerInterface $logger
     * @param AttributeFactory $eavAttributeFactory
     * @param Entity $eav_entity
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
        \Magento\Eav\Model\Config $eavConfig,
        ReplicationHelper $replicationHelper

    )
    {
        $this->replExtendedVariantValueRepository = $replExtendedVariantValueRepository;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->logger = $logger;
        $this->_eavAttributeFactory = $eavAttributeFactory;
        $this->_eavEntity = $eav_entity;
        $this->replAttributeRepositoryInterface = $replAttributeRepositoryInterface;
        $this->replAttributeOptionValueRepositoryInterface = $replAttributeOptionValueRepositoryInterface;
        $this->eavConfig = $eavConfig;
        $this->replicationHelper = $replicationHelper;
    }


    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        // Process display only attributes which are going to be used for product specification
        $this->processAttributes();
        // Process variants attributes which are going to be used for configurable product
        $this->processVariantAttributes();
    }

    /**
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function processAttributes()
    {
        /*
         * Replicating data from ls_replication_repl_attribute table
         * Getting Data only for those which are not yet processed OR is modified.
         * Technical Structure :- where processed = 0 || is_updated = 1
         */

        $criteria = $this->replicationHelper->buildCriteriaForNewItems();

        /** @var \Ls\Replication\Model\ReplAttributeSearchResults $replAttributes */
        $replAttributes = $this->replAttributeRepositoryInterface->getList($criteria);

        /** @var defualt attribute set if for catalog_product $defaultAttributeSetId */
        $defaultAttributeSetId = $this->replicationHelper->getDefaultAttributeSetId();

        /** @var default group if of general tab for specific product attribute set $defaultGroupId */
        $defaultGroupId = $this->replicationHelper->getDefaultGroupIdOfAttributeSet($defaultAttributeSetId);

        /** @var \Ls\Replication\Model\ReplAttribute $replAttribute */
        foreach ($replAttributes->getItems() as $replAttribute) {
                $this->createAttributeByObject($replAttribute, $defaultAttributeSetId, $defaultGroupId);
                if ($replAttribute->getValueType() == '5' || $replAttribute->getValueType() == '7') {
                    $this->addAttributeOptions($replAttribute->getCode());
                }
                $replAttribute->setData('processed', '1');
                $replAttribute->save();
        }
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function processVariantAttributes()
    {
        $this->logger->debug("Running Varients create task...");
        /** @var default attribute set id for catalog_product $defaultAttributeSetId */
        $defaultAttributeSetId = $this->replicationHelper->getDefaultAttributeSetId();

        /** @var default group id of general tab for specific product attribute set $defaultGroupId */
        $defaultGroupId = $this->replicationHelper->getDefaultGroupIdOfAttributeSet($defaultAttributeSetId);

        $criteria = $this->replicationHelper->buildCriteriaForNewItems('', '', '', 1000);
        $variants = $this->replExtendedVariantValueRepository->getList($criteria)->getItems();
        $variantCodes = array();
        /** @var \Ls\Replication\Model\ReplExtendedVariantValue $variant */
        foreach ($variants as $variant) {
            try {
                $variant->setProcessed('1')->save();
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
            if (empty($variantCodes[$variant->getCode()]) || !in_array($variant->getValue(), $variantCodes[$variant->getCode()]))
                $variantCodes[$variant->getCode()][] = $variant->getValue();
        }
        foreach ($variantCodes as $code => $value) {
            $formattedCode = $this->replicationHelper->formatAttributeCode($code);
            $attribute = $this->eavConfig->getAttribute('catalog_product', $formattedCode);
            if (!$attribute || !$attribute->getAttributeId()) {
                $attributeData = [
                    'attribute_code' => $formattedCode,
                    'is_global' => 1,
                    'frontend_label' => ucwords(strtolower($code)),
                    'frontend_input' => 'select',
                    'default_value_text' => '',
                    'default_value_yesno' => 0,
                    'default_value_date' => '',
                    'default_value_textarea' => '',
                    'is_unique' => 0,
                    'apply_to' => 0,
                    'is_required' => 0,
                    'is_configurable' => 1,
                    'is_searchable' => 1,
                    'is_comparable' => 1,
                    'is_user_defined' => 1,
                    'is_visible_in_advanced_search' => 1,
                    'is_used_for_price_rules' => 0,
                    'is_wysiwyg_enabled' => 0,
                    'is_html_allowed_on_front' => 1,
                    'is_visible_on_front' => 1,
                    'used_in_product_listing' => 0,
                    'used_for_sort_by' => 1,
                    'is_filterable' => 1,
                    'is_filterable_in_search' => 1,
                    'backend_type' => 'varchar',
                    'is_filterable_in_search' => 1,
                    'used_in_product_listing' => 1,
                    'is_used_in_grid' => 1,
                    'is_visible_in_grid' => 1,
                    'is_filterable_in_grid' => 1,
                    'attribute_set_id' => $defaultAttributeSetId,
                    'attribute_group_id' => $defaultGroupId
                ];
                try {
                    $this->_eavAttributeFactory->create()
                        ->addData($attributeData)
                        ->setEntityTypeId($this->getEntityTypeId('catalog_product'))
                        ->save();
                } catch (\Exception $e) {
                    $this->logger->debug($e->getMessage());
                }
            }

            $existingOptions = $this->getOptimizedOptionArrayByAttributeCode($formattedCode);
            $newoptionsArray = array();
            if (empty($existingOptions)) {
                $this->eavSetupFactory->create()
                    ->addAttributeOption(
                        [
                            'values' => $value,
                            'attribute_id' => $this->getAttributeIdbyCode($formattedCode)
                        ]
                    );

            } elseif (!empty($value)) {
                foreach ($value as $k => $v) {
                    if (!in_array($v, $existingOptions)) {
                        $newoptionsArray[] = $v;
                    }
                }
                if (!empty($newoptionsArray)) {
                    $this->eavSetupFactory->create()
                        ->addAttributeOption(
                            [
                                'values' => $newoptionsArray,
                                'attribute_id' => $this->getAttributeIdbyCode($formattedCode)
                            ]
                        );
                }
            }
        }
    }

    /**
     * @param \Ls\Replication\Model\ReplAttribute $replAttribute
     * @param $attributeSetId
     * @param $attributeGroupId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function createAttributeByObject(\Ls\Replication\Model\ReplAttribute $replAttribute, $attributeSetId, $attributeGroupId)
    {
        $formattedCode = $this->replicationHelper->formatAttributeCode($replAttribute->getCode());
        /** @var \Magento\Eav\Api\Data\AttributeInterface $attribute */
        $attribute = $this->eavConfig->getAttribute('catalog_product', $formattedCode);
        if (!$attribute || !$attribute->getAttributeId()) {
            $valueTypeArray = $this->getValueTypeArray();
            $attributeData = [
                'attribute_code' => $formattedCode,
                'is_global' => 1,
                'frontend_label' => $replAttribute->getDescription(),
                'frontend_input' => $valueTypeArray[$replAttribute->getValueType()],
                'is_unique' => 0,
                'apply_to' => 0,
                'is_required' => 0,
                'is_configurable' => 0,
                'is_searchable' => 1,
                'is_comparable' => 1,
                'is_user_defined' => 1,
                'is_visible_in_advanced_search' => 1,
                'is_used_for_price_rules' => 0,
                'is_wysiwyg_enabled' => 0,
                'is_html_allowed_on_front' => 1,
                'is_visible_on_front' => 1,
                'used_in_product_listing' => 0,
                'used_for_sort_by' => 1,
                'backend_type' => 'varchar',
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId
            ];

            try {
                $this->_eavAttributeFactory->create()
                    ->addData($attributeData)
                    ->setEntityTypeId($this->getEntityTypeId('catalog_product'))
                    ->save();
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
            $this->logger->debug("Successfully created attribute object for " . $formattedCode);
        }
    }

    /**
     * @param string
     * @return int
     */
    protected function getEntityTypeId($type = 'catalog_product')
    {
        return $this->_eavEntity->setType($type)->getTypeId();
    }

    /**
     * @param string $attribute_code
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function addAttributeOptions($attribute_code = '')
    {
        $option_data = $this->generateOptionValues($attribute_code);
        if (!empty($option_data)) {
            $formattedCode = $this->replicationHelper->formatAttributeCode($attribute_code);
            $this->eavSetupFactory->create()
                ->addAttributeOption(
                    [
                        'values' => $option_data,
                        'attribute_id' => $this->getAttributeIdbyCode($formattedCode)
                    ]
                );
        }

    }

    /**
     * @param string $attribute_code
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function generateOptionValues($attribute_code = '')
    {
        $optionarray = array();
        $criteria = $this->replicationHelper->buildCriteriaForNewItems('Code', $attribute_code, 'eq');
        /** @var \Ls\Replication\Model\ReplAttributeOptionValueSearchResults $replAttributeOptionValues */
        $replAttributeOptionValues = $this->replAttributeOptionValueRepositoryInterface->getList($criteria);

        // get existing option array
        $existingOptions = $this->getOptimizedOptionArrayByAttributeCode($attribute_code);

        /** @var \Ls\Replication\Model\ReplAttributeOptionValue $item */
        foreach ($replAttributeOptionValues->getItems() as $item) {
            $item->setProcessed('1');
            $item->save();
            // if have existing option and current value is a part of existing option then don't do anything
            if (!empty($existingOptions) and in_array($item->getValue(), $existingOptions)) {
                continue;
            }
            $optionarray[] = $item->getValue();
        }
        return $optionarray;
    }

    /**
     * @param string $attribute_code
     * @return int|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getAttributeIdbyCode($attribute_code = '')
    {
        return $this->productAttributeRepository->get($attribute_code)->getAttributeId();
    }

    /**
     * @return array
     */
    protected function getValueTypeArray()
    {
        return array(
            '0' => 'text',
            '1' => 'text',
            '2' => 'price',
            '3' => 'date',
            '4' => 'text',
            '5' => 'select',
            '6' => 'text',
            '7' => 'select',
            '100' => 'text'
        );
    }

    /**
     * @param string $attribute_code
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getOptimizedOptionArrayByAttributeCode($attribute_code = '')
    {
        $optimziedArray = array();
        if ($attribute_code == '' || is_null($attribute_code)) {
            return $optimziedArray;
        }
        $existingOptions = $this->eavConfig->getAttribute('catalog_product', $attribute_code)
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
