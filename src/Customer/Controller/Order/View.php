<?php

namespace Ls\Customer\Controller\Order;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\App\Request\Http;

/**
 * Class View
 * @package Ls\Customer\Controller\Order
 */
class View extends \Magento\Framework\App\Action\Action
{
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
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Http $request,
        \Ls\Omni\Helper\OrderHelper $orderHelper,
        \Magento\Framework\Registry $registry
    ) {
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
        $orderId = $this->request->getParam('order_id');
        $this->setCurrentOrderInRegistry($orderId);
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }

    /**
     * @param $orderId
     */
    public function setCurrentOrderInRegistry($orderId)
    {
        $response = $this->orderHelper->getOrderDetailsAgainstId($orderId);
        $this->setOrderInRegistry($response);
    }

    /**
     * @param $order
     */
    public function setOrderInRegistry($order)
    {
        $this->registry->register('current_order', $order);
    }
}
