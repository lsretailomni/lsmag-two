<?php

namespace Ls\Core\Setup\Patch\Data;

use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Class UpdateLsCoreCustomerAttributesMetadata
 * @package Ls\Core\Setup\Patch\Data
 */
class UpdateLsCoreCustomerAttributesMetadata implements DataPatchInterface, PatchVersionInterface
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
    public function apply()
    {
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $this->updateCustomerAttributesMetadata($customerSetup);
    }

    /**
     * @param CustomerSetup $customerSetup
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function updateCustomerAttributesMetadata($customerSetup)
    {
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
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '1.0.2';
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
