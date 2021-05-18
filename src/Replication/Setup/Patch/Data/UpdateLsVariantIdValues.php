<?php

namespace Ls\Replication\Setup\Patch\Data;

use \Ls\Core\Model\LSR;
use \Ls\Core\Setup\Patch\Data\CreateLsVariantIdAttribute;
use \Ls\Replication\Api\ReplItemVariantRegistrationRepositoryInterface as ReplItemVariantRegistrationRepository;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use \Ls\Replication\Model\ReplItemVariantRegistration;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Migration script to update the newly created ls_variant_id
 */
class UpdateLsVariantIdValues implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /** @var ReplicationHelper */
    private $replicationHelper;

    /** @var ReplItemVariantRegistrationRepository */
    private $replItemVariantRegistrationRepository;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var Logger */
    private $logger;

    /** @var Product */
    private $productResourceModel;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ReplicationHelper $replicationHelper
     * @param ReplItemVariantRegistrationRepository $replItemVariantRegistrationRepository
     * @param ProductRepositoryInterface $productRepository
     * @param Logger $logger
     * @param Product $productResourceModel
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ReplicationHelper $replicationHelper,
        ReplItemVariantRegistrationRepository $replItemVariantRegistrationRepository,
        ProductRepositoryInterface $productRepository,
        Logger $logger,
        Product $productResourceModel
    ) {
        $this->moduleDataSetup                       = $moduleDataSetup;
        $this->replicationHelper                     = $replicationHelper;
        $this->replItemVariantRegistrationRepository = $replItemVariantRegistrationRepository;
        $this->productRepository                     = $productRepository;
        $this->logger                                = $logger;
        $this->productResourceModel                  = $productResourceModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [CreateLsVariantIdAttribute::class];
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->updateAttributeValues();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    private function updateAttributeValues()
    {
        $variants = $this->getAllVariants();

        if (!empty($variants)) {
            /** @var ReplItemVariantRegistration $value */
            foreach ($variants as $value) {
                $itemId = $value->getItemId();

                try {
                    if ($itemId == '43140') {
                        $x = 1;
                    }
                    $productData             = $this->productRepository->get($itemId);
                    $associatedSimpleProduct = $this->replicationHelper->getRelatedVariantGivenConfAttributesValues(
                        $productData,
                        $value,
                        $value->getScopeId()
                    );

                    foreach ($associatedSimpleProduct as $variant) {
                        $variant->setCustomAttribute(
                            LSR::LS_VARIANT_ID_ATTRIBUTE_CODE,
                            $value->getVariantId()
                        );
                        $this->productResourceModel->saveAttribute(
                            $variant,
                            LSR::LS_VARIANT_ID_ATTRIBUTE_CODE
                        );
                    }
                } catch (NoSuchEntityException $e) {
                    $this->logger->debug($e->getMessage());
                    continue;
                } catch (\Exception $e) {
                    $this->logger->debug($e->getMessage());
                }
            }

        }
    }

    /**
     * Getting all the variants available
     *
     * @return mixed
     */
    private function getAllVariants()
    {
        $filters  = [
            ['field' => 'VariantId', 'value' => true, 'condition_type' => 'notnull'],
            ['field' => 'ItemId', 'value' => true, 'condition_type' => 'notnull']
        ];
        $criteria = $this->replicationHelper->buildCriteriaForDirect($filters, -1);

        return $this->replItemVariantRegistrationRepository->getList($criteria)->getItems();
    }
}
