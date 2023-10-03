<?php

namespace Ls\Core\Setup\Patch\Data;

use \Ls\Core\Model\LSR;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateLsCoreProductAttributesMetadata implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [
            CreateLsCoreAttributes::class,
            CreateLsItemIdAttribute::class,
            CreateLsVariantIdAttribute::class
        ];
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->updateProductAttributesMetadata();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Upgrade product attributes meta data
     */
    private function updateProductAttributesMetadata()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->updateAttribute(
            Product::ENTITY,
            LSR::LS_ITEM_ID_ATTRIBUTE_CODE,
            [
                'used_in_product_listing' => true,
            ]
        );
        $eavSetup->updateAttribute(
            Product::ENTITY,
            LSR::LS_VARIANT_ID_ATTRIBUTE_CODE,
            [
                'used_in_product_listing' => true,
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
