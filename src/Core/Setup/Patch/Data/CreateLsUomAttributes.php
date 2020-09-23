<?php

namespace Ls\Core\Setup\Patch\Data;

use Ls\Core\Model\LSR;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class CreateLsUomAttributes
 * @package Ls\Core\Setup\Patch\Data
 */
class CreateLsUomAttributes implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * CreateLsUomAttributes constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param AttributeSetFactory $attributeSetFactory
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        AttributeSetFactory $attributeSetFactory,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup     = $moduleDataSetup;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->eavSetupFactory     = $eavSetupFactory;
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
        $this->createUomAttributes();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Trigger the install data function for Product
     */
    private function createUomAttributes()
    {
        $uomCode    = LSR::LS_UOM_ATTRIBUTE;
        $uomQtyCode = LSR::LS_UOM_ATTRIBUTE_QTY;
        $eavSetup   = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->addAttribute(
            Product::ENTITY,
            $uomCode,
            [
                'label'        => 'Unit Of Measure',
                'type'         => 'int',
                'required'     => '0',
                'global'       => ScopedAttributeInterface::SCOPE_GLOBAL,
                'user_defined' => 1,
                'is_unique'    => 0,
                'searchable'   => 1,
                'filterable'   => 1,
                'configurable' => 1,
                'comparable'   => 1,
                'input'        => 'swatch_text'
            ]
        )->addAttribute(
            Product::ENTITY,
            $uomQtyCode,
            [
                'label'                   => 'Quantity Unit Of Measure',
                'input'                   => 'text',
                'type'                    => 'varchar',
                'required'                => '0',
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'user_defined'            => 1,
                'unique'                  => 0,
                'used_in_product_listing' => 1
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
