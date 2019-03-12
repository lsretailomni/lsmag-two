<?php

namespace Ls\Omni\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Core\Model\LSR;

/**
 * Class DataAssignObserver
 * @package Ls\Omni\Observer
 */
class CouponCodeObserver implements ObserverInterface
{
    /** @var BasketHelper */
    private $basketHelper;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var \Magento\Framework\Message\ManagerInterface */
    private $messageManager;

    /** @var  \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory */
    public $redirectFactory;

    /** @var \Magento\Framework\UrlInterface  */
    public $url;

    /**
     * CouponCodeObserver constructor.
     * @param BasketHelper $basketHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        BasketHelper $basketHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->basketHelper = $basketHelper;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->redirectFactory = $redirectFactory;
        $this->url = $url;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $controller = $observer->getControllerAction();
        $couponCode = $controller->getRequest()->getParam('coupon_code');
        $couponCode = trim($couponCode);
        $status = $this->basketHelper->setCouponCode($couponCode);
        if ($controller->getRequest()->getParam('remove') == 1) {
            $this->basketHelper->setCouponCode('');
            $this->messageManager->addSuccessMessage(__("Coupon Code Successfully Removed"));
        }
        else if ($status == "success") {
            $this->messageManager->addSuccessMessage(__(
                'You used coupon code "%1".',
                $couponCode
            ));
        } else {
            if($status==""){
               $status= __(LSR::LS_COUPON_CODE_ERROR_MESSAGE);
            }
            $this->messageManager->addErrorMessage($status);
        }

    }

}
