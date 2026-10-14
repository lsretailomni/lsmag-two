<?php

namespace Ls\Replication\Setup\Patch\Data;

use \Ls\Core\Model\LSR;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Create LS Vendor Sourcing and Prepayment product attributes
 */
class CreateLsVendorSourcingPrepaymentAttributes implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->createAllProductAttributes();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Create the product attributes
     *
     * @return void
     */
    private function createAllProductAttributes()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->addAttribute(
            Product::ENTITY,
            LSR::LS_VENDOR_SOURCING_ATTRIBUTE_CODE,
            [
                'type'                    => 'int',
                'label'                   => LSR::LS_VENDOR_SOURCING_ATTRIBUTE_LABEL,
                'input'                   => 'boolean',
                'source'                  => Boolean::class,
                'sort_order'              => 6,
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'searchable'              => false,
                'filterable'              => false,
                'visible_on_front'        => true,
                'used_in_product_listing' => true,
                'default'                 => '0',
                'group'                   => 'General Information',
            ]
        );
        $eavSetup->addAttribute(
            Product::ENTITY,
            LSR::LS_PREPAYMENT_ATTRIBUTE_CODE,
            [
                'type'                    => 'decimal',
                'label'                   => LSR::LS_PREPAYMENT_ATTRIBUTE_LABEL,
                'input'                   => 'text',
                'sort_order'              => 7,
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'searchable'              => false,
                'filterable'              => false,
                'visible_on_front'        => true,
                'used_in_product_listing' => true,
                'default'                 => null,
                'group'                   => 'General Information',
            ]
        );
        $this->assignToDefaultAttributeSet($eavSetup);
    }

    /**
     * Assign the attributes to the default product attribute set
     *
     * @param EavSetup $eavSetup
     * @return void
     */
    private function assignToDefaultAttributeSet(EavSetup $eavSetup)
    {
        $attributeSetId = $eavSetup->getDefaultAttributeSetId(Product::ENTITY);
        $groupName      = 'General Information';
        $eavSetup->addAttributeGroup(Product::ENTITY, $attributeSetId, $groupName);
        $sortOrder = 6;
        foreach ([LSR::LS_VENDOR_SOURCING_ATTRIBUTE_CODE, LSR::LS_PREPAYMENT_ATTRIBUTE_CODE] as $attributeCode) {
            $eavSetup->addAttributeToSet(
                Product::ENTITY,
                $attributeSetId,
                $groupName,
                $attributeCode,
                $sortOrder++
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }
}
