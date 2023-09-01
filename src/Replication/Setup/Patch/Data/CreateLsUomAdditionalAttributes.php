<?php

namespace Ls\Replication\Setup\Patch\Data;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity;
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
        $formattedCodes = [
            LSR::LS_UOM_ATTRIBUTE_HEIGHT => 'Unit Of Measure Height',
            LSR::LS_UOM_ATTRIBUTE_WEIGHT => 'Unit Of Measure Weight' ,
            LSR::LS_UOM_ATTRIBUTE_LENGTH => 'Unit Of Measure Length',
            LSR::LS_UOM_ATTRIBUTE_WIDTH  => 'Unit Of Measure Width',
            LSR::LS_UOM_ATTRIBUTE_CUBAGE => 'Unit Of Measure Cubage'
        ];

        foreach ($formattedCodes as $formattedCode => $formattedCodeLabel) {
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $formattedCode);

            $defaultAttributeSetId = $this->replicationHelper->getDefaultAttributeSetId();

            $defaultGroupId = $this->replicationHelper->getDefaultGroupIdOfAttributeSet($defaultAttributeSetId);

            if (!$attribute || !$attribute->getAttributeId()) {
                $attributeData = [
                    'frontend_label'                => [__($formattedCodeLabel)],
                    'frontend_input'                => 'text',
                    'backend_type'                  => 'varchar',
                    'is_required'                   => '0',
                    'attribute_code'                => $formattedCode,
                    'is_global'                     => '1',
                    'is_user_defined'               => 1,
                    'is_unique'                     => 0,
                    'is_searchable'                 => 1,
                    'is_comparable'                 => 1,
                    'is_filterable'                 => 0,
                    'is_visible_in_advanced_search' => 1,
                    'is_filterable_in_search'       => '0',
                    'is_used_for_promo_rules'       => '0',
                    'is_html_allowed_on_front'      => '1',
                    'used_in_product_listing'       => '1',
                    'used_for_sort_by'              => '1',
                    'attribute_set_id'              => $defaultAttributeSetId,
                    'attribute_group_id'            => $defaultGroupId,
                    'backend_model'                 => ArrayBackend::class,
                    'source_model'                  => Table::class,
                ];

                $this->eavAttributeFactory->create()
                    ->addData($attributeData)
                    ->setEntityTypeId($this->eavEntity->setType(Product::ENTITY)->getTypeId())
                    ->save();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
