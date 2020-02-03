<?php

namespace Ls\Omni\Observer;

use Exception;
use Ls\Core\Model\LSR;
use Ls\Omni\Client\Ecommerce\Entity\OneList;
use Ls\Omni\Client\Ecommerce\Entity\Order;
use Ls\Omni\Exception\InvalidEnumException;
use Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Helper\ContactHelper;
use Ls\Omni\Helper\Data;
use LS\Omni\Helper\ItemHelper;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;

/**
 * Class CartObserver
 * @package Ls\Omni\Observer
 */
class CartObserver implements ObserverInterface
{
    /** @var ContactHelper */
    private $contactHelper;

    /** @var BasketHelper */
    private $basketHelper;

    /** @var ItemHelper */
    private $itemHelper;

    /** @var LoggerInterface */
    private $logger;

    /** @var \Magento\Customer\Model\Session\Proxy $customerSession */
    private $customerSession;

    /** @var Proxy $checkoutSession */
    private $checkoutSession;

    /** @var bool */
    private $watchNextSave = false;

    /** @var LSR @var */
    private $lsr;

    /** @var Data @var */
    private $data;

    /**
     * CartObserver constructor.
     * @param ContactHelper $contactHelper
     * @param BasketHelper $basketHelper
     * @param ItemHelper $itemHelper
     * @param LoggerInterface $logger
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param Proxy $checkoutSession
     * @param LSR $LSR
     * @param Data $data
     */
    public function __construct(
        ContactHelper $contactHelper,
        BasketHelper $basketHelper,
        ItemHelper $itemHelper,
        LoggerInterface $logger,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        Proxy $checkoutSession,
        LSR $LSR,
        Data $data
    ) {
        $this->contactHelper   = $contactHelper;
        $this->basketHelper    = $basketHelper;
        $this->itemHelper      = $itemHelper;
        $this->logger          = $logger;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->lsr             = $LSR;
        $this->data            = $data;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    // @codingStandardsIgnoreLine
    public function execute(Observer $observer)
    {
        /*
          * Adding condition to only process if LSR is enabled.
          */
        if ($this->lsr->isLSR()) {
            if ($this->watchNextSave) {
                try {
                    /** @var Quote $quote */
                    $quote      = $this->checkoutSession->getQuote();
                    $couponCode = $this->checkoutSession->getCouponCode();
                    // This will create one list if not created and will return onelist if its already created.
                    /** @var OneList|null $oneList */
                    $oneList = $this->basketHelper->get();
                    //TODO if there is any no items, i-e when user only has one item and s/he prefer to remove from cart,
                    // then dont calculate basket functionality below.
                    // add items from the quote to the oneList and return the updated onelist
                    $oneList = $this->basketHelper->setOneListQuote($quote, $oneList);
                    if (!empty($couponCode)) {
                        $status = $this->basketHelper->setCouponCode($couponCode);
                        if (!is_object($status)) {
                            $this->checkoutSession->setCouponCode('');
                        }
                    }
                    if (count($quote->getAllItems()) == 0) {
                        $quote->setLsGiftCardAmountUsed(0);
                        $quote->setLsGiftCardNo(null);
                        $quote->setLsPointsSpent(0);
                        $quote->setLsPointsEarn(0);
                        $quote->setGrandTotal(0);
                        $quote->setBaseGrandTotal(0);
                        $this->basketHelper->quoteRepository->save($quote);
                    }
                    /** @var Order $basketData */
                    $basketData = $this->basketHelper->update($oneList);
                    $this->itemHelper->setDiscountedPricesForItems($quote, $basketData);
                    if (!empty($basketData)) {
                        $this->checkoutSession->getQuote()->setLsPointsEarn($basketData->getPointsRewarded())->save();
                    }
                    if ($this->checkoutSession->getQuote()->getLsGiftCardAmountUsed() > 0 ||
                        $this->checkoutSession->getQuote()->getLsPointsSpent() > 0) {
                        $this->data->orderBalanceCheck(
                            $this->checkoutSession->getQuote()->getLsGiftCardNo(),
                            $this->checkoutSession->getQuote()->getLsGiftCardAmountUsed(),
                            $this->checkoutSession->getQuote()->getLsPointsSpent(),
                            $basketData
                        );
                    }
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        }
        return $this;
    }

    /**
     * @param bool $value
     */
    public function watchNextSave($value = true)
    {
        $this->watchNextSave = $value;
    }
}
