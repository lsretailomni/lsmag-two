<?php

namespace Ls\Omni\Plugin\Product;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Api\ReplExtendedVariantValueRepositoryInterface as ReplExtendedVariantValueRepository;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\Api\SortOrderBuilder;

/**
 * Class AttributeData
 * @package Magento\ConfigurableProduct\Helper
 */
class AttributeData
{

    /**
     * @var ReplExtendedVariantValueRepository
     */
    public $replExtendedVariantValueRepository;

    /**
     * @var ReplicationHelper
     */
    public $replicationHelper;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var SortOrderBuilder
     */
    public $sortOrderBuilder;


    /**
     * AttributeData constructor.
     * @param ReplExtendedVariantValueRepository $replExtendedVariantValueRepository
     * @param ReplicationHelper $replicationHelper
     * @param LSR $lsr
     * @param SortOrderBuilder $sortOrderBuilder
     */
    public function __construct(
        ReplExtendedVariantValueRepository $replExtendedVariantValueRepository,
        ReplicationHelper $replicationHelper,
        LSR $lsr,
        SortOrderBuilder $sortOrderBuilder
    ) {
        $this->replicationHelper                  = $replicationHelper;
        $this->replExtendedVariantValueRepository = $replExtendedVariantValueRepository;
        $this->lsr                                = $lsr;
        $this->sortOrderBuilder                   = $sortOrderBuilder;
    }

    public function afterGetAttributesData($subject, $optionsData, $product, $array)
    {
        $itemId  = $product->getSku();
        $storeId = $this->lsr->getCurrentStoreId();
        foreach ($optionsData['attributes'] as $attributeId => $value) {
            $newOptionData = [];
            $attributeCode = $this->replicationHelper->changeAttributeCodeFormat($value['code']);
            $filters       = [];
            $filters[]     = ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq'];
            $filters[]     = ['field' => 'ItemId', 'value' => $itemId, 'condition_type' => 'eq'];
            $filters[]     = ['field' => 'Code', 'value' => $attributeCode, 'condition_type' => 'eq'];
            $criteria      = $this->replicationHelper->buildCriteriaForArrayFrontEnd($filters, -1);
            $sortOrder     = $this->sortOrderBuilder->setField('LogicalOrder')->setDirection('ASC')->create();
            $criteria->setSortOrders([$sortOrder]);
            /** @var ReplExtendedVariantValueSearchResults $variants */
            $variants = $this->replExtendedVariantValueRepository->getList($criteria);
            if ($variants->getTotalCount() > 0) {
                /** @var ReplExtendedVariantValue $variant */
                foreach ($variants->getItems() as $variant) {
                    foreach ($value['options'] as $optionData) {
                        if ($variant->getValue() == $optionData['label']) {
                            $newOptionData[] = $optionData;
                        }
                    }
                }
            }
            $optionsData['attributes'][$attributeId]['options'] = $newOptionData;
        }
        return $optionsData;
    }
}
