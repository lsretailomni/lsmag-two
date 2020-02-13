<?php

namespace Ls\Replication\Model\ResourceModel\Order\Grid;

use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as GridCollection;

/**
 * Class Collection
 * @package Ls\Replication\Model\ResourceModel\Order\Grid
 */
class Collection extends GridCollection
{
    /**
     * override sales order grid collection function
     */
    protected function _renderFiltersBefore()
    {
        $joinTable = $this->getTable('sales_order');
        $this->getSelect()->joinLeft($joinTable, 'main_table.entity_id = sales_order.entity_id', ['document_id']);
        $this->getSelect()->group('main_table.entity_id');
        $this->getSelect()->group('main_table.store_id');
        parent::_renderFiltersBefore();
    }
}
