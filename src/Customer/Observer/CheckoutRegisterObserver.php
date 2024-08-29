<?php

namespace Ls\Customer\Observer;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class CheckoutRegisterObserver
 * @package Ls\Customer\Observer
 */
class CheckoutRegisterObserver implements ObserverInterface
{
    /** @var ContactHelper */
    private $contactHelper;

    /** @var CheckoutSession */
    private $checkoutSession;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var CustomerFactory */
    private $customerFactory;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var Customer */
    private $customerResourceModel;

    /** @var LSR @var */
    private $lsr;

    /**
     * CheckoutRegisterObserver constructor.
     * @param ContactHelper $contactHelper
     * @param CheckoutSession $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     * @param CustomerFactory $customerFactory
     * @param StoreManagerInterface $storeManager
     * @param Customer $customerResourceModel
     * @param LSR $LSR
     */

    public function __construct(
        ContactHelper $contactHelper,
        CheckoutSession $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        Customer $customerResourceModel,
        LSR $LSR
    ) {
        $this->contactHelper         = $contactHelper;
        $this->checkoutSession       = $checkoutSession;
        $this->orderRepository       = $orderRepository;
        $this->customerFactory       = $customerFactory;
        $this->storeManager          = $storeManager;
        $this->customerResourceModel = $customerResourceModel;
        $this->lsr                   = $LSR;
    }

    /**
     * @param Observer $observer
     * @throws Exception
     * @throws LocalizedException
     */
    // @codingStandardsIgnoreStart
    public function execute(Observer $observer)
    {
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $orderId = $this->checkoutSession->getLastOrderId();
            $order   = $this->orderRepository->get($orderId);
            if ($order->getCustomerId()) {
                // only performed when a customer id is created
                $customer = $this->customerFactory->create()
                    ->setWebsiteId($this->storeManager->getWebsite()->getWebsiteId())
                    ->loadByEmail($order->getCustomerEmail());
                // setting the lsr_username field
                $customer->setData('lsr_username', $customer->getEmail());
                // manually set the password for now and generate the reset password link
                $customer->setData('password', 'admin@123');
                /* var $contact = Ls/Omni/Client/Ecommerce/Entity/MemberContact */
                $contact = $this->contactHelper->contact($customer);
                if (is_object($contact) && $contact->getId()) {
                    $token = $contact->getLoggedOnToDevice()->getSecurityToken();
                    $card  = $contact->getCard();
                    $customer->setData('lsr_id', $contact->getId());
                    $customer->setData('lsr_token', $token);
                    $customer->setData('lsr_cardid', $card->getId());

                    if ($contact->getAccount()->getScheme()->getId()) {
                        $customerGroupId = $this->contactHelper->getCustomerGroupIdByName(
                            $contact->getAccount()->getScheme()->getId()
                        );
                        $customer->setGroupId($customerGroupId);
                    }
                    $result = $this->contactHelper->forgotPassword($customer->getEmail());
                    if ($result) {
                        $customer->setData('lsr_resetcode', $result);
                    }
                    $this->customerResourceModel->save($customer);
                }
            }
        }
        return $this;
    }
    // @codingStandardsIgnoreEnd
}
