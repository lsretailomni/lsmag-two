<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Customer Deletion
 */
class Customer extends AbstractReset
{
    /** List of all the Customer tables */
    public const MAGENTO_CUSTOMER_TABLES = [
        'customer_address_entity',
        'customer_address_entity_datetime',
        'customer_address_entity_decimal',
        'customer_address_entity_int',
        'customer_address_entity_text',
        'customer_address_entity_varchar',
        'customer_entity',
        'customer_entity_datetime',
        'customer_entity_decimal',
        'customer_entity_int',
        'customer_entity_text',
        'customer_entity_varchar',
        'customer_log',
        'customer_visitor',
        'persistent_session',
        'wishlist',
        'wishlist_item',
        'wishlist_item_option'
    ];

    public const MAGENTO_CUSTOMER_GRIDS = [
        'customer_grid_flat'
    ];

    /**
     * Remove customers
     *
     * @return ResponseInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $scopeId = $this->_request->getParam('store');

        if ($scopeId != '') {
            $websiteId = $this->replicationHelper->getWebsiteIdGivenStoreId($scopeId);
            $this->deleteAllCustomersGivenWebsiteId($websiteId);
            $this->deleteAllOrphanRecords($websiteId);
        } else {
            $this->truncateAllGivenTables(array_merge(self::MAGENTO_CUSTOMER_TABLES, self::MAGENTO_CUSTOMER_GRIDS));
        }

        $this->messageManager->addSuccessMessage(__('Customers deleted successfully.'));

        return $this->_redirect('adminhtml/system_config/edit/section/ls_mag', ['store' => $scopeId]);
    }

    /**
     * Delete all customers given website_id
     *
     * @param $websiteId
     * @return void
     */
    public function deleteAllCustomersGivenWebsiteId($websiteId)
    {
        $this->replicationHelper->deleteGivenTableDataGivenConditions(
            $this->replicationHelper->getGivenTableName('customer_entity'),
            ['website_id = (?)' => $websiteId]
        );
    }

    /**
     * Delete all orphan records
     *
     * @param $websiteId
     * @return void
     */
    public function deleteAllOrphanRecords($websiteId)
    {
        foreach (self::MAGENTO_CUSTOMER_GRIDS as $grid) {
            $this->replicationHelper->deleteGivenTableDataGivenConditions(
                $this->replicationHelper->getGivenTableName($grid),
                [
                    'store_id = ?' => $websiteId
                ]
            );
        }
    }
}
