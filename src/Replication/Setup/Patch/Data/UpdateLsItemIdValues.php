<?php

namespace Ls\Replication\Setup\Patch\Data;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Core\Setup\Patch\Data\CreateLsItemIdAttribute;
use \Ls\Replication\Logger\Logger;
use \Ls\Replication\Model\ResourceModel\ReplItem\Collection;
use \Ls\Replication\Model\ResourceModel\ReplItem\CollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Migration script to update the newly created lsr_item_id
 */
class UpdateLsItemIdValues implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var Logger */
    private $logger;

    /** @var Product */
    private $productResourceModel;

    /**
     * @var CollectionFactory
     */
    private $replItemCollectionFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ProductRepositoryInterface $productRepository
     * @param Logger $logger
     * @param Product $productResourceModel
     * @param CollectionFactory $replItemCollectionFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ProductRepositoryInterface $productRepository,
        Logger $logger,
        Product $productResourceModel,
        CollectionFactory $replItemCollectionFactory
    ) {
        $this->moduleDataSetup           = $moduleDataSetup;
        $this->productRepository         = $productRepository;
        $this->logger                    = $logger;
        $this->productResourceModel      = $productResourceModel;
        $this->replItemCollectionFactory = $replItemCollectionFactory;
    }

    /**
     * Get array of patches that have to be executed prior to this.
     *
     * Example of implementation:
     *
     * [
     *      \Vendor_Name\Module_Name\Setup\Patch\Patch1::class,
     *      \Vendor_Name\Module_Name\Setup\Patch\Patch2::class
     * ]
     *
     * @return string[]
     */
    public static function getDependencies()
    {
        return [CreateLsItemIdAttribute::class];
    }

    /**
     * Run code inside patch
     * If code fails, patch must be reverted, in case when we are speaking about schema - then under revert
     * means run PatchInterface::revert()
     *
     * If we speak about data, under revert means: $transaction->rollback()
     *
     * @return $this
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->updateAttributeValues();
        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * Get aliases (previous names) for the patch.
     *
     * @return string[]
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * Update attribute values
     *
     * @return void
     */
    private function updateAttributeValues()
    {
        $items = $this->getAllReplItems();

        foreach ($items as $value) {
            $itemId = $value->getNavId();

            try {
                $productData = $this->productRepository->get($itemId, true, 0);
                $this->setCustomAttributeAndSaveProduct($productData, $itemId);

                if ($productData->getTypeId() == 'configurable') {
                    $children = $productData
                        ->getTypeInstance()
                        ->getUsedProducts($productData);

                    foreach ($children as $child) {
                        $this->setCustomAttributeAndSaveProduct($child, $itemId);
                    }
                }
            } catch (NoSuchEntityException $e) {
                $this->logger->debug($e->getMessage());
                continue;
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
    }

    /**
     * Getting all the variants available
     *
     * @return Collection
     */
    private function getAllReplItems()
    {
        $replItemCollection = $this->replItemCollectionFactory->create();
        $replItemCollection
            ->addFieldToFilter('IsDeleted', 0)
            ->addFieldToFilter('nav_id', ['neq' => 'NULL']);
        $replItemCollection->getSelect()->group('main_table.nav_id');

        return $replItemCollection;
    }

    /**
     * Set custom attribute and save product
     *
     * @param mixed $productData
     * @param string $itemId
     * @return void
     * @throws Exception
     */
    private function setCustomAttributeAndSaveProduct($productData, $itemId)
    {
        $productData->setCustomAttribute(
            LSR::LS_ITEM_ID_ATTRIBUTE_CODE,
            $itemId
        );
        $this->productResourceModel->saveAttribute(
            $productData,
            LSR::LS_ITEM_ID_ATTRIBUTE_CODE
        );
    }
}
