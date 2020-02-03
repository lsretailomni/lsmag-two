<?php

namespace Ls\Omni\Observer;

use Ls\Core\Model\LSR;
use Ls\Omni\Exception\InvalidEnumException;
use Ls\Omni\Helper\BasketHelper;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CouponCodeObserver
 * @package Ls\Omni\Observer
 */
class CouponCodeObserver implements ObserverInterface
{
    /** @var BasketHelper */
    private $basketHelper;

    /** @var LoggerInterface */
    private $logger;

    /** @var ManagerInterface */
    private $messageManager;

    /** @var  RedirectFactory $redirectFactory */
    private $redirectFactory;

    /** @var UrlInterface */
    private $url;

    /** @var LSR @var */
    private $lsr;

    /**
     * CouponCodeObserver constructor.
     * @param BasketHelper $basketHelper
     * @param LoggerInterface $logger
     * @param ManagerInterface $messageManager
     * @param RedirectFactory $redirectFactory
     * @param UrlInterface $url
     * @param LSR $LSR
     */
    public function __construct(
        BasketHelper $basketHelper,
        LoggerInterface $logger,
        ManagerInterface $messageManager,
        RedirectFactory $redirectFactory,
        UrlInterface $url,
        LSR $LSR
    ) {
        $this->basketHelper    = $basketHelper;
        $this->logger          = $logger;
        $this->messageManager  = $messageManager;
        $this->redirectFactory = $redirectFactory;
        $this->url             = $url;
        $this->lsr             = $LSR;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR()) {
            $controller = $observer->getControllerAction();
            $couponCode = $controller->getRequest()->getParam('coupon_code');
            $couponCode = trim($couponCode);
            $status     = $this->basketHelper->setCouponCode($couponCode);
            if ($controller->getRequest()->getParam('remove') == 1) {
                $this->basketHelper->setCouponCode('');
                $this->messageManager->addSuccessMessage(__("Coupon code successfully removed."));
            } else {
                if ($status == "success") {
                    $this->messageManager->addSuccessMessage(__(
                        'You used coupon code "%1".',
                        $couponCode
                    ));
                } else {
                    if ($status == "") {
                        $message = __("Coupon Code is not valid for these item(s)");
                        $status  = __($message);
                    }
                    $this->messageManager->addErrorMessage($status);
                }
            }
        }
        return $this;
    }
}
