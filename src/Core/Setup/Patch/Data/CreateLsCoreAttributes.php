<?php

namespace Ls\Core\Setup\Patch\Data;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Class CreateLsCoreAttributes
 * @package Ls\Core\Setup\Patch\Data
 */
class CreateLsCoreAttributes implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * CreateLsCoreAttributes constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     * @param AttributeSetFactory $attributeSetFactory
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup      = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory  = $attributeSetFactory;
        $this->eavSetupFactory      = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getVersion()
    {
        return '1.0.1';
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->createAllCustomerAttributes();
        $this->createAllCategoryAttributes();
        $this->createAllProductAttributes();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Trigger the install data function for Customer
     */
    private function createAllCustomerAttributes()
    {
        $customerSetup    = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $customerEntity   = $customerSetup->getEavConfig()->getEntityType('customer');
        $attributeSetId   = $customerEntity->getDefaultAttributeSetId();
        $attributeSet     = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);
        $customerSetup->addAttribute(Customer::ENTITY, 'lsr_username', [
            'type'               => 'varchar',
            'input'              => 'text',
            'label'              => 'Member Username',
            'unique'             => false,
            'visible'            => false,
            'visible_in_front'   => true,
            'required'           => false,
            'user_defined'       => false,
            'default'            => null,
            'attribute_set_id'   => $attributeSetId,
            'attribute_group_id' => $attributeGroupId
        ])->addAttribute(Customer::ENTITY, 'lsr_id', [
            'type'               => 'varchar',
            'input'              => 'text',
            'label'              => 'Member Id',
            'unique'             => false,
            'visible'            => false,
            'visible_in_front'   => true,
            'required'           => false,
            'user_defined'       => false,
            'default'            => null,
            'attribute_set_id'   => $attributeSetId,
            'attribute_group_id' => $attributeGroupId
        ])->addAttribute(Customer::ENTITY, 'lsr_token', [
            'type'               => 'varchar',
            'input'              => 'text',
            'label'              => 'Member Token',
            'unique'             => false,
            'visible'            => false,
            'visible_in_front'   => true,
            'required'           => false,
            'user_defined'       => false,
            'default'            => null,
            'attribute_set_id'   => $attributeSetId,
            'attribute_group_id' => $attributeGroupId
        ])->addAttribute(Customer::ENTITY, 'lsr_resetcode', [
            'type'               => 'varchar',
            'input'              => 'text',
            'label'              => 'Password Reset Code',
            'unique'             => false,
            'visible'            => false,
            'visible_in_front'   => true,
            'required'           => false,
            'user_defined'       => false,
            'default'            => null,
            'attribute_set_id'   => $attributeSetId,
            'attribute_group_id' => $attributeGroupId
        ])->addAttribute(Customer::ENTITY, 'lsr_cardid', [
            'type'               => 'varchar',
            'input'              => 'text',
            'label'              => 'LSR Card ID',
            'unique'             => false,
            'visible'            => false,
            'visible_in_front'   => true,
            'required'           => false,
            'user_defined'       => false,
            'default'            => null,
            'attribute_set_id'   => $attributeSetId,
            'attribute_group_id' => $attributeGroupId
        ]);
    }

    /**
     * Trigger the install data function for Category
     */
    private function createAllCategoryAttributes()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->addAttribute(
            Category::ENTITY,
            'nav_id',
            [
                'type'         => 'varchar',
                'label'        => 'Nav ID',
                'input'        => 'text',
                'sort_order'   => 4,
                'global'       => ScopedAttributeInterface::SCOPE_STORE,
                'visible'      => true,
                'required'     => false,
                'user_defined' => false,
                'default'      => null,
                'group'        => 'General Information',
            ]
        );
    }

    /**
     * Trigger the install data function for Product
     */
    private function createAllProductAttributes()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->addAttribute(
            Product::ENTITY,
            'barcode',
            [
                'type'         => 'varchar',
                'label'        => 'Barcode',
                'input'        => 'text',
                'sort_order'   => 4,
                'global'       => ScopedAttributeInterface::SCOPE_STORE,
                'visible'      => true,
                'required'     => false,
                'user_defined' => false,
                'default'      => null,
                'group'        => 'General Information',
            ]
        )->addAttribute(
            Product::ENTITY,
            'uom',
            [
                'type'         => 'varchar',
                'label'        => 'Unit of Measurement',
                'input'        => 'text',
                'sort_order'   => 4,
                'global'       => ScopedAttributeInterface::SCOPE_STORE,
                'visible'      => true,
                'required'     => false,
                'user_defined' => false,
                'default'      => null,
                'group'        => 'General Information',
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
