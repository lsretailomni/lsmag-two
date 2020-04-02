<?php

namespace Ls\Customer\Setup;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Zend_Validate_Exception;

/**
 * Class InstallData
 * @package Ls\Customer\Setup
 */
class InstallData implements InstallDataInterface
{

    /**
     * @var CustomerSetupFactory
     */
    public $customerSetupFactory;

    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @param CustomerSetupFactory $customerSetupFactory
     * @param AttributeSetFactory $attributeSetFactory
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory  = $attributeSetFactory;
        $this->eavSetupFactory      = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {

        $setup->startSetup();
        /*
         * Trigger the install data function for Customer
         */
        $this->createAllCustomerAttributes($setup, $context);
        /*
         * Trigger the install data function for Category
         */
        $this->createAllCategoryAttributes($setup, $context);
        /*
         * Trigger the install data function for Product
         */
        $this->createAllProductAttributes($setup, $context);
        $setup->endSetup();
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    protected function createAllCustomerAttributes(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        /** @var CustomerSetup $customerSetup */
        $customerSetup  = $this->customerSetupFactory->create(['setup' => $setup]);
        $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();
        /** @var $attributeSet AttributeSet */
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
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws LocalizedException
     * @throws Zend_Validate_Exception
     */
    protected function createAllCategoryAttributes(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
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
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws LocalizedException
     * @throws Zend_Validate_Exception
     */
    protected function createAllProductAttributes(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
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
}
