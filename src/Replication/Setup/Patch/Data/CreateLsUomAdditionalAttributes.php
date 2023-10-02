<?php

namespace Ls\Replication\Setup\Patch\Data;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Model\Entity\Attribute\Source\Table;

/**
 * Class CreateLsUomAttributes
 * @package Ls\Replication\Setup\Patch\Data
 */
class CreateLsUomAdditionalAttributes implements DataPatchInterface
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

    private $replicationHelper;

    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @var AttributeFactory
     */
    private $eavAttributeFactory;

    /**
     * @var Entity
     */
    private $eavEntity;

    /**
     * CreateLsUomAttributes constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param AttributeSetFactory $attributeSetFactory
     * @param EavSetupFactory $eavSetupFactory
     * @param ReplicationHelper $replicationHelper
     * @param Config $eavConfig
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        AttributeSetFactory $attributeSetFactory,
        EavSetupFactory $eavSetupFactory,
        AttributeFactory $eavAttributeFactory,
        ReplicationHelper $replicationHelper,
        Config $eavConfig,
        Entity $eavEntity
    ) {
        $this->moduleDataSetup     = $moduleDataSetup;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->eavSetupFactory     = $eavSetupFactory;
        $this->replicationHelper   = $replicationHelper;
        $this->eavConfig           = $eavConfig;
        $this->eavAttributeFactory = $eavAttributeFactory;
        $this->eavEntity           = $eavEntity;
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->createUomAdditionalAttributes();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createUomAdditionalAttributes()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->addAttribute(
            Product::ENTITY,
            LSR::LS_UOM_ATTRIBUTE_HEIGHT,
            [
                'type'                    => 'varchar',
                'label'                   => 'Height',
                'input'                   => 'text',
                'sort_order'              => 9,
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'searchable'              => false,
                'filterable'              => false,
                'visible_on_front'        => false,
                'used_in_product_listing' => false,
                'default'                 => null,
                'group'                   => 'General Information',
            ]
        )->addAttribute(
            Product::ENTITY,
            LSR::LS_UOM_ATTRIBUTE_LENGTH,
            [
                'type'                    => 'varchar',
                'label'                   => 'Length',
                'input'                   => 'text',
                'sort_order'              => 10,
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'searchable'              => false,
                'filterable'              => false,
                'visible_on_front'        => false,
                'used_in_product_listing' => false,
                'default'                 => null,
                'group'                   => 'General Information',
            ]
        )->addAttribute(
            Product::ENTITY,
            LSR::LS_UOM_ATTRIBUTE_WIDTH,
            [
                'type'                    => 'varchar',
                'label'                   => 'Width',
                'input'                   => 'text',
                'sort_order'              => 11,
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'searchable'              => false,
                'filterable'              => false,
                'visible_on_front'        => false,
                'used_in_product_listing' => false,
                'default'                 => null,
                'group'                   => 'General Information',
            ]
        )->addAttribute(
            Product::ENTITY,
            LSR::LS_UOM_ATTRIBUTE_CUBAGE,
            [
                'type'                    => 'varchar',
                'label'                   => 'Cubage',
                'input'                   => 'text',
                'sort_order'              => 12,
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'searchable'              => false,
                'filterable'              => false,
                'visible_on_front'        => false,
                'used_in_product_listing' => false,
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
