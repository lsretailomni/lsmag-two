<?php

namespace Ls\Omni\Observer\Frontend;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\OneList;
use \Ls\Omni\Client\Ecommerce\Entity\Order;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use \LS\Omni\Helper\ItemHelper;
use Magento\Checkout\Model\Session\Proxy as CheckoutProxy;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;

/**
 * Class CartObserver
 * @package Ls\Omni\Observer\Frontend
 */
class CartObserver implements ObserverInterface
{

    /**
     * @var BasketHelper
     */
    private $basketHelper;

    /**
     * @var ItemHelper
     */
    private $itemHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;


    /**
     * @var CheckoutProxy
     */
    private $checkoutSession;

    /**
     * @var bool
     */
    private $watchNextSave = false;

    /**
     * @var LSR
     */
    private $lsr;

    /**
     * @var Data
     */
    private $data;

    /**
     * CartObserver constructor.
     * @param BasketHelper $basketHelper
     * @param ItemHelper $itemHelper
     * @param LoggerInterface $logger
     * @param CheckoutProxy $checkoutSession
     * @param LSR $LSR
     * @param Data $data
     */
    public function __construct(
        BasketHelper $basketHelper,
        ItemHelper $itemHelper,
        LoggerInterface $logger,
        CheckoutProxy $checkoutSession,
        LSR $LSR,
        Data $data
    ) {
        $this->basketHelper    = $basketHelper;
        $this->itemHelper      = $itemHelper;
        $this->logger          = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->lsr             = $LSR;
        $this->data            = $data;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     */
    // @codingStandardsIgnoreLine
    public function execute(Observer $observer)
    {
        /*
          * Adding condition to only process if LSR is enabled.
          */
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
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
