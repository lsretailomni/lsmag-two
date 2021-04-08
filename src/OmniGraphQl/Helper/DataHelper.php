<?php

namespace Ls\OmniGraphQl\Helper;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Useful helper functions for the module
 *
 */
class DataHelper extends AbstractHelper
{

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var BasketHelper
     */
    private $basketHelper;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /** @var CustomerRepositoryInterface */
    public $customerRepository;

    /** @var CustomerFactory */
    public $customerFactory;

    /**
     * @var Session
     */
    public $customerSession;

    /**
     * @param Context $context
     * @param ManagerInterface $eventManager
     * @param BasketHelper $basketHelper
     * @param CheckoutSession $checkoutSession
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerFactory $customerFactory
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        ManagerInterface $eventManager,
        BasketHelper $basketHelper,
        CheckoutSession $checkoutSession,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepositoryInterface $orderRepository,
        CustomerRepositoryInterface $customerRepository,
        CustomerFactory $customerFactory,
        Session $customerSession
    ) {
        parent::__construct($context);
        $this->eventManager          = $eventManager;
        $this->basketHelper          = $basketHelper;
        $this->checkoutSession       = $checkoutSession;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository       = $orderRepository;
        $this->customerRepository    = $customerRepository;
        $this->customerFactory       = $customerFactory;
        $this->customerSession       = $customerSession;
    }

    /**
     * Setting quote id and ls_one_list in the session and calling the required event
     * @param $quote
     * @return CartInterface|Quote
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function triggerEventForCartChange($quote)
    {
        $basketHelper = $this->basketHelper->get($quote->getLsOneListId());

        if ($basketHelper) {
            $this->basketHelper->setOneListInCustomerSession($basketHelper);
        }

        $this->checkoutSession->setQuoteId($quote->getId());
        $this->eventManager->dispatch('checkout_cart_save_after');

        return $this->checkoutSession->getQuote();
    }

    /**
     * Gives order based on the given increment_id
     * @param $incrementId
     * @return OrderInterface
     */
    public function getOrderByIncrementId($incrementId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $incrementId)->create()
            ->setPageSize(1)->setCurrentPage(1);
        $orderData      = null;
        $order          = $this->orderRepository->getList($searchCriteria);

        if ($order->getTotalCount()) {
            $orderData = current($order->getItems());
        }

        return $orderData;
    }

    /**
     * Setting required values in the customer session that will be used later
     * @param int $customerId
     * @param int $websiteId
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function setCustomerValuesInSession($customerId = 0, $websiteId = 0)
    {
        if ($customerId === 0) {
            return;
        }

        $customer = $this->customerRepository->getById($customerId);
        $customer = $this->customerFactory->create()
            ->setWebsiteId($websiteId)
            ->loadByEmail($customer->getEmail());
        $this->customerSession->setCustomer($customer);
        //$this->customerSession->setCustomerAsLoggedIn($customer)

        $this->customerSession->setData(LSR::SESSION_CUSTOMER_SECURITYTOKEN, $customer->getData('lsr_token'));
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_LSRID, $customer->getData('lsr_id'));
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
    }

    /**
     * Getting customer session
     * @return Session
     */
    public function getCustomerSession()
    {
        return $this->customerSession;
    }
}
