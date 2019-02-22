<?php
namespace Ls\Omni\Observer;

use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Framework\Event\ObserverInterface;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Core\Model\LSR;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class OrderObserver
 * @package Ls\Omni\Observer
 */
class OrderSuccessObserver implements ObserverInterface
{
    /** @var ContactHelper  */
    private $contactHelper;

    /** @var BasketHelper  */
    private $basketHelper;

    /** @var OrderHelper  */
    private $orderHelper;

    /** @var \Psr\Log\LoggerInterface  */
    private $logger;

    /**
     * @var \Magento\Customer\Model\Session\Proxy
     */
    private $customerSession;

    /**
     * @var \Magento\Checkout\Model\Session\Proxy
     */
    private $checkoutSession;

    /** @var bool  */
    private $watchNextSave = false;

    protected $orderRepository;

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
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        OrderRepositoryInterface $OrderRepositoryInterface
    ) {
        $this->contactHelper = $contactHelper;
        $this->basketHelper = $basketHelper;
        $this->orderHelper = $orderHelper;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $OrderRepositoryInterface;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $order_id = $observer->getEvent()->getOrderIds()[0];
        $order = $this->orderRepository->get($order_id);
        //TODO Update/Create order for successful payment when get payment from 3rd Party page.

    }
}
