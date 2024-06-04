<?php

namespace Ls\Omni\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\StoreHelper;
use \Ls\Omni\Helper\BasketHelper;
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
     * @var Data
     */
    private $helper;

    /**
     * @var BasketHelper
     */
    private $basketHelper;
    /**
     * @var StoreHelper
     */
    private StoreHelper $storeHelper;
    /**
     * @var Http
     */
    private Http $request;
    /**
     * @var LSR
     */
    private LSR $lsr;
    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    private $quoteIdToMaskedQuoteId;

    /**
     * @param Data $helper
     * @param BasketHelper $basketHelper
     * @param StoreHelper $storeHelper
     * @param Http $request
     * @param LSR $lsr
     * @param QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId
     */
    public function __construct(
        Data $helper,
        BasketHelper $basketHelper,
        StoreHelper $storeHelper,
        Http $request,
        LSR $lsr,
        QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId
    ) {
        $this->helper                 = $helper;
        $this->basketHelper           = $basketHelper;
        $this->storeHelper            = $storeHelper;
        $this->request                = $request;
        $this->lsr                    = $lsr;
        $this->quoteIdToMaskedQuoteId = $quoteIdToMaskedQuoteId;
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
        $giftCardPin        = $quote->getLsGiftCardPin();
        $giftCardCnyFactor        = $quote->getLsGiftCardCnyFactor();
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
            $maskedCartId = $this->quoteIdToMaskedQuoteId->execute($quote->getId());

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
        $order->setLsGiftCardPin($giftCardPin);
        $order->setLsGiftCardCnyFactor($giftCardCnyFactor);

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
     * @return Phrase
     * @throws NoSuchEntityException
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     */
    public function storeInventoryCheck($maskedCartId, $userId, $scopeId, $storeId, $quote = null)
    {
        $message = null;
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
