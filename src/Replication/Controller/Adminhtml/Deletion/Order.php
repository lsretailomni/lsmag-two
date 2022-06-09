<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Order Deletion
 */
class Order extends AbstractReset
{
    /** @var array List of all the Order tables */
    public const MAGENTO_ORDER_TOTALS = [
        'gift_message',
        'quote',
        'quote_address',
        'quote_address_item',
        'quote_id_mask',
        'quote_item',
        'quote_item_option',
        'quote_payment',
        'quote_shipping_rate',
        'reporting_orders',
        'sales_bestsellers_aggregated_daily',
        'sales_bestsellers_aggregated_monthly',
        'sales_bestsellers_aggregated_yearly',
        'sales_creditmemo',
        'sales_creditmemo_comment',
        'sales_creditmemo_item',
        'sales_invoice',
        'sales_invoiced_aggregated',
        'sales_invoiced_aggregated_order',
        'sales_invoice_comment',
        'sales_invoice_item',
        'sales_order',
        'sales_order_address',
        'sales_order_aggregated_created',
        'sales_order_aggregated_updated',
        'sales_order_item',
        'sales_order_payment',
        'sales_order_status_history',
        'sales_order_tax',
        'sales_order_tax_item',
        'sales_payment_transaction',
        'sales_refunded_aggregated',
        'sales_refunded_aggregated_order',
        'sales_shipment',
        'sales_shipment_comment',
        'sales_shipment_item',
        'sales_shipment_track',
        'sales_shipping_aggregated',
        'sales_shipping_aggregated_order',
        'tax_order_aggregated_created',
        'tax_order_aggregated_updated',
        'sequence_order_0',
        'sequence_creditmemo_0',
        'sequence_invoice_0',
        'sequence_shipment_0'
    ];

    public const MAGENTO_ORDER_GRIDS = [
        'sales_order_grid',
        'sales_shipment_grid',
        'sales_creditmemo_grid',
        'sales_invoice_grid'
    ];

    // @codingStandardsIgnoreStart
    /** @var array */
    protected $_publicActions = ['order'];
    // @codingStandardsIgnoreEnd

    /**
     * Remove Orders
     *
     * @return ResponseInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $scopeId = $this->_request->getParam('store');

        if ($scopeId != '') {
            $this->deleteAllOrdersGivenStoreId($scopeId);
            $this->deleteAllOrphanRecords($scopeId);
            $stores              = [$this->replicationHelper->storeManager->getStore($scopeId)];
            $storeSpecificTables = $this->getStoreSpecificOrderTables($stores);
            $this->truncateAllGivenTables($storeSpecificTables);
        } else {
            $stores              = $this->replicationHelper->lsr->getAllStores();
            $storeSpecificTables = $this->getStoreSpecificOrderTables($stores);
            $this->truncateAllGivenTables(
                array_merge(self::MAGENTO_ORDER_TOTALS, self::MAGENTO_ORDER_GRIDS, $storeSpecificTables)
            );
        }

        $this->messageManager->addSuccessMessage(__('Orders deleted successfully.'));

        return $this->_redirect('adminhtml/system_config/edit/section/ls_mag', ['store' => $scopeId]);
    }

    /**
     * Get store specific order totals
     *
     * @param $stores
     * @return array
     */
    public function getStoreSpecificOrderTables($stores)
    {
        $storeSpecificTables = [];

        foreach ($stores as $store) {
            $storeId               = $store->getId();
            $storeSpecificTables[] = 'sequence_order_' . $storeId;
            $storeSpecificTables[] = 'sequence_creditmemo_' . $storeId;
            $storeSpecificTables[] = 'sequence_invoice_' . $storeId;
            $storeSpecificTables[] = 'sequence_shipment_' . $storeId;
        }

        return $storeSpecificTables;
    }

    /**
     * Delete all orders given store_id
     *
     * @param $storeId
     * @return void
     */
    public function deleteAllOrdersGivenStoreId($storeId)
    {
        $this->replicationHelper->deleteGivenTableDataGivenConditions(
            $this->replicationHelper->getGivenTableName('sales_order'),
            ['store_id = (?)' => $storeId]
        );
    }

    /**
     * Delete all orphan records
     *
     * @param $storeId
     * @return void
     */
    public function deleteAllOrphanRecords($storeId)
    {
        foreach (self::MAGENTO_ORDER_GRIDS as $grid) {
            $this->replicationHelper->deleteGivenTableDataGivenConditions(
                $this->replicationHelper->getGivenTableName($grid),
                [
                    'store_id = ?' => $storeId
                ]
            );
        }
    }
}
