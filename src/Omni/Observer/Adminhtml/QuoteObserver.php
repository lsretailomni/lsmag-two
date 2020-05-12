<?php

namespace Ls\Omni\Observer\Adminhtml;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\OneList;
use \Ls\Omni\Client\Ecommerce\Entity\Order;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use \LS\Omni\Helper\ItemHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;

/**
 * Class QuoteObserver
 * @package Ls\Omni\Observer\Adminhtml
 */
class QuoteObserver implements ObserverInterface
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
     * @var LSR
     */
    private $lsr;

    /**
     * @var Data
     */
    private $data;

    /**
     * QuoteObserver constructor.
     * @param BasketHelper $basketHelper
     * @param ItemHelper $itemHelper
     * @param LoggerInterface $logger
     * @param LSR $LSR
     * @param Data $data
     */
    public function __construct(
        BasketHelper $basketHelper,
        ItemHelper $itemHelper,
        LoggerInterface $logger,
        LSR $LSR,
        Data $data
    ) {
        $this->basketHelper = $basketHelper;
        $this->itemHelper   = $itemHelper;
        $this->logger       = $logger;
        $this->lsr          = $LSR;
        $this->data         = $data;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     */
    // @codingStandardsIgnoreLine
    public function execute(Observer $observer)
    {
        /** @var Quote $quote */
        $quote = $observer->getEvent()->getQuote();
        /*
        * Adding condition to only process if LSR is enabled.
        */
        if ($this->lsr->isLSR($quote->getStoreId())) {
            try {
                $couponCode = $quote->getCouponCode();
                // This will create one list if not created and will return onelist if its already created.
                /** @var OneList|null $oneList */
                $oneList = $this->basketHelper->get();
                $oneList = $this->basketHelper->setOneListQuote($quote, $oneList);
                if (!empty($couponCode)) {
                    $status = $this->basketHelper->setCouponCode($couponCode);
                    if (!is_object($status)) {
                        $quote->setCouponCode('');
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
                    $quote->setLsOnelistId($oneList->getId());
                    $quote->setLsPointsEarn($basketData->getPointsRewarded())->save();
                }
                if ($quote->getLsGiftCardAmountUsed() > 0 ||
                    $quote->getLsPointsSpent() > 0) {
                    $this->data->orderBalanceCheck(
                        $quote->getLsGiftCardNo(),
                        $quote->getLsGiftCardAmountUsed(),
                        $quote->getLsPointsSpent(),
                        $basketData
                    );
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
        return $this;
    }
}
