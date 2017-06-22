<?php

namespace Ls\Customer\Setup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{

    /**
     * @var CustomerSetupFactory
     */
    protected $customerSetupFactory;

    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * @param CustomerSetupFactory $customerSetupFactory
     * @param AttributeSetFactory $attributeSetFactory
     */
    public function __construct(
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }


    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if(!$context->getVersion()) {

        }

        if (version_compare($context->getVersion(), '1.1.0') < 0) {
            // see LSR/Core/data/lsr_setup/data-install-0.1.0.php
            // from LSMag1 and the other files in the same folder for the attribute values


            // lsr_username, taken from Core/data/lsr_setup/data-upgrade-0.1.9-0.1.10.php

            /** @var CustomerSetup $customerSetup */
            $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);

            $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
            $attributeSetId = $customerEntity->getDefaultAttributeSetId();

            /** @var $attributeSet AttributeSet */
            $attributeSet = $this->attributeSetFactory->create();
            $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

            $customerSetup->addAttribute(Customer::ENTITY, 'lsr_id', [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'Member Id',
                'unique' => false,
                'visible' => false,
                'visible_in_front' => true,
                'required' => false,
                'user_defined' => false,
                'default' => NULL
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'lsr_id')
                ->addData([
                    'attribute_set_id' => $attributeSetId,
                    'attribute_group_id' => $attributeGroupId
                ]);

            $attribute->save();
        }

        if (version_compare($context->getVersion(), '1.2.0') < 0) {
            // see LSR/Core/data/lsr_setup/data-install-0.1.0.php
            // from LSMag1 and the other files in the same folder for the attribute values


            // lsr_username, taken from Core/data/lsr_setup/data-upgrade-0.1.9-0.1.10.php

            /** @var CustomerSetup $customerSetup */
            $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);

            $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
            $attributeSetId = $customerEntity->getDefaultAttributeSetId();

            /** @var $attributeSet AttributeSet */
            $attributeSet = $this->attributeSetFactory->create();
            $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

            $customerSetup->addAttribute(Customer::ENTITY, 'lsr_token', [
                'type' => 'varchar',
                'input' => 'text',
                'label' => 'Member Token',
                'unique' => false,
                'visible' => false,
                'visible_in_front' => true,
                'required' => false,
                'user_defined' => false,
                'default' => NULL
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'lsr_token')
                ->addData([
                    'attribute_set_id' => $attributeSetId,
                    'attribute_group_id' => $attributeGroupId
                ]);

            $attribute->save();
        }

        $setup->endSetup();

    }
}