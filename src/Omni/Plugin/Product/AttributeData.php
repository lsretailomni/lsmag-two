<?php

namespace Ls\Omni\Plugin\Product;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Api\ReplExtendedVariantValueRepositoryInterface as ReplExtendedVariantValueRepository;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Model\ReplExtendedVariantValue;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interceptor to intercept ConfigurableAttributeData methods
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

    /**
     * After plugin to change the order of options available for a configurable attribute
     *
     * @param ConfigurableAttributeData $subject
     * @param array $result
     * @param Product $product
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterGetAttributesData(
        ConfigurableAttributeData $subject,
        array $result,
        Product $product
    ) {
        $itemId  = $product->getSku();
        $storeId = $this->lsr->getCurrentStoreId();

        foreach ($result['attributes'] as $attributeId => $value) {
            $defaultScopedAttributeObject = $this->replicationHelper->getProductAttributeGivenCodeAndScope(
                $value['code']
            );

            $newOptionData                = [];
            $filters                      = [
                ['field' => 'scope_id', 'value' => $storeId, 'condition_type' => 'eq'],
                ['field' => 'ItemId', 'value' => $itemId, 'condition_type' => 'eq'],
                [
                    'field' => 'Code',
                    'value' => $defaultScopedAttributeObject->getDefaultFrontendLabel(),
                    'condition_type' => 'eq'
                ]
            ];
            $criteria                     = $this->replicationHelper->buildCriteriaForArrayFrontEnd($filters, -1);
            $sortOrder                    = $this
                ->sortOrderBuilder
                ->setField('LogicalOrder')
                ->setDirection('ASC')
                ->create();
            $criteria->setSortOrders([$sortOrder]);
            $variants = $this->replExtendedVariantValueRepository->getList($criteria);

            if ($variants->getTotalCount() > 0) {
                /** @var ReplExtendedVariantValue $variant */
                foreach ($variants->getItems() as $variant) {
                    if (!empty($defaultScopedAttributeObject->getId())) {
                        $optionId = $defaultScopedAttributeObject->getSource()->getOptionId($variant->getValue());

                        foreach ($value['options'] as $optionData) {
                            if ($optionId == $optionData['id']) {
                                $newOptionData[] = $optionData;
                                break;
                            }
                        }
                    }
                }
                $result['attributes'][$attributeId]['options'] = $newOptionData;
            }
        }

        return $result;
    }
}
