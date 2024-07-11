<?php

namespace Ls\Omni\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\GiftCard;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Omni\Model\Cache\Type;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Currency;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;

/**
 * Class GiftCardHelper for gift card support
 */
class GiftCardHelper extends AbstractHelperOmni
{

    const SERVICE_TYPE = 'ecommerce';

    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    /**
     * @var Filesystem
     */
    public $filesystem;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * For getting gift card balance
     *
     * @param $giftCardNo
     * @param $giftCardPin
     * @return float|GiftCard|null
     */
    public function getGiftCardBalance($giftCardNo, $giftCardPin = null)
    {
        $response        = null;
        $getExchangeRate = 0;
        // @codingStandardsIgnoreStart
        $request = new Operation\GiftCardGetBalance();
        $entity  = new Entity\GiftCardGetBalance();
        $entity->setCardNo($giftCardNo);
        if ($giftCardPin) {
            $entity->setPin($giftCardPin);
        }
        // @codingStandardsIgnoreEnd
        try {
            $responseData = $request->execute($entity);
            $response     = $responseData ? $responseData->getResult() : $response;

        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        $this->checkoutSession->setGiftCard($response);
        return $response;
    }

    /**
     * Validate gift card amount is valid with order total
     *
     * @param $grandTotal
     * @param $giftCardAmount
     * @param $giftCardBalanceAmount
     * @return bool
     */
    public function isGiftCardAmountValid($grandTotal, $giftCardAmount, $giftCardBalanceAmount)
    {
        if ($giftCardAmount <= $grandTotal && $giftCardAmount <= $giftCardBalanceAmount) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if gift card is enabled
     *
     * @param $area
     * @return string
     * @throws NoSuchEntityException
     */
    public function isGiftCardEnabled($area)
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            if ($area == 'cart') {
                return ($this->lsr->getStoreConfig(
                        LSR::LS_ENABLE_GIFTCARD_ELEMENTS,
                        $this->lsr->getCurrentStoreId()
                    ) && $this->lsr->getStoreConfig(
                        LSR::LS_GIFTCARD_SHOW_ON_CART,
                        $this->lsr->getCurrentStoreId()
                    )
                );
            }
            return ($this->lsr->getStoreConfig(
                    LSR::LS_ENABLE_GIFTCARD_ELEMENTS,
                    $this->lsr->getCurrentStoreId()
                ) && $this->lsr->getStoreConfig(
                    LSR::LS_GIFTCARD_SHOW_ON_CHECKOUT,
                    $this->lsr->getCurrentStoreId()
                )
            );
        } else {
            return false;
        }
    }

    /**
     * Check pin code field in enable or not in gift card
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function isPinCodeFieldEnable()
    {
        return $this->lsr->getStoreConfig(LSR::LS_GIFTCARD_SHOW_PIN_CODE_FIELD, $this->lsr->getCurrentStoreId());
    }

    /**
     * Get currency exchange rate based on store currency or gift card currency passed in param.
     *
     * @param $giftCardCurrency
     * @param $storeId
     * @return false|Entity\GetPointRateResponse|\Ls\Omni\Client\ResponseInterface|null
     * @throws NoSuchEntityException
     */
    public function getPointRate($giftCardCurrency = null, $storeId = null)
    {
        if (!$storeId) {
            $storeId = $this->lsr->getCurrentStoreId();
        }

        $response        = null;
        $getExchangeRate = false;

        if ($this->lsr->isLSR($storeId) && $this->isEnabledGiftCard()) {
            $cacheId = LSR::POINTRATE . $storeId."_".$giftCardCurrency;
            $response = $this->cacheHelper->getCachedContent($cacheId);

            if ($response !== false) {
                return $this->formatValue($response);
            }

            if (!empty($giftCardCurrency)) {
                $getExchangeRate = ($giftCardCurrency != $this->lsr->getStoreCurrencyCode()) ? true : false;
            }

            // @codingStandardsIgnoreStart
            $request = new Operation\GetPointRate();
            $entity = new Entity\GetPointRate();
            // @codingStandardsIgnoreEnd

            if ($getExchangeRate) {
                $entity->setCurrency($giftCardCurrency);
            } else {
                $entity->setCurrency($this->lsr->getStoreCurrencyCode());
            }

            try {
                $response = $request->execute($entity);
            } catch (Exception $e) {
                $this->_logger->error($e->getMessage());
            }
            if (!empty($response)) {
                //$currencyFactor = $response->getResult();
                //$exchangeRate   = 1 / $currencyFactor;

                $this->cacheHelper->persistContentInCache(
                    $cacheId,
                    $response->getResult(),
                    [Type::CACHE_TAG],
                    86400
                );

                return $this->formatValue($response->getResult());
            }
        }
        return $response;
    }

    /**
     * To check if gift card elements are enabled
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function isEnabledGiftCard()
    {
        return $this->lsr->getStoreConfig(
            LSR::LS_ENABLE_GIFTCARD_ELEMENTS,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * Format value to two decimal places
     *
     * @param float $value
     * @return string
     */
    public function formatValue($value)
    {
        return $this->currencyHelper->format($value, ['display' => Currency::NO_SYMBOL], false);
    }

    /**
     * Get Local currency code from config
     *
     * @return null|string
     * @throws NoSuchEntityException
     */
    public function getLocalCurrencyCode()
    {
        return $this->lsr->getStoreConfig(
            LSR::SC_SERVICE_LCY_CODE,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * Get gift card balance amount after currency conversion and the currency factor of gift card currency
     *
     * @param $giftCardResponse
     * @return array
     * @throws NoSuchEntityException
     */
    public function getConvertedGiftCardBalance($giftCardResponse)
    {
        $pointRate = $storeCurrencyPointRate = $giftCardPointRate = $quotePointRate = 0;
        $currency  = $giftCardResponse->getCurrencyCode();
        if ($this->lsr->getStoreCurrencyCode() == $this->giftCardHelper->getLocalCurrencyCode()) {
            $pointRate      = $this->giftCardHelper->getPointRate($giftCardResponse->getCurrencyCode());
            $quotePointRate = $pointRate;
            $case           = 1;
        } elseif ($this->lsr->getStoreCurrencyCode() != $this->giftCardHelper->getLocalCurrencyCode()) {
            $storeCurrencyPointRate = $this->giftCardHelper->getPointRate($this->lsr->getStoreCurrencyCode());
            $giftCardPointRate      = $this->giftCardHelper->getPointRate($giftCardResponse->getCurrencyCode());
            $quotePointRate         = $giftCardPointRate;
            $case                   = 2;
        }

        if ($pointRate > 0 || ($storeCurrencyPointRate > 0 && $giftCardPointRate > 0)) {
            $giftCardBalanceAmount = match ($case) {
                1 => $giftCardResponse->getBalance() / $pointRate,
                2 => ($giftCardResponse->getBalance() / $giftCardPointRate) * $storeCurrencyPointRate,
                default => $giftCardResponse->getBalance(),
            };
            $currency = $giftCardResponse->getCurrencyCode();
        } else {
            $giftCardBalanceAmount = $giftCardResponse->getBalance();
        }

        return [
            'gift_card_balance_amount' => $giftCardBalanceAmount,
            'quote_point_rate'         => $quotePointRate,
            'gift_card_currency'       => $currency
        ];
    }
}
