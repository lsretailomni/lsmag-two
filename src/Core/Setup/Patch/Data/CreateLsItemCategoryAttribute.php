<?php

namespace Ls\Core\Setup\Patch\Data;

use \Ls\Core\Model\LSR;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Validator\ValidateException;
use Psr\Log\LoggerInterface;

/**
 * Data patch to item category attribute
 */
class CreateLsItemCategoryAttribute implements DataPatchInterface
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->logger          = $logger;
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
        return [];
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

        try {
            $this->createRequiredProductAttribute();
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * Trigger the install data function for Product
     *
     * @return void
     * @throws LocalizedException
     * @throws ValidateException
     */
    private function createRequiredProductAttribute()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->addAttribute(
            Product::ENTITY,
            LSR::LS_ITEM_CATEGORY,
            [
                'type'                    => 'varchar',
                'label'                   => LSR::LS_ITEM_CATEGORY_LABEL,
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
                'used_for_promo_rules'    => true,
                'is_used_in_grid'         => true,
                'is_visible_in_grid'      => true,
                'is_filterable_in_grid'   => true,
                'group'                   => 'General Information'
            ]
        );
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
}
