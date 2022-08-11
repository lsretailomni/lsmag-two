<?php

namespace Ls\Omni\Plugin\Block\Adminhtml\Order;

use \Ls\Core\Model\LSR;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;

class View
{
    /** @var  LSR $lsr */
    public $lsr;

    /**
     * @param LSR $lsr
     */
    public function __construct(LSR $lsr)
    {
        $this->lsr = $lsr;
    }

    /**
     * Before set layout
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\View $view
     * @return void
     * @throws NoSuchEntityException
     */
    public function beforeSetLayout(\Magento\Sales\Block\Adminhtml\Order\View $view)
    {
        $message = __('Send order to LS Central?');
        $url     = $view->getUrl('omni/order/request/id', ['order_id' => $view->getOrderId()]);

        if (!$view->getOrder()->getDocumentId() && $this->isAllowed($view->getOrder())) {
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

    /**
     * Order status is not one of restricted order statuses
     *
     * @param Order $order
     * @return bool
     */
    public function isAllowed($order)
    {
        $orderStatuses = $this->lsr->getStoreConfig(
            LSR::LSR_RESTRICTED_ORDER_STATUSES,
            $order->getStore()->getWebsiteId()
        );

        $status = $order->getStatus();

        return !empty($orderStatuses) && !(in_array($status, explode(',', $orderStatuses)));
    }
}
