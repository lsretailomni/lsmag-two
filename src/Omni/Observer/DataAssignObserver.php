<?php

namespace Ls\Omni\Observer;

use Carbon\Carbon;
use \Ls\Core\Model\LSR;
use Magento\Checkout\Model\Session\Proxy;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\StoreHelper;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Framework\App\Request\Http;

/**
 * Class for assigning and validating different extension attribute values
 */
class DataAssignObserver implements ObserverInterface
{
    /**
     * @var Proxy
     */
    private $checkoutSession;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var BasketHelper
     */
    private $basketHelper;
    /**
     * @var QuoteIdMaskFactory
     */
    private QuoteIdMaskFactory $quoteIdMaskFactory;
    /**
     * @var StoreHelper
     */
    private StoreHelper $storeHelper;
    /**
     * @var LSR
     */
    private LSR $lsr;

    /**
     * @param Proxy $checkoutSession
     * @param Data $helper
     * @param BasketHelper $basketHelper
     * @param StoreHelper $storeHelper
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param LSR $lsr
     */
    public function __construct(
        Proxy $checkoutSession,
        Data $helper,
        BasketHelper $basketHelper,
        StoreHelper $storeHelper,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        Http $request,
        LSR $lsr
    ) {
        $this->checkoutSession      = $checkoutSession;
        $this->helper               = $helper;
        $this->basketHelper         = $basketHelper;
        $this->storeHelper          = $storeHelper;
        $this->quoteIdMaskFactory   = $quoteIdMaskFactory;
        $this->request              = $request;
        $this->lsr                  = $lsr;
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
        $giftCardNo         = $quote->getLsGiftCardNo();
        $giftCardAmountUsed = $quote->getLsGiftCardAmountUsed();
        $loyaltyPointsSpent = $quote->getLsPointsSpent();
        $errorMessage       = $this->helper->orderBalanceCheck(
            $giftCardNo,
            $giftCardAmountUsed,
            $loyaltyPointsSpent,
            $this->basketHelper->getBasketSessionValue(),
            false
        );

        //For click and collect validate cart item inventory in store and pickup date and
        // time with store opening hours
        if (!$errorMessage &&
            $quote->getShippingAddress()->getShippingMethod() == "clickandcollect_clickandcollect"
        ) {
            $quoteIdMask = $this->quoteIdMaskFactory->create();
            $maskedCartId      = $quoteIdMask->load(
                $quote->getId(),
                'quote_id'
            )->getMaskedId();

            $errorMessage = $this->validateClickAndCollectOrder(
                $quote,
                $maskedCartId,
                $quote->getCustomerId(),
                $quote->getStoreId(),
                $quote->getPickupStore()
            );
        } elseif ($quote->getPayment()->getMethod() == "ls_payment_method_pay_at_store") {
            $errorMessage = __('Pay at Store is not supported. Please select a different payment method.');
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

        $order->setLsPointsSpent($loyaltyPointsSpent);
        $order->setLsPointsEarn($quote->getLsPointsEarn());

        $order->setLsGiftCardAmountUsed($giftCardAmountUsed);
        $order->setLsGiftCardNo($giftCardNo);

        return $this;
    }

    /** For click and collect validate cart item inventory in store and pickup date and time
     *
     * @param $quote
     * @param $maskedCartId
     * @param $userId
     * @param $scopeId
     * @param $storeId
     * @return \Magento\Framework\Phrase|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException
     * @throws \Zend_Log_Exception
     */
    public function validateClickAndCollectOrder($quote, $maskedCartId, $userId, $scopeId, $storeId)
    {
        if ($this->request->getHeader("Content-Type") == "application/json"
            && strpos($this->request->getOriginalPathInfo(), "graphql") === true) {
            //Stock and Date range validation in graphql based on store configuration

            $stockInventoryCheckMsg     = ($this->lsr->getStoreConfig(
                LSR::LSR_GRAPHQL_STOCK_VALIDATION_ACTIVE,
                $this->lsr->getCurrentStoreId()
            )) ? $this->storeInventoryCheck($maskedCartId, $userId, $scopeId, $storeId) : '';

            $validatePickupDateRangeMsg = ($this->lsr->getStoreConfig(
                LSR::LSR_GRAPHQL_DATETIME_RANGE_VALIDATION_ACTIVE,
                $this->lsr->getCurrentStoreId()
            )) ? $this->validatePickupDateRange($quote, $storeId) : '';
        } else { //Stock and Date range validation in frontend based on store configuration

            $stockInventoryCheckMsg     = ($this->lsr->getStoreConfig(
                LSR::LSR_STOCK_VALIDATION_ACTIVE,
                $this->lsr->getCurrentStoreId()
            )) ? $this->storeInventoryCheck($maskedCartId, $userId, $scopeId, $storeId) : '';

            $validatePickupDateRangeMsg = ($this->lsr->getStoreConfig(
                LSR::LSR_DATETIME_RANGE_VALIDATION_ACTIVE,
                $this->lsr->getCurrentStoreId()
            )) ? $this->validatePickupDateRange($quote, $storeId) : '';
        }

        $validatePaymentMethod      = $this->validatePaymentMethod($quote, $storeId);

        return ($stockInventoryCheckMsg) ?: ( ($validatePickupDateRangeMsg) ? : $validatePaymentMethod );
    }

    /** To validate cart item inventory in store
     *
     * @param $maskedCartId
     * @param $userId
     * @param $scopeId
     * @param $storeId
     * @return \Magento\Framework\Phrase
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException
     */
    public function storeInventoryCheck($maskedCartId, $userId, $scopeId, $storeId)
    {
        $message = null;
        if (empty($storeId)) {
            $message = __('Please select a store to proceed.');
        }

        $stockCollection = $this->helper->fetchCartAndReturnStock(
            $maskedCartId,
            $userId,
            $scopeId,
            $storeId
        );

        if (!$stockCollection) {
            $message = __('Oops! Unable to do stock lookup currently.');
        }

        foreach ($stockCollection as $stock) {
            if (!$stock['status']) {
                $message = __('Unable to use selected shipping method since some or all of the cart items are not available in selected store.');
            }
        }
        return $message;
    }

    /**
     * Validate pickup date and time range
     *
     * @param $quote
     * @param $storeId
     * @return \Magento\Framework\Phrase
     * @throws \Zend_Log_Exception
     * @throws NoSuchEntityException
     */
    public function validatePickupDateRange($quote, $storeId)
    {
        $message = null;
        $validDateTime = false;
        if ($storeId && !empty($quote->getPickupDateTimeslot())) {
            $pickupDateTimeArr = explode(" ", $quote->getPickupDateTimeslot());

            $pickupTimeStamp = Carbon::parse($quote->getPickupDateTimeslot());
            $websiteId = $quote->getStoreId();
            $store = $this->storeHelper->getStore($websiteId, $storeId);
            $storeHoursArray = $this->storeHelper->formatDateTimeSlotsValues(
                $store->getStoreHours()
            );

            foreach ($storeHoursArray as $date => $hoursArr) {
                $openHoursCnt = count($hoursArr);
                if ($date == "Today") {
                    $date = $this->storeHelper->getCurrentDate();
                }

                if ($openHoursCnt > 0 && $date == $pickupDateTimeArr[0]) {
                    $validDateTime = true;
                    $storeOpeningTimeStamp = Carbon::parse($date." ".$hoursArr[0]);
                    $storeClosingTimeStamp = Carbon::parse($date." ".$hoursArr[$openHoursCnt-1]);

                    if (!$pickupTimeStamp->between($storeOpeningTimeStamp, $storeClosingTimeStamp, true)) {
                        $validDateTime = false;
                    }
                    break;
                }
            }
        }

        if (!$validDateTime) {
            $message = __('Please select a date & time within store opening hours.');
        }

        return $message;
    }

    /**
     * Validate click and collect payment methods with store configuration values
     *
     * @param $quote
     * @param $storeId
     * @return \Magento\Framework\Phrase
     * @throws NoSuchEntityException
     */
    public function validatePaymentMethod($quote, $storeId)
    {
        $shippingMethod        = $quote->getShippingAddress()->getShippingMethod();
        $selectedPaymentMethod = $quote->getPayment()->getMethod();
        $message = null;
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
