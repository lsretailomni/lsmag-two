<?php

namespace Ls\Customer\Block\Onepage;

/**
 * Class Success
 * @package Ls\Customer\Block\Onepage
 */
class Success extends \Magento\Checkout\Block\Onepage\Success
{
    /**
     * Prepares block data
     *
     * @return void
     */
    protected function prepareBlockData()
    {
        $order      = $this->_checkoutSession->getLastRealOrder();
        $documentId = $this->_checkoutSession->getLastDocumentId();
        if ($documentId) {
            $this->addData(
                [
                    'is_order_visible' => $this->isVisible($order),
                    'view_order_url'   => $this->getUrl(
                        'customer/order/view/',
                        ['order_id' => $documentId]
                    ),
                    'print_url'        => $this->getUrl(
                        'sales/order/print',
                        ['order_id' => $order->getEntityId()]
                    ),
                    'can_print_order'  => false,
                    'can_view_order'   => $this->canViewOrder($order),
                    'order_id'         => $documentId
                ]
            );
        } else {
            parent::prepareBlockData();
        }
    }

}
