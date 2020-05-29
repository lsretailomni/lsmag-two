<?php

namespace Ls\Omni\Plugin\Block\Adminhtml\Order;

/**
 * Class View
 * @package Ls\Omni\Plugin\Block\Adminhtml\Order
 */
class View
{
    public function beforeSetLayout(\Magento\Sales\Block\Adminhtml\Order\View $view)
    {
        $message = __('Send order to LS Central?');
        $url     = $view->getUrl('omni/order/request/id', ['order_id' => $view->getOrderId()]);
        if (is_null($view->getOrder()->getDocumentId())) {
            $view->addButton(
                'send-order-request',
                [
                    'label'   => __('Send to LS Central'),
                    'class'   => 'send-order-request',
                    'onclick' => "confirmSetLocation('{$message}', '{$url}')"
                ]
            );
        }
    }
}
