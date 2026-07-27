<?php
declare(strict_types=1);

namespace Ls\Omni\Plugin\AdminOrder;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Sales\Model\AdminOrder\Create;
use Psr\Log\LoggerInterface;

/**
 * Interceptor to create oneList while creating order from admin
 */
class CreatePlugin
{
    /**
     * @param BasketHelper $basketHelper
     * @param ItemHelper $itemHelper
     * @param LoggerInterface $logger
     * @param LSR $lsr
     * @param Data $data
     * @param OrderHelper $orderHelper
     * @param Quote $backendQuoteSession
     */
    public function __construct(
        public BasketHelper $basketHelper,
        public ItemHelper $itemHelper,
        public LoggerInterface $logger,
        public LSR $lsr,
        public Data $data,
        public OrderHelper $orderHelper,
        public Quote $backendQuoteSession
    ) {
    }

    /**
     * Before plugin to clear the stopCalcRowTotal flag before the quote's totals are collected
     *
     * Magento\Sales\Model\AdminOrder\Create::saveQuote() calls $this->getQuote()->collectTotals()
     * internally before returning, so clearing the flag only in the after plugin is one step too
     * late for the totals collected during the current request. Clearing it here, before
     * saveQuote() runs, ensures the custom price set by the admin is not discarded by
     * Ls\Omni\Plugin\Quote\Item\AbstractItemPlugin::afterGetCalculationPriceOriginal().
     *
     * @param Create $subject
     * @return void
     */
    public function beforeSaveQuote(Create $subject): void
    {
        if (!$subject->getQuote()->getId() || empty($subject->getQuote()->getAllVisibleItems())) {
            return;
        }

        $quote = $subject->getQuote();
        if ($this->lsr->isLSR($quote->getStoreId())
            && $this->lsr->isAdminOrderCustomPriceActive($quote->getStoreId())
        ) {
            $this->basketHelper->getCheckoutSession()->unsetData('stopCalcRowTotal');
        }
    }

    /**
     * After plugin to create oneList after quote is saved
     *
     * @param Create $subject
     * @param $result
     * @return mixed
     * @throws GuzzleException|AlreadyExistsException
     */
    public function afterSaveQuote(
        Create $subject,
        $result
    ) {
        if (!$subject->getQuote()->getId() || empty($subject->getQuote()->getAllVisibleItems())) {
            return $result;
        }

        $quote = $subject->getQuote();
        $this->orderHelper->storeManager->setCurrentStore($quote->getStoreId());
        $this->basketHelper->setCorrectStoreIdInCheckoutSession($quote->getStoreId());
        $this->basketHelper->getCustomerSession()->setCustomerId($quote->getCustomer()->getId());
        if (!empty($quote)) {
            $this->basketHelper->getCheckoutSession()->setQuoteId($quote->getId());
            $quote->setIsActive(1);
            $this->itemHelper->quoteResourceModel->save($quote);
            $this->basketHelper->getCustomerSession()->setCustomerId($quote->getCustomer()->getId());
        }

        try {
            if ($this->lsr->isLSR($quote->getStoreId())
                && !$this->lsr->isAdminOrderCustomPriceActive($quote->getStoreId())
            ) {
                $couponCode = $quote->getCouponCode();
                $webStore = $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_STORE, $quote->getStore()->getWebsiteId());
                $this->basketHelper->storeId = $webStore;
                $oneList = $this->basketHelper->getOneListAdmin(
                    $quote->getCustomerEmail(),
                    $quote->getStore()->getWebsiteId(),
                    false
                );

                $oneList = $this->basketHelper->setOneListQuote($quote, $oneList);
                if (!empty($couponCode)) {
                    $status = $this->basketHelper->setCouponCode($couponCode);
                    if (!is_object($status)) {
                        $quote->setCouponCode('');
                    }
                }
                if (count($quote->getAllItems()) == 0) {
                    $quote->setLsPosDataEntries(null);
                    $quote->setLsPointsSpent(0);
                    $quote->setLsPointsEarn(0);
                    $quote->setGrandTotal(0);
                    $quote->setBaseGrandTotal(0);
                    $this->basketHelper->quoteRepository->save($quote);
                }
                $basketData = $this->basketHelper->update($oneList);
                $quote = $this->basketHelper->getCurrentQuote();
                $this->itemHelper->setDiscountedPricesForItems($quote, $basketData, 2);
                $this->backendQuoteSession->_resetState();
                if (!empty($basketData) && method_exists($basketData, 'getPointsRewarded')) {
                    $quote->setLsPointsEarn($basketData->getPointsRewarded())->save();
                }
                if ((float)array_sum(array_column(json_decode((string)$quote->getLsPosDataEntries(), true) ?? [], 'amount')) > 0 ||
                    $quote->getLsPointsSpent() > 0) {
                    $this->data->orderBalanceCheck(
                        $quote->getLsPosDataEntries(),
                        (float)array_sum(array_column(json_decode((string)$quote->getLsPosDataEntries(), true) ?? [], 'amount')),
                        $quote->getLsPointsSpent(),
                        $basketData
                    );
                }
            } elseif ($this->lsr->isAdminOrderCustomPriceActive($quote->getStoreId())) {
                $this->basketHelper->getCheckoutSession()->unsetData('stopCalcRowTotal');
                // Magento's own native item pricing/totals collection (already run by
                // Create::saveQuote()'s own internal collectTotals() call) is trusted as-is for
                // Price/RowTotal - only the secondary base-currency/tax-inclusive fields it
                // doesn't itself keep in sync with a custom price need this explicit correction.
                $this->itemHelper->setBaseCurrencyFieldsFromItemPrice($quote);
                $this->itemHelper->quoteResourceModel->save($quote);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }
}
