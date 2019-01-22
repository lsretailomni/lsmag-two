<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Class Order Deletion
 */
class Order extends Action
{
    /** @var LoggerInterface */
    public $logger;

    /** @var ResourceConnection */
    public $resource;

    /** @var array List of all the Order tables */
    public $order_tables = [
        "gift_message",
        "quote",
        "quote_address",
        "quote_address_item",
        "quote_id_mask",
        "quote_item",
        "quote_item_option",
        "quote_payment",
        "quote_shipping_rate",
        "reporting_orders",
        "sales_bestsellers_aggregated_daily",
        "sales_bestsellers_aggregated_monthly",
        "sales_bestsellers_aggregated_yearly",
        "sales_creditmemo",
        "sales_creditmemo_comment",
        "sales_creditmemo_grid",
        "sales_creditmemo_item",
        "sales_invoice",
        "sales_invoiced_aggregated",
        "sales_invoiced_aggregated_order",
        "sales_invoice_comment",
        "sales_invoice_grid",
        "sales_invoice_item",
        "sales_order",
        "sales_order_address",
        "sales_order_aggregated_created",
        "sales_order_aggregated_updated",
        "sales_order_grid",
        "sales_order_item",
        "sales_order_payment",
        "sales_order_status_history",
        "sales_order_tax",
        "sales_order_tax_item",
        "sales_payment_transaction",
        "sales_refunded_aggregated",
        "sales_refunded_aggregated_order",
        "sales_shipment",
        "sales_shipment_comment",
        "sales_shipment_grid",
        "sales_shipment_item",
        "sales_shipment_track",
        "sales_shipping_aggregated",
        "sales_shipping_aggregated_order",
        "tax_order_aggregated_created",
        "tax_order_aggregated_updated"
    ];

    /** @var array  */
    public $publicActions = ['order'];

    /**
     * Order Deletion constructor.
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resource,
        LoggerInterface $logger,
        Context $context
    ) {
        $this->resource = $resource;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Remove Orders
     *
     * @return void
     */
    public function execute()
    {
        // @codingStandardsIgnoreStart
        $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $connection->query('SET FOREIGN_KEY_CHECKS = 0;');
        foreach ($this->order_tables as $orderTable) {
            $tableName = $connection->getTableName($orderTable);
            $query = "TRUNCATE TABLE " . $tableName;
            try {
                $connection->query($query);
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
        $connection->query('SET FOREIGN_KEY_CHECKS = 1;');
        // @codingStandardsIgnoreEnd
        $this->messageManager->addSuccessMessage(__('Orders deleted successfully.'));
        $this->_redirect('adminhtml/system_config/edit/section/ls_mag');
    }
}
