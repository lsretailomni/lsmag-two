<?php
declare(strict_types=1);

namespace Ls\Omni\Observer;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\RootMobileTransaction;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * This observer is responsible for basket integration
 */
class CartObserver implements ObserverInterface
{
    /**
     * @param BasketHelper $basketHelper
     * @param LoggerInterface $logger
     * @param CheckoutSession $checkoutSession
     * @param LSR $lsr
     */
    public function __construct(
        public BasketHelper $basketHelper,
        public LoggerInterface $logger,
        public CheckoutSession $checkoutSession,
        public LSR $lsr
    ) {
    }

    /**
     * Entry point for the observer
     *
     * @param Observer $observer
     * @return $this
     * @throws NoSuchEntityException
     * @throws LocalizedException|GuzzleException
     */
    public function execute(Observer $observer)
    {
        try {
            if ($this->lsr->isLSR(
                $this->lsr->getCurrentStoreId(),
                false,
                $this->lsr->getBasketIntegrationOnFrontend()
            )) {
                $salesQuoteItems = $observer->getItems();
                if (!empty($salesQuoteItems)) {
                    $salesQuoteItem = reset($salesQuoteItems);
                    $quote = $this->basketHelper->getQuoteRepository()->get($salesQuoteItem->getQuoteId());
                } else {
                    $quote = $this->checkoutSession->getQuote();
                }
                // This will create one list if not created and will return onelist if its already created.
                /** @var RootMobileTransaction|null $oneList */
                $oneList = $this->basketHelper->get();
                // add items from the quote to the oneList and return the updated onelist
                $oneList = $this->basketHelper->setOneListQuote($quote, $oneList);
                if (count($quote->getAllItems()) == 0) {
                    $quote->setLsGiftCardAmountUsed(0);
                    $quote->setLsGiftCardNo(null);
                    $quote->setLsGiftCardPin(null);
                    $quote->setLsPointsSpent(0);
                    $quote->setLsPointsEarn(0);
                    $quote->setSubtotal(0);
                    $quote->setBaseSubtotal(0);
                    $quote->setGrandTotal(0);
                    $quote->setBaseGrandTotal(0);
                    $this->basketHelper->quoteRepository->save($quote);
                    $this->basketHelper->setOneListCalculationInCheckoutSession(null);
                }
                $this->basketHelper->updateBasketAndSaveTotals($oneList, $quote);
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $this;
    }
}
