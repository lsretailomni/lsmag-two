<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use \Ls\Core\Model\LSR;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Magento uses Catalog Price Rule for discounts replication
 * Class Discount Deletion
 */
class Discount extends AbstractReset
{
    /**  List of all the Discount tables */
    public const MAGENTO_DISCOUNT_TABLES = [
        'catalogrule',
        'catalogrule_customer_group',
        'catalogrule_group_website',
        'catalogrule_group_website_replica',
        'catalogrule_product_price',
        'catalogrule_product_price_replica',
        'catalogrule_product',
        'catalogrule_product_replica',
        'catalogrule_website',
        'sequence_catalogrule'
    ];

    public const DEPENDENT_CRONS = [
        LSR::SC_SUCCESS_CRON_DISCOUNT
    ];

    /**
     * Remove discounts
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
            $childCollection = $this->replicationHelper->getCatalogRulesCollectionGivenWebsiteId($websiteId);
            $parentCollection = $this->replicationHelper->getGivenColumnsFromGivenCollection(
                $childCollection,
                ['rule_id']
            );
            $this->replicationHelper->deleteGivenTableDataGivenConditions(
                $this->replicationHelper->getGivenTableName('catalogrule'),
                ['rule_id IN (?)' => $parentCollection->getSelect()]
            );

            $where = ['scope_id = ?' => $scopeId];
        } else {
            $this->truncateAllGivenTables(self::MAGENTO_DISCOUNT_TABLES);
        }

        $this->updateAllGivenTablesToUnprocessed(self::LS_DISCOUNT_RELATED_TABLES, $where);
        $this->replicationHelper->updateAllGivenCronsWithGivenStatus(self::DEPENDENT_CRONS, $scopeId, false);

        $this->messageManager->addSuccessMessage(__('Discounts deleted successfully.'));

        return $this->_redirect('adminhtml/system_config/edit/section/ls_mag', ['store' => $scopeId]);
    }
}
