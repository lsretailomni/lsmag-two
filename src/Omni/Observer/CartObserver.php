<?php

namespace Ls\Omni\Observer;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\OneList;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use \LS\Omni\Helper\ItemHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * CartObserver Observer
 * This class is overriding in hospitality module
 */
class CartObserver implements ObserverInterface
{

    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * @var ItemHelper
     */
    public $itemHelper;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var Data
     */
    public $data;

    /**
     * @param BasketHelper $basketHelper
     * @param ItemHelper $itemHelper
     * @param LoggerInterface $logger
     * @param CheckoutSession $checkoutSession
     * @param LSR $LSR
     * @param Data $data
     */
    public function __construct(
        BasketHelper $basketHelper,
        ItemHelper $itemHelper,
        LoggerInterface $logger,
        CheckoutSession $checkoutSession,
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
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /*
          * Adding condition to only process if LSR is enabled.
          */
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            try {
                $salesQuoteItems = $observer->getItems();
                if (!empty($salesQuoteItems)) {
                    $salesQuoteItem = reset($salesQuoteItems);
                    $quote          = $this->basketHelper->getQuoteRepository()->get($salesQuoteItem->getQuoteId());
                } else {
                    $quote = $this->checkoutSession->getQuote();
                }
                // This will create one list if not created and will return onelist if its already created.
                /** @var OneList|null $oneList */
                $oneList = $this->basketHelper->get();
                // add items from the quote to the oneList and return the updated onelist
                $oneList = $this->basketHelper->setOneListQuote($quote, $oneList);
                if (count($quote->getAllItems()) == 0) {
                    $quote->setLsGiftCardAmountUsed(0);
                    $quote->setLsGiftCardNo(null);
                    $quote->setLsGiftCardPin(null);
                    $quote->setLsPointsSpent(0);
                    $quote->setLsPointsEarn(0);
                    $quote->setGrandTotal(0);
                    $quote->setBaseGrandTotal(0);
                    $this->basketHelper->quoteRepository->save($quote);
                    $this->basketHelper->setOneListCalculationInCheckoutSession(null);
                }
                $this->basketHelper->updateBasketAndSaveTotals($oneList, $quote);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
        return $this;
    }
}
