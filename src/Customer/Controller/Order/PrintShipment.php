<?php

namespace Ls\Customer\Controller\Order;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class PrintShipment
 * @package Ls\Customer\Controller\Order
 */
class PrintShipment extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    public $messageManager;

    /**
     * @var ResultFactory
     */
    public $resultRedirect;

    /** @var PageFactory */
    public $resultPageFactory;

    /**
     * @var Http $request
     */
    public $request;

    /**
     * @var \Ls\Omni\Helper\OrderHelper
     */
    public $orderHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    public $registry;

    /**
     * View constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Http $request
     * @param \Ls\Omni\Helper\OrderHelper $orderHelper
     * @param \Magento\Framework\Registry $registry
     * @param ResultFactory $result
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Http $request,
        \Ls\Omni\Helper\OrderHelper $orderHelper,
        \Magento\Framework\Registry $registry,
        ResultFactory $result,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->resultRedirect = $result;
        $this->messageManager = $messageManager;
        $this->request = $request;
        $this->registry = $registry;
        $this->orderHelper = $orderHelper;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $response = null;
        if ($this->request->getParam('order_id')) {
            $orderId = $this->request->getParam('order_id');
            $response = $this->setCurrentOrderInRegistry($orderId);
            $this->setCurrentMagOrderInRegistry($orderId);
            $this->setShipmentId();
            $this->registry->register('current_shipment_option',true);
            $this->registry->register('hide_shipping_links',false);
            if ($response === null || !$this->orderHelper->isAuthorizedForOrder($response)) {
                return $this->_redirect('sales/order/history/');
            }
            $this->setCurrentMagOrderInRegistry($orderId);
        }
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }

    /**
     * @param $orderId
     * @return \Ls\Omni\Client\Ecommerce\Entity\Order|\Ls\Omni\Client\Ecommerce\Entity\OrderGetByIdResponse|\Ls\Omni\Client\ResponseInterface|null
     */
    public function setCurrentOrderInRegistry($orderId)
    {
        $response = $this->orderHelper->getOrderDetailsAgainstId($orderId);
        if ($response) {
            $this->setOrderInRegistry($response);
        }
        return $response;
    }

    /**
     * @param $order
     */
    public function setOrderInRegistry($order)
    {
        $this->registry->register('current_order', $order);
    }

    /**
     * @param $order
     */
    public function HideShippingLinks()
    {
        return $this->registry->registry('hide_shipping_links');
    }

    /**
     * @param $orderId
     */
    public function setCurrentMagOrderInRegistry($orderId)
    {
        $order=$this->orderHelper->getOrderByDocumentId($orderId);
        $this->registry->unregister('current_mag_order');
        $this->registry->register('current_mag_order', $order);
    }

    /**
     * @param $orderId
     */
    public function setShipmentId()
    {
        $order = $this->registry->registry('current_mag_order');
        foreach ($order->getShipmentsCollection() as $shipment) {
            $this->registry->register('current_shipment_id',$shipment->getIncrementId());
        }
    }

}
