<?php
declare(strict_types=1);

namespace Ls\Omni\Helper;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\CentralEcommerce\Entity\GetDataEntryBalanceV2;
use \Ls\Omni\Client\CentralEcommerce\Entity\POSDataEntry;
use Magento\Framework\Currency;
use Magento\Framework\Exception\LocalizedException;
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
     * @param string $entryType Entry type for LS Central balance API (e.g. GIFTCARDNO, INCOMEACCOUNT)
     * @return float|POSDataEntry|null
     */
    public function getGiftCardBalance(string $giftCardNo, ?string $giftCardPin = null, string $entryType = 'GIFTCARDNO')
    {
        $response = null;
        $giftCardPin = empty($giftCardPin) ? 0 : $giftCardPin;
        $operation = $this->createInstance(
            \Ls\Omni\Client\CentralEcommerce\Operation\GetDataEntryBalanceV2::class
        );
        $operationInput = [
            GetDataEntryBalanceV2::ENTRY_TYPE => $entryType,
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
     * @param POSDataEntry $giftCardResponse
     * @return bool
     * @throws Exception
     */
    public function isGiftCardExpired(POSDataEntry $giftCardResponse)
    {
        if ($giftCardResponse->getExpirydate() == '0001-01-01') {
            return false;
        }
        $date = new \DateTime($giftCardResponse->getExpirydate());
        $now = new \DateTime();

        return $date < $now;
    }

    /**
     * Decode JSON-encoded multi-gift-card list stored in ls_gift_card_no.
     * Returns array of ['entry_no', 'pin_code', 'amount', 'currency_factor', 'currency_code'].
     *
     * @param string|null $raw
     * @return array
     */
    public function decodeGiftCards(?string $raw): array
    {
        if (empty($raw)) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Encode gift card list array to JSON for storage.
     *
     * @param array $cards
     * @return string
     */
    public function encodeGiftCards(array $cards): string
    {
        return json_encode($cards);
    }

    /**
     * Check if given code is already in the stored gift card JSON list.
     *
     * @param string|null $raw
     * @param string $no
     * @return bool
     */
    public function isGiftCardAlreadyApplied(?string $raw, string $no): bool
    {
        foreach ($this->decodeGiftCards($raw) as $card) {
            if (($card['entry_no'] ?? '') === $no) {
                return true;
            }
        }
        return false;
    }

    // -------------------------------------------------------------------------
    // Unified entry column helpers (ls_pos_data_entries stores both gift cards and
    // vouchers as a JSON array; each entry has {entry_type, entry_no, pin_code, amount,
    // currency_code, currency_factor, tender_type})
    // -------------------------------------------------------------------------

    /**
     * Decode all entries from the unified ls_pos_data_entries column.
     */
    public function decodeEntries(?string $raw): array
    {
        if (empty($raw)) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Encode entries array to JSON for storage in ls_pos_data_entries.
     */
    public function encodeEntries(array $entries): string
    {
        return json_encode(array_values($entries));
    }

    /**
     * Get only GIFTCARDNO entries from the unified column.
     */
    public function getGiftCardEntries(?string $raw): array
    {
        return array_values(array_filter(
            $this->decodeEntries($raw),
            fn($e) => strtoupper($e['entry_type'] ?? '') === 'GIFTCARDNO'
        ));
    }

    /**
     * Get only non-GIFTCARDNO (voucher) entries from the unified column.
     */
    public function getVoucherEntries(?string $raw): array
    {
        return array_values(array_filter(
            $this->decodeEntries($raw),
            fn($e) => strtoupper($e['entry_type'] ?? '') !== 'GIFTCARDNO'
        ));
    }

    /**
     * Sum amounts for a given set of entries.
     */
    public function sumAmounts(array $entries): float
    {
        return (float)array_sum(array_column($entries, 'amount'));
    }

    /**
     * Get total gift card amount from unified column.
     */
    public function getGiftCardTotal(?string $raw): float
    {
        return $this->sumAmounts($this->getGiftCardEntries($raw));
    }

    /**
     * Get total voucher amount from unified column.
     */
    public function getVoucherTotal(?string $raw): float
    {
        return $this->sumAmounts($this->getVoucherEntries($raw));
    }

    /**
     * Get total of ALL entries (gift cards + vouchers) from unified column.
     */
    public function getTotalFromEntries(?string $raw): float
    {
        return $this->sumAmounts($this->decodeEntries($raw));
    }

    /**
     * Check if a gift card code is already applied (reads from unified column).
     */
    public function isGiftCardAlreadyAppliedInEntries(?string $raw, string $no): bool
    {
        foreach ($this->getGiftCardEntries($raw) as $card) {
            if (($card['entry_no'] ?? '') === $no) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a voucher code is already applied (reads from unified column).
     */
    public function isVoucherAlreadyApplied(?string $raw, string $no): bool
    {
        foreach ($this->getVoucherEntries($raw) as $v) {
            if (($v['entry_no'] ?? '') === $no) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the pin of the first gift card in unified column (for legacy compatibility).
     */
    public function getFirstGiftCardPin(?string $raw): ?string
    {
        $cards = $this->getGiftCardEntries($raw);
        return $cards ? ($cards[0]['pin_code'] ?? null) : null;
    }

    /**
     * Get JSON-encoded list of gift card entries only (for legacy ls_gift_card_no segment).
     */
    public function getGiftCardEntriesJson(?string $raw): ?string
    {
        $cards = $this->getGiftCardEntries($raw);
        return empty($cards) ? null : json_encode($cards);
    }

    /**
     * Get JSON-encoded list of voucher entries only.
     */
    public function getVoucherEntriesJson(?string $raw): ?string
    {
        $vouchers = $this->getVoucherEntries($raw);
        return empty($vouchers) ? null : json_encode($vouchers);
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
     * @param bool $addCurrencySymbol
     * @return array|string|string[]
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function formatValue($value, $addCurrencySymbol = false)
    {
        $currency = $this->storeManager->getStore()->getCurrentCurrency();

        return $currency->format(
            $value,
            ['display' => $addCurrencySymbol ? Currency\Data\Currency::USE_SYMBOL : Currency\Data\Currency::NO_SYMBOL],
            false
        );
    }

    /**
     * Format expiry date, showing time only if it exists
     *
     * @param string|null $expireDate
     * @return string|null
     */
    public function formatExpireDate(?string $expireDate): ?string
    {
        if (empty($expireDate) || $expireDate == '0001-01-01') {
            return null;
        }
        try {
            $dateTime = new \DateTime($expireDate);
            $time     = $dateTime->format('H:i:s');
            return $time !== '00:00:00'
                ? $dateTime->format('Y-m-d H:i:s')
                : $dateTime->format('Y-m-d');
        } catch (\Exception $e) {
            return $expireDate;
        }
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
     * @param POSDataEntry $giftCardResponse
     * @return array
     * @throws NoSuchEntityException|GuzzleException|LocalizedException
     */
    public function getConvertedGiftCardBalance(POSDataEntry $giftCardResponse)
    {
        $pointRate = $storeCurrencyPointRate = $giftCardPointRate = $quotePointRate = 0;
        $currency = $giftCardResponse->getCurrencycode();

        if ($this->lsr->getStoreCurrencyCode() == $this->giftCardHelper->getLocalCurrencyCode()) {
            $pointRate = $this->loyaltyHelper->getPointRate(
                null,
                $giftCardResponse->getCurrencycode(),
                true
            );
            $quotePointRate = $pointRate;
            $case = 1;
        } elseif ($this->lsr->getStoreCurrencyCode() != $this->giftCardHelper->getLocalCurrencyCode()) {
            $storeCurrencyPointRate = $this->loyaltyHelper->getPointRate(
                null,
                $this->lsr->getStoreCurrencyCode(),
                true
            );
            $giftCardPointRate = $this->loyaltyHelper->getPointRate(
                null,
                $giftCardResponse->getCurrencycode(),
                true
            );
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
