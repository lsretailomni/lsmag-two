<?php
declare(strict_types=1);

namespace Ls\Omni\Helper;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\CentralEcommerce\Entity\POSDataEntry;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Helper for voucher support
 */
class VoucherHelper extends AbstractHelperOmni
{
    /**
     * Default LS Central entry type, used when no voucher/gift card configuration exists.
     */
    public const ENTRY_TYPE_GIFT_CARD = 'GIFTCARDNO';

    /**
     * Try each configured entry type (code field) against the LS Central balance API.
     * Returns an array with the matched config entry, the balance response, and whether it is a voucher,
     * or null if no configured entry type returns a valid balance.
     *
     * The admin config `code` column stores LS Central entry types, e.g. GIFTCARDNO, VOUCHER.
     *
     *
     * @param string $giftCardNo  The code entered by the customer on the frontend
     * @param string|null $pin
     * @return array{response: POSDataEntry, config: array, entry_type: string}|null
     * @throws NoSuchEntityException
     */
    public function resolveCode(string $giftCardNo, ?string $pin): ?array
    {
        $configs = $this->lsr->getVoucherGiftCardConfiguration();

        if (empty($configs)) {
            // No admin configuration — fall back to default GIFTCARDNO behaviour
            $response = $this->giftCardHelper->getGiftCardBalance($giftCardNo, $pin, self::ENTRY_TYPE_GIFT_CARD);
            if ($response instanceof POSDataEntry) {
                return [
                    'response'   => $response,
                    'config'     => [],
                    'entry_type' => self::ENTRY_TYPE_GIFT_CARD,
                ];
            }
            return null;
        }

        foreach ($configs as $entry) {
            $entryType = trim((string)($entry['code'] ?? ''));
            if (empty($entryType)) {
                continue;
            }

            $response = $this->giftCardHelper->getGiftCardBalance($giftCardNo, $pin, $entryType);

            if ($response instanceof POSDataEntry) {
                return [
                    'response'   => $response,
                    'config'     => $entry,
                    'entry_type' => $entryType,
                ];
            }
        }

        return null;
    }

    /**
     * Resolve a caller-supplied entry type against the configured entry types.
     *
     * Accepts only the entry types resolveCode() itself would walk, so an explicitly supplied entry type
     * cannot be used to query a POS data entry type that was never exposed to ecommerce (e.g. INCOMEACCOUNT).
     * Matching is case-insensitive and the configured spelling is returned, so LS Central always receives
     * exactly what was configured.
     *
     * @param string $entryType The caller-supplied LS Central entry type
     * @return string|null The configured entry type, or null when it is not an allowed entry type
     * @throws NoSuchEntityException
     */
    public function normalizeEntryType(string $entryType): ?string
    {
        $entryType = trim($entryType);

        if ($entryType === '') {
            return null;
        }

        $configs = $this->lsr->getVoucherGiftCardConfiguration();

        if (empty($configs)) {
            // Mirror resolveCode()'s fallback: only the default gift card entry type is available.
            return strcasecmp($entryType, self::ENTRY_TYPE_GIFT_CARD) === 0 ? self::ENTRY_TYPE_GIFT_CARD : null;
        }

        foreach ($configs as $entry) {
            $code = trim((string)($entry['code'] ?? ''));

            if ($code !== '' && strcasecmp($code, $entryType) === 0) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Get voucher tender type from configuration by entry type code
     *
     * @param string $entryType  The LS Central entry type (the admin config `code` value)
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getTenderTypeByEntryType(string $entryType): ?string
    {
        $configs = $this->lsr->getVoucherGiftCardConfiguration();
        foreach ($configs as $entry) {
            if (isset($entry['code']) && strtoupper($entry['code']) === strtoupper($entryType)) {
                return $entry['tender_type'] ?? null;
            }
        }
        return null;
    }

    /**
     * Decode the JSON-encoded voucher list stored in ls_pos_data_entries.
     * Returns an array of ['entry_type', 'entry_no', 'pin_code', 'amount', 'currency_code', 'currency_factor', 'tender_type'].
     *
     * @param string|null $raw
     * @return array
     */
    public function decodeVouchers(?string $raw): array
    {
        if (empty($raw)) {
            return [];
        }
        $decoded = json_decode($raw, true);
        // Support legacy plain string (single voucher no)
        if (!is_array($decoded)) {
            return [];
        }
        return $decoded;
    }

    /**
     * Encode the voucher list array to JSON for storage in ls_pos_data_entries.
     *
     * @param array $vouchers
     * @return string
     */
    public function encodeVouchers(array $vouchers): string
    {
        return json_encode($vouchers);
    }

    /**
     * Check if voucher redemption is enabled for the given area.
     *
     * Vouchers share the gift card configuration group (ls_mag/ls_giftcard), so this
     * mirrors GiftCardHelper::isGiftCardEnabled(): the elements must be enabled and the
     * cart/checkout display toggle for the requested area must be on.
     *
     * @param string $area
     * @return bool
     * @throws NoSuchEntityException|GuzzleException
     */
    public function isVoucherEnabled(string $area): bool
    {
        if (!$this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            return false;
        }

        $elementsEnabled = (bool) $this->lsr->getStoreConfig(
            LSR::LS_ENABLE_GIFTCARD_ELEMENTS,
            $this->lsr->getCurrentStoreId()
        );

        $areaPath = $area === 'cart'
            ? LSR::LS_GIFTCARD_SHOW_ON_CART
            : LSR::LS_GIFTCARD_SHOW_ON_CHECKOUT;

        return $elementsEnabled && (bool) $this->lsr->getStoreConfig(
            $areaPath,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * Check if the pin code field is enabled for voucher/gift card redemption.
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isPinCodeFieldEnable(): bool
    {
        return (bool) $this->lsr->getStoreConfig(
            LSR::LS_GIFTCARD_SHOW_PIN_CODE_FIELD,
            $this->lsr->getCurrentStoreId()
        );
    }
}
