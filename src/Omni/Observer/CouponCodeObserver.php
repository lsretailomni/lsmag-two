<?php
declare(strict_types=1);

namespace Ls\Omni\Observer;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;

/**
 * This observer is responsible for applying coupon to the cart
 */
class CouponCodeObserver implements ObserverInterface
{
    /**
     * @param BasketHelper $basketHelper
     * @param ManagerInterface $messageManager
     * @param LSR $lsr
     */
    public function __construct(
        public BasketHelper $basketHelper,
        public ManagerInterface $messageManager,
        public LSR $lsr
    ) {
    }

    /**
     * Entry point for the observer
     *
     * @param Observer $observer
     * @return $this
     * @throws InvalidEnumException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws GuzzleException
     */
    public function execute(Observer $observer)
    {
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR(
            $this->lsr->getCurrentStoreId(),
            false,
            $this->lsr->getBasketIntegrationOnFrontend()
        )) {
            $controller = $observer->getControllerAction();
            $couponCode = $controller->getRequest()->getParam('coupon_code');
            $couponCode = !empty($couponCode) ? trim($couponCode) : '';
            $status = $this->basketHelper->setCouponCode($couponCode);

            if ($controller->getRequest()->getParam('remove') == 1) {
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
                        $status = __($message);
                    }
                    $this->messageManager->addErrorMessage($status);
                }
            }
        }

        return $this;
    }
}
