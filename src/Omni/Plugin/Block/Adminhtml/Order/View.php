<?php

namespace Ls\Omni\Plugin\Block\Adminhtml\Order;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\OrderHelper;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Framework\Exception\NoSuchEntityException;

class View
{
    /**
     * @var OrderHelper
     */
    public $orderHelper;

    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * @param OrderHelper $orderHelper
     * @param BasketHelper $basketHelper
     */
    public function __construct(OrderHelper $orderHelper, BasketHelper $basketHelper)
    {
        $this->orderHelper = $orderHelper;
        $this->basketHelper = $basketHelper;
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
        $order   = $view->getOrder();

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
        if ($order->canEdit() && $this->orderHelper->lsr->getStoreConfig(
            LSR::LSR_ORDER_EDIT,
            $order->getStoreId()
        )) {
            $this->basketHelper->setOneListCalculationInCheckoutSession(null);
            $message = __('Want to Edit the Order?');
            $view->removeButton('order_edit');

            $onclickJs = 'jQuery(\'#order_edit\').orderEditDialog({message: \''
                . $message . '\', url: \'' . $view->getEditUrl()
                . '\'}).orderEditDialog(\'showDialog\');';

            $view->addButton(
                'order_edit',
                [
                    'label'          => __('Edit'),
                    'class'          => 'edit primary',
                    'onclick'        => $onclickJs,
                    'data_attribute' => [
                        'mage-init' => '{"orderEditDialog":{}}',
                    ]
                ]
            );
        }
    }
}
