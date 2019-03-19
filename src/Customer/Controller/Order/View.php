<?php

namespace Ls\Customer\Controller\Order;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class View
 * @package Ls\Customer\Controller\Order
 */
class View extends \Magento\Framework\App\Action\Action
{
    /**
     * @var
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
            if ($response === null) {
                $message = __('This order id is not corresponded to any order');
                $this->messageManager->addErrorMessage($message);
            }
        }
        if ($response === null) {
            $resultRedirect = $this->resultRedirect->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
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
}
