<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

abstract class AbstractReset extends Action
{
    public const LS_ITEM_RELATED_TABLES = [
        'ls_replication_repl_item',
        'ls_replication_repl_item_variant_registration',
        'ls_replication_repl_price',
        'ls_replication_repl_barcode',
        'ls_replication_repl_inv_status',
        'ls_replication_repl_hierarchy_leaf',
        'ls_replication_repl_attribute_value',
        'ls_replication_repl_image_link',
        'ls_replication_repl_item_unit_of_measure',
        'ls_replication_repl_loy_vendor_item_mapping',
        'ls_replication_repl_item_modifier',
        'ls_replication_repl_item_recipe',
        'ls_replication_repl_hierarchy_hosp_deal',
        'ls_replication_repl_hierarchy_hosp_deal_line'
    ];

    /** @var ReplicationHelper */
    public $replicationHelper;

    /**
     * @param Context $context
     * @param ReplicationHelper $replicationHelper
     */
    public function __construct(
        Context $context,
        ReplicationHelper $replicationHelper
    ) {
        parent::__construct($context);
        $this->replicationHelper = $replicationHelper;
    }
}
