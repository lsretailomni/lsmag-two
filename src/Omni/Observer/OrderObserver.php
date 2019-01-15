<?php
namespace Ls\Omni\Observer;

use Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Helper\OrderHelper;
use Magento\Framework\Event\ObserverInterface;
use Ls\Omni\Helper\ContactHelper;
use Ls\Omni\Client\Ecommerce\Entity;
use Ls\Core\Model\LSR;

class OrderObserver implements ObserverInterface
{
    /** @var ContactHelper  */
    private $contactHelper;

    /** @var BasketHelper  */
    protected $basketHelper;

    /** @var OrderHelper  */
    protected $orderHelper;

    /** @var \Psr\Log\LoggerInterface  */
    protected $logger;

    /**
     * @var \Magento\Customer\Model\Session\Proxy
     */
    protected $customerSession;

    /**
     * @var \Magento\Checkout\Model\Session\Proxy
     */
    protected $checkoutSession;

    /** @var bool  */
    protected $watchNextSave = false;

    /**
     * OrderObserver constructor.
     * @param ContactHelper $contactHelper
     * @param BasketHelper $basketHelper
     * @param OrderHelper $orderHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     */

    public function __construct(
        ContactHelper $contactHelper,
        BasketHelper $basketHelper,
        OrderHelper $orderHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession
    ) {
        $this->contactHelper = $contactHelper;
        $this->basketHelper = $basketHelper;
        $this->orderHelper = $orderHelper;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getData('order');
        /** @var Entity\BasketCalcResponse $basketCalculation */
        $basketCalculation = $this->basketHelper->getOneListCalculation();
        $request = $this->orderHelper->prepareOrder($order, $basketCalculation);
        $response = $this->orderHelper->placeOrder($request);
        if ($response) {
            //delete from Omni.
            if ($this->customerSession->getData(LSR::SESSION_CART_ONELIST)) {
                $onelist = $this->customerSession->getData(LSR::SESSION_CART_ONELIST);
                //TODO error which Hjalti highlighted. when there is only one item in the cart and customer remove that.
                $success = $this->basketHelper->delete($onelist);
                $this->customerSession->unsetData(LSR::SESSION_CART_ONELIST);
                // delete checkout session data.
                $this->basketHelper->unSetOneListCalculation();
            }
        } else {
            // TODO: error handling
            $this->logger->critical(
                __('Something trrible happen while placing order')
            );
        }

        return $this;
    }
}
