<?php
declare(strict_types=1);

namespace Ls\Omni\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\GiftCardHelper;
use \Ls\Omni\Helper\StoreHelper;
use \Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Helper\VendorSourcingHelper;
use Ls\Omni\Model\VendorSourcing\CheckoutFallbackService;
use Ls\Omni\Model\VendorSourcing\ItemSourcingVerdict;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\App\Request\Http;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;

/**
 * Class for assigning and validating different extension attribute values
 */
class DataAssignObserver implements ObserverInterface
{
    /**
     * @param Data $helper
     * @param BasketHelper $basketHelper
     * @param StoreHelper $storeHelper
     * @param Http $request
     * @param LSR $lsr
     * @param QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId
     * @param CheckoutSession $checkoutSession
     * @param CheckoutFallbackService $checkoutFallbackService
     */
    public function __construct(
        public Data $helper,
        public BasketHelper $basketHelper,
        public StoreHelper $storeHelper,
        public Http $request,
        public LSR $lsr,
        public GiftCardHelper $giftCardHelper,
        public QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId,
        public CheckoutSession $checkoutSession,
        public CheckoutFallbackService $checkoutFallbackService
    ) {
    }

    /***
     * For setting quote values
     *
     * @param Observer $observer
     * @return $this|void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws ValidatorException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException
     * @throws \Zend_Log_Exception
     */
    public function execute(Observer $observer)
    {
        $quote              = $observer->getQuote();
        // Gift card amount is now computed from the unified ls_pos_data_entries column
        $giftCardAmountUsed = $this->giftCardHelper->getGiftCardTotal($quote->getLsPosDataEntries());
        $loyaltyPointsSpent = $quote->getLsPointsSpent();
        $errorMessage       = $this->helper->orderBalanceCheck(
            null,
            $giftCardAmountUsed,
            $loyaltyPointsSpent,
            $this->basketHelper->getBasketSessionValue(),
            false
        );

        // For click and collect validate cart item inventory in store and pickup date and
        // time with store opening hours
        if (!$errorMessage &&
            $quote->getShippingAddress()->getShippingMethod() == "clickandcollect_clickandcollect"
        ) {
            $maskedCartId = $this->quoteIdToMaskedQuoteId->execute((int) $quote->getId());

            $errorMessage = $this->validateClickAndCollectOrder(
                $quote,
                $maskedCartId,
                $quote->getCustomerId(),
                $quote->getStoreId(),
                $quote->getPickupStore()
            );
        } else {
            if ($quote->getPayment()->getMethod() == "ls_payment_method_pay_at_store") {
                $errorMessage = __('Pay at Store is not supported. Please select a different payment method.');
            }

            if (!$errorMessage) {
                $errorMessage = $this->validateDeliveryOrder($quote);
            }
        }

        if ($errorMessage) {
            throw new ValidatorException($errorMessage);
        }
        $order = $observer->getOrder();

        if ($quote->getPickupDateTimeslot()) {
            $order->setPickupDateTimeslot($quote->getPickupDateTimeslot());
        }

        if ($quote->getPickupStore()) {
            $order->setPickupStore($quote->getPickupStore());
        }

        if (!empty($quote->getCouponCode())) {
            $order->setCouponCode($quote->getCouponCode());
        }

        if (!empty($quote->getLsSubscriptionId())) {
            $order->setLsSubscriptionId($quote->getLsSubscriptionId());
        }

        $order->setLsPointsSpent($loyaltyPointsSpent);
        $order->setLsPointsEarn($quote->getLsPointsEarn());
        $order->setLsDiscountAmount($quote->getLsDiscountAmount());

        // Unified entry column (gift cards + vouchers in one JSON column)
        $order->setLsPosDataEntries($quote->getLsPosDataEntries());

        // Vendor-sourcing order-submission (ticket 89364, follow-on to 89360): the per-item
        // verdicts produced above (evaluate()/evaluateDelivery(), via the checks in
        // validateClickAndCollectOrder()/validateDeliveryOrder()) are no longer stamped onto the
        // Order here as transient 'ls_vendor_sourced_lines' data - that approach did not survive
        // OrderObserver's other two registered events (checkout_onepage_controller_success_action,
        // adyen_payment_process_resulturl_after), both of which reload the Order from the database
        // on a separate HTTP request, silently losing this data for online/redirect payment
        // methods (bug fix, post-merge review, Bug B). OrderHelper::prepareOrder() now recomputes
        // the determination fresh off the order's quote every time it runs instead - see that
        // method and CheckoutFallbackService::buildVendorSourcedLinesMap().

        return $this;
    }

    /** For click and collect validate cart item inventory in store and pickup date and time
     *
     * @param $quote
     * @param $maskedCartId
     * @param $userId
     * @param $scopeId
     * @param $storeId
     * @return Phrase
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException
     * @throws \Zend_Log_Exception
     */
    public function validateClickAndCollectOrder($quote, $maskedCartId, $userId, $scopeId, $storeId)
    {
        if (str_contains($this->request->getOriginalPathInfo(), "graphql")) {
            //Stock validation in graphql based on store configuration

            $stockInventoryCheckMsg = ($this->lsr->getStoreConfig(
                LSR::LSR_GRAPHQL_STOCK_VALIDATION_ACTIVE,
                $this->lsr->getCurrentStoreId()
            )) ? $this->storeInventoryCheck($maskedCartId, $userId, $scopeId, $storeId) : '';

        } else { //Stock validation in frontend based on store configuration

            $stockInventoryCheckMsg = ($this->lsr->getStoreConfig(
                LSR::LSR_STOCK_VALIDATION_ACTIVE,
                $this->lsr->getCurrentStoreId()
            )) ? $this->storeInventoryCheck($maskedCartId, $userId, $scopeId, $storeId, $quote) : '';
        }

        $validatePaymentMethod = $this->validatePaymentMethod($quote);

        return $stockInventoryCheckMsg ?? $validatePaymentMethod;
    }

    /** To validate cart item inventory in store
     *
     * @param $maskedCartId
     * @param $userId
     * @param $scopeId
     * @param $storeId
     * @param null $quote
     * @return Phrase|null
     * @throws NoSuchEntityException
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     */
    public function storeInventoryCheck($maskedCartId, $userId, $scopeId, $storeId, $quote = null)
    {
        if (empty($storeId)) {
            return __('Please select a store to proceed.');
        }

        $stockCollection = $this->helper->fetchCartAndReturnStock(
            $maskedCartId,
            $userId,
            $scopeId,
            $storeId,
            $quote
        );

        if (empty($this->checkoutSession->getNoManageStock())) {
            // Only a genuine null (fetchCartAndReturnStock() couldn't perform the lookup at all)
            // is a failure here - an empty array means there was simply nothing left needing a
            // per-store check (e.g. a cart that is entirely vendor-sourced/unmanaged items once
            // ticket 89358's manage_stock=0 gating lands), which is a legitimate "nothing to
            // block on" outcome, not an error (bug fix, post-merge review, Bug A).
            if ($stockCollection === null) {
                return __('Oops! Unable to do stock lookup currently.');
            }

            // Bug A fix (post-merge review, "C&C trigger gap"): once vendor-sourced products get
            // manage_stock=0 (ticket 89358), they are silently excluded from $stockCollection
            // altogether by StockHelper::getGivenItemsStockInGivenStore()'s per-item gate - never
            // even a status=0 row - so hasZeroStockRow($stockCollection) alone would never see
            // them and evaluate() would never run for an all-vendor-sourced (or vendor-sourced +
            // all-in-stock) cart. Passing the quote's items through lets evaluate() independently
            // re-discover and evaluate such items via the ungated stock check. $quote is only
            // passed on the non-GraphQL call path today (see validateClickAndCollectOrder()) -
            // gracefully skip this additional check when it's null, same as before.
            $quoteItems = $quote ? $quote->getAllVisibleItems() : [];

            if ($this->hasZeroStockRow($stockCollection) || !empty($quoteItems)) {
                $fallbackResult = $this->checkoutFallbackService->evaluate(
                    $stockCollection,
                    VendorSourcingHelper::TYPE_COLLECT,
                    $quoteItems
                );

                if ($fallbackResult->hasBlockedItems()) {
                    return $this->buildBlockedStockMessage($fallbackResult->getBlockedVerdicts());
                }
            }
        }

        return null;
    }

    /**
     * Validate delivery/flatrate orders for vendor-sourcing fallback (FR7/FR9).
     * No-ops immediately (zero extra calls) if the cart contains no vendor-sourcing-eligible item.
     *
     * @param $quote
     * @return Phrase|null
     */
    public function validateDeliveryOrder($quote)
    {
        $items = $quote->getAllVisibleItems();

        $fallbackResult = $this->checkoutFallbackService->evaluateDelivery($items);

        if ($fallbackResult->hasBlockedItems()) {
            return $this->buildBlockedStockMessage($fallbackResult->getBlockedVerdicts());
        }

        return null;
    }

    /**
     * Whether any row in the given stock collection is out of stock
     *
     * @param array $stockCollection
     * @return bool
     */
    private function hasZeroStockRow(array $stockCollection): bool
    {
        foreach ($stockCollection as $stock) {
            if (!$stock['status']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Combine the per-item blocked verdict messages into a single Phrase
     *
     * @param ItemSourcingVerdict[] $blockedVerdicts
     * @return Phrase
     */
    private function buildBlockedStockMessage(array $blockedVerdicts): Phrase
    {
        $messages = array_map(
            static fn (ItemSourcingVerdict $verdict): string => (string)$verdict->getMessage(),
            $blockedVerdicts
        );

        return __(implode("\n", $messages));
    }

    /**
     * Validate click and collect payment methods with store configuration values
     *
     * @param $quote
     * @return \Magento\Framework\Phrase
     * @throws NoSuchEntityException
     */
    public function validatePaymentMethod($quote)
    {
        $shippingMethod        = $quote->getShippingAddress()->getShippingMethod();
        $selectedPaymentMethod = $quote->getPayment()->getMethod();
        $message               = null;
        if ($shippingMethod == "clickandcollect_clickandcollect") {
            $paymentOptionArray = explode(
                ',',
                $this->lsr->getStoreConfig(LSR::SC_PAYMENT_OPTION, $this->lsr->getCurrentStoreId())
            );

            if (!in_array($selectedPaymentMethod, $paymentOptionArray)) {
                $message = __('Selected payment method is not supported. Please select from allowed payment methods.');
            }
        }

        return $message;
    }
}
