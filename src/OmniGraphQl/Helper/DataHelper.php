<?php

namespace Ls\OmniGraphQl\Helper;

use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

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

    /**
     * @param Context $context
     * @param ManagerInterface $eventManager
     * @param BasketHelper $basketHelper
     * @param CheckoutSession $checkoutSession
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Context $context,
        ManagerInterface $eventManager,
        BasketHelper $basketHelper,
        CheckoutSession $checkoutSession,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepositoryInterface $orderRepository
    ) {
        parent::__construct($context);
        $this->eventManager = $eventManager;
        $this->basketHelper = $basketHelper;
        $this->checkoutSession = $checkoutSession;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Setting quote id and ls_one_list in the session and calling the required event
     * @param $quote
     * @return CartInterface|\Magento\Quote\Model\Quote
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
        $orderData = null;
        $order = $this->orderRepository->getList($searchCriteria);
        if ($order->getTotalCount()) {
            $orderData = current($order->getItems());
        }
        return $orderData;
    }
}
