<?php

namespace Ls\Core\Setup\Patch\Data;

use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class UpdateLsCoreCustomerAttributesMetadata
 * @package Ls\Core\Setup\Patch\Data
 */
class UpdateLsCoreCustomerAttributesMetadata implements DataPatchInterface
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
     * UpdateLsCoreCustomerAttributesMetadata constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory
    ) {
        $this->moduleDataSetup      = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [
            CreateLsCoreAttributes::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->updateCustomerAttributesMetadata();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * updateCustomerAttributesMetadata
     */
    private function updateCustomerAttributesMetadata()
    {
        $customerSetup    = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $entityAttributes = [
            'customer' => [
                'lsr_username' => [
                    'is_used_in_grid'       => true,
                    'is_visible_in_grid'    => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => true,
                ],
                'lsr_cardid'   => [
                    'is_used_in_grid'       => true,
                    'is_visible_in_grid'    => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => true,
                ]
            ]
        ];
        $customerSetup->upgradeAttributes($entityAttributes);
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
