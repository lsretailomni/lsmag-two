<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use \Ls\Core\Model\LSR;
use Ls\Replication\Model\ResourceModel\ReplAttribute\Collection;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Attribute Deletion
 */
class Attribute extends AbstractReset
{
    public const DEPENDENT_CRONS = [
        LSR::SC_SUCCESS_CRON_ATTRIBUTE,
        LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT,
        LSR::SC_SUCCESS_CRON_DATA_TRANSLATION_TO_MAGENTO
    ];

    /**
     * Remove Attributes
     *
     * @return ResponseInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $scopeId = $this->_request->getParam('store');
        $where   = [];

        if ($scopeId != '') {
            $websiteId = $this->replicationHelper->getWebsiteIdGivenStoreId($scopeId);
            $this->removeSoftAttributes($scopeId);
            $this->removeHardAttributes($scopeId);
            $where = ['scope_id = ?' => $scopeId];
            $this->updateAllGivenTablesToUnprocessed(self::LS_ATTRIBUTE_RELATED_TABLES, ['scope_id = ?' => $websiteId]);
        } else {
            $this->replicationHelper->deleteGivenTableDataGivenConditions(
                $this->replicationHelper->getGivenTableName('eav_attribute'),
                ['attribute_code like (?)' => 'ls\_%']
            );
            $this->updateAllGivenTablesToUnprocessed(self::LS_ATTRIBUTE_RELATED_TABLES, $where);
        }
        // Reset Data Translation Table for attributes
        $where['TranslationId = ?'] = LSR::SC_TRANSLATION_ID_ATTRIBUTE_OPTION_VALUE;
        $this->updateDataTranslationTables($where);

        $this->replicationHelper->updateAllGivenCronsWithGivenStatus(self::DEPENDENT_CRONS, $scopeId, false);
        $this->messageManager->addSuccessMessage(__('LS Attributes deleted successfully.'));

        return $this->_redirect('adminhtml/system_config/edit/section/ls_mag', ['store' => $scopeId]);
    }

    /**
     * Only fetching those attributes which are uncommon in multiple websites
     *
     * @param $scopeId
     * @return Collection
     */
    public function getAllUnCommonSoftReplicationAttributesGivenScopeId($scopeId)
    {
        $childCollection = $this->replAttributeCollectionFactory->create();
        $parentCollection = clone $childCollection;
        $childCollection->getSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns('main_table.Code')
            ->group('main_table.Code')
            ->having(new \Zend_Db_Expr('COUNT(main_table.Code)') . ' = 1');

        $parentCollection
            ->getSelect()
            ->where('Code IN (?)', new \Zend_Db_Expr($childCollection->getSelect()))
            ->where('scope_id = (?)', $scopeId);

        return $parentCollection;
    }

    /**
     * Only fetching those attributes which are uncommon in multiple websites
     *
     * @param $scopeId
     * @return \Ls\Replication\Model\ResourceModel\ReplExtendedVariantValue\Collection
     */
    public function getAllUnCommonHardReplicationAttributesGivenScopeId($scopeId)
    {
        $storeIds = array_keys($this->replicationHelper->storeManager->getStores());
        $storeIds = array_diff($storeIds, [$scopeId]);
        $childCollection = $this->replExtendedCollectionFactory->create();
        $parentCollection = clone $childCollection;
        $childCollection->getSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns('main_table.Code')
            ->group('main_table.Code')
            ->where('scope_id IN (?)', $storeIds);

        $parentCollection->getSelect()
            ->where('Code NOT IN (?)', new \Zend_Db_Expr($childCollection->getSelect()))
            ->where('scope_id = (?)', $scopeId)
            ->group('Code');

        return $parentCollection;
    }

    /**
     * Get formatted attribute codes given collection
     *
     * @param $parentCollection
     * @return array
     */
    public function getFormattedAttributeCodesGivenCollection($parentCollection)
    {
        $attributes = [];
        foreach ($parentCollection as $attribute) {
            $formattedCode = $this->replicationHelper->formatAttributeCode($attribute->getCode());
            $attributes[] = $formattedCode;
        }

        return $attributes;
    }

    /**
     * Remove all soft attributes
     *
     * @param $scopeId
     * @return void
     */
    public function removeSoftAttributes($scopeId)
    {
        $parentCollection = $this->getAllUnCommonSoftReplicationAttributesGivenScopeId($scopeId);
        $attributes = $this->getFormattedAttributeCodesGivenCollection($parentCollection);
        $this->replicationHelper->deleteGivenTableDataGivenConditions(
            $this->replicationHelper->getGivenTableName('eav_attribute'),
            ['attribute_code IN (?)' => $attributes]
        );
    }

    /**
     * Remove all hard attributes
     *
     * @param $scopeId
     * @return void
     */
    public function removeHardAttributes($scopeId)
    {
        $parentCollection = $this->getAllUnCommonHardReplicationAttributesGivenScopeId($scopeId);
        $attributes = $this->getFormattedAttributeCodesGivenCollection($parentCollection);
        $this->replicationHelper->deleteGivenTableDataGivenConditions(
            $this->replicationHelper->getGivenTableName('eav_attribute'),
            ['attribute_code IN (?)' => $attributes]
        );
    }
}
