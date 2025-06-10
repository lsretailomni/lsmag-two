<?php

namespace Ls\Customer\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResourceModel;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractOmniObserver implements ObserverInterface
{
    /**
     * @param ContactHelper $contactHelper
     * @param ManagerInterface $messageManager
     * @param LSR $lsr
     * @param LoggerInterface $logger
     * @param CustomerSession $customerSession
     * @param RedirectInterface $redirectInterface
     * @param ActionFlag $actionFlag
     * @param CustomerResourceModel $customerResourceModel
     * @param Registry $registry
     * @param Data $jsonHelper
     * @param JsonFactory $resultJsonFactory
     * @param CheckoutSession $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     * @param CustomerFactory $customerFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        public ContactHelper $contactHelper,
        public ManagerInterface $messageManager,
        public LSR $lsr,
        public LoggerInterface $logger,
        public CustomerSession $customerSession,
        public RedirectInterface $redirectInterface,
        public ActionFlag $actionFlag,
        public Customer $customerResourceModel,
        public Registry $registry,
        public Data $jsonHelper,
        public JsonFactory $resultJsonFactory,
        public CheckoutSession $checkoutSession,
        public OrderRepositoryInterface $orderRepository,
        public CustomerFactory $customerFactory,
        public StoreManagerInterface $storeManager,
    ) {
    }

    /**
     * Entry point for the observer
     *
     * @param Observer $observer
     * @return void
     */
    abstract public function execute(Observer $observer);
}
