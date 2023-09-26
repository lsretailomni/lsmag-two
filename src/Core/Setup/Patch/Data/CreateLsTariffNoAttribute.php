<?php

namespace Ls\Core\Setup\Patch\Data;

use \Ls\Core\Model\LSR;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Create LS Tariff No Attribute
 */
class CreateLsTariffNoAttribute implements DataPatchInterface
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
     * CreateLsVendorAttributes constructor.
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
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->createAllProductAttributes();
        $this->moduleDataSetup->getConnection()->endSetup();
    }


    /**
     * Trigger the install data function for Product
     */
    private function createAllProductAttributes()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->addAttribute(
            Product::ENTITY,
            LSR::LS_TARIFF_NO_ATTRIBUTE_CODE,
            [
                'type'                    => 'varchar',
                'label'                   => LSR::LS_TARIFF_NO_ATTRIBUTE_LABEL,
                'input'                   => 'text',
                'sort_order'              => 5,
                'global'                  => ScopedAttributeInterface::SCOPE_STORE,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'searchable'              => true,
                'filterable'              => false,
                'visible_on_front'        => true,
                'used_in_product_listing' => true,
                'default'                 => null,
                'group'                   => 'General Information',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
