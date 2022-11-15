<?php

namespace Ls\Omni\Plugin\Block\Adminhtml\Order;

use \Ls\Core\Model\LSR;
use Ls\Omni\Helper\OrderHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;

class View
{
    /**
     * @var OrderHelper
     */
    public $orderHelper;

    public function __construct(OrderHelper $orderHelper)
    {
        $this->orderHelper = $orderHelper;
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

        if (!$view->getOrder()->getDocumentId() && $this->orderHelper->isAllowed($view->getOrder())) {
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
