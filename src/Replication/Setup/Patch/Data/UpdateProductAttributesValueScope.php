<?php

namespace Ls\Replication\Setup\Patch\Data;

use \Ls\Replication\Logger\Logger;
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
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Logger $logger
     * @param AttributeRepositoryInterface $attributeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Logger $logger,
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->moduleDataSetup     = $moduleDataSetup;
        $this->logger              = $logger;
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
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

            foreach ($items as $item) {
                $item->setData('is_global', ScopedAttributeInterface::SCOPE_STORE);
                $this->attributeRepository->save($item);
            }
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }
}
