<?php
namespace Ls\Omni\Observer;

use Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Helper\OrderHelper;
use Magento\Framework\Event\ObserverInterface;
use Ls\Omni\Helper\ContactHelper;
use Ls\Omni\Client\Ecommerce\Entity;
use Ls\Customer\Model\LSR;
use Magento\Sales\Model\Order;

class OrderObserver implements ObserverInterface
{
    private $contactHelper;
    protected $basketHelper;
    protected $orderHelper;
    protected $logger;
    protected $customerSession;
    protected $checkoutSession;
    protected $watchNextSave = FALSE;

    public function __construct(
        ContactHelper $contactHelper,
        BasketHelper $basketHelper,
        OrderHelper $orderHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        $this->contactHelper = $contactHelper;
        $this->basketHelper = $basketHelper;
        $this->orderHelper = $orderHelper;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getData( 'order' );

        /** @var Entity\BasketCalcResponse $basketCalculation */
        $basketCalculation = $this->basketHelper->getOneListCalculation();
        
        $request = $this->orderHelper->prepareOrder($order, $basketCalculation);
        $response = $this->orderHelper->placeOrder($request);
        // TODO: error handling

        return $this;
    }
}
