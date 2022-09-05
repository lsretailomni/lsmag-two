<?php

namespace Ls\Replication\Setup\Patch\Data;

use \Ls\Replication\Api\ReplExtendedVariantValueRepositoryInterface as ReplExtendedVariantValueRepository;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use \Ls\Replication\Model\ReplExtendedVariantValueSearchResults;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Migration script to update the existing product attributes value scope in order to support data translation
 */
class UpdateProductAttributesValueScope implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /** @var Logger */
    private $logger;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /**
     * @var ReplExtendedVariantValueRepository
     */
    private $replExtendedVariantValueRepository;

    /** @var ReplicationHelper */
    private $replicationHelper;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Logger $logger
     * @param AttributeRepositoryInterface $attributeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ReplExtendedVariantValueRepository $replExtendedVariantValueRepository
     * @param ReplicationHelper $replicationHelper
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Logger $logger,
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ReplExtendedVariantValueRepository $replExtendedVariantValueRepository,
        ReplicationHelper $replicationHelper
    ) {
        $this->moduleDataSetup                    = $moduleDataSetup;
        $this->logger                             = $logger;
        $this->attributeRepository                = $attributeRepository;
        $this->searchCriteriaBuilder              = $searchCriteriaBuilder;
        $this->replExtendedVariantValueRepository = $replExtendedVariantValueRepository;
        $this->replicationHelper                  = $replicationHelper;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->updateAttributes();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * Update attributes
     *
     * @return void
     */
    private function updateAttributes()
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('attribute_code', 'ls_%', 'like')
            ->addFilter('is_global', ScopedAttributeInterface::SCOPE_GLOBAL)
            ->create();

        try {
            $items = $this->attributeRepository
                ->getList('catalog_product', $searchCriteria)
                ->getItems();

            $attributesToExclude = array_unique($this->getAllConfigurableAttributes());

            foreach ($items as $item) {
                if (in_array($item->getAttributeCode(), $attributesToExclude)) {
                    continue;
                }
                $item->setData('is_global', ScopedAttributeInterface::SCOPE_STORE);
                $this->attributeRepository->save($item);
            }
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * Get all configurable Attributes
     *
     * @return array
     */
    public function getAllConfigurableAttributes()
    {
        $criteria = $this->replicationHelper->buildCriteriaForDirect([], -1);
        /** @var ReplExtendedVariantValueSearchResults $variants */
        $variantAttributes          = $this->replExtendedVariantValueRepository->getList($criteria);
        $configurableAttributeCodes = [];

        foreach ($variantAttributes->getItems() as $variantAttribute) {
            $configurableAttributeCodes[] = $this->replicationHelper->formatAttributeCode(
                $variantAttribute->getCode()
            );
        }

        return $configurableAttributeCodes;
    }
}
