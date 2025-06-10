<?php

namespace Ls\Core\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

class CreateAdditionalLsCoreCustomerAttributes implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     * @param AttributeSetFactory $attributeSetFactory
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private CustomerSetupFactory $customerSetupFactory,
        private AttributeSetFactory $attributeSetFactory
    ) {
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
        return [CreateLsCoreAttributes::class];
    }

    /**
     * This version associate patch with Magento setup version.
     * For example, if Magento current setup version is 2.0.3 and patch version is 2.0.2 then
     * this patch will be added to registry, but will not be applied, because it is already applied
     * by old mechanism of UpgradeData.php script
     *
     * @return string
     */
    public static function getVersion()
    {
        return '1.0.1';
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
        $this->createAllCustomerAttributes();
        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
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
        $customerSetup->addAttribute(Customer::ENTITY, 'lsr_account_id', [
            'type'               => 'varchar',
            'input'              => 'text',
            'label'              => 'Account Id',
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
     * Get aliases (previous names) for the patch.
     *
     * @return string[]
     */
    public function getAliases()
    {
        return [];
    }
}
