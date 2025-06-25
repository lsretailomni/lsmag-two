<?php
declare(strict_types=1);

namespace Ls\Omni\Helper;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\GetDataEntryBalanceV2;
use \Ls\Omni\Client\Ecommerce\Entity\GiftCard;
use \Ls\Omni\Client\Ecommerce\Operation;
use Magento\Framework\Currency;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class GiftCardHelper for gift card support
 */
class GiftCardHelper extends AbstractHelperOmni
{
    /**
     * For getting gift card balance
     *
     * @param string $giftCardNo
     * @param ?string $giftCardPin
     * @return float|GiftCard|null
     */
    public function getGiftCardBalance(string $giftCardNo, ?string $giftCardPin = null)
    {
        $response = null;
        $giftCardPin = empty($giftCardPin) ? 0 : $giftCardPin;
        $operation = $this->createInstance(
            Operation\GetDataEntryBalanceV2::class
        );
        $operationInput = [
            GetDataEntryBalanceV2::ENTRY_TYPE => 'GIFTCARDNO',
            GetDataEntryBalanceV2::ENTRY_CODE => $giftCardNo,
            GetDataEntryBalanceV2::PIN =>  $giftCardPin,
        ];

        $operation->setOperationInput($operationInput);
        try {
            $responseData = $operation->execute();
            $response = $responseData && $responseData->getResponsecode() == '0000' &&
            $responseData->getGetdataentrybalancexml() &&
            !empty(current($responseData->getGetdataentrybalancexml()->getPosdataentry())->getData()) ?
                current($responseData->getGetdataentrybalancexml()->getPosdataentry()) : null;

        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        $this->basketHelper->setGiftCardResponseInCheckoutSession($response);

        return $response;
    }

    /**
     * Validate gift card amount is valid with order total
     *
     * @param float $grandTotal
     * @param float $giftCardAmount
     * @param float $giftCardBalanceAmount
     * @return bool
     */
    public function isGiftCardAmountValid(float $grandTotal, float $giftCardAmount, float $giftCardBalanceAmount)
    {
        return $giftCardAmount <= $grandTotal && $giftCardAmount <= $giftCardBalanceAmount;
    }

    /**
     * Check to see if gift card is expired
     *
     * @param Entity\POSDataEntry $giftCardResponse
     * @return bool
     * @throws Exception
     */
    public function isGiftCardExpired(Entity\POSDataEntry $giftCardResponse)
    {
        if (!$giftCardResponse->getExpirydate()) {
            return false;
        }
        $date = new \DateTime($giftCardResponse->getExpirydate());
        $now = new \DateTime();

        return $date < $now;
    }

    /**
     * Check if gift card is enabled
     *
     * @param string $area
     * @return bool
     * @throws NoSuchEntityException|GuzzleException
     */
    public function isGiftCardEnabled(string $area)
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
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isPinCodeFieldEnable()
    {
        return (bool) $this->lsr->getStoreConfig(LSR::LS_GIFTCARD_SHOW_PIN_CODE_FIELD, $this->lsr->getCurrentStoreId());
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
        return str_replace(
            ',',
            '.',
            $this->currencyHelper->format(
                $value,
                ['display' => Currency::NO_SYMBOL],
                false
            )
        );
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
     * @param Entity\POSDataEntry $giftCardResponse
     * @return array
     * @throws NoSuchEntityException|GuzzleException
     */
    public function getConvertedGiftCardBalance(Entity\POSDataEntry $giftCardResponse)
    {
        $pointRate = $storeCurrencyPointRate = $giftCardPointRate = $quotePointRate = 0;
        $currency = $giftCardResponse->getCurrencycode();

        if ($this->lsr->getStoreCurrencyCode() == $this->giftCardHelper->getLocalCurrencyCode()) {
            $pointRate = $this->loyaltyHelper->getPointRate(null, $giftCardResponse->getCurrencycode());
            $quotePointRate = $pointRate;
            $case = 1;
        } elseif ($this->lsr->getStoreCurrencyCode() != $this->giftCardHelper->getLocalCurrencyCode()) {
            $storeCurrencyPointRate = $this->loyaltyHelper->getPointRate(null, $this->lsr->getStoreCurrencyCode());
            $giftCardPointRate = $this->loyaltyHelper->getPointRate(null, $giftCardResponse->getCurrencycode());
            $quotePointRate = $giftCardPointRate;
            $case = 2;
        }

        if ($pointRate > 0 || ($storeCurrencyPointRate > 0 && $giftCardPointRate > 0)) {
            $giftCardBalanceAmount = match ($case) {
                1 => $giftCardResponse->getBalance() / $pointRate,
                2 => ($giftCardResponse->getBalance() / $giftCardPointRate) * $storeCurrencyPointRate,
                default => $giftCardResponse->getBalance(),
            };
            $currency = $giftCardResponse->getCurrencycode();
        } else {
            $giftCardBalanceAmount = $giftCardResponse->getBalance();
        }

        return [
            'gift_card_balance_amount' => $giftCardBalanceAmount,
            'quote_point_rate' => $quotePointRate,
            'gift_card_currency' => $currency
        ];
    }
}
