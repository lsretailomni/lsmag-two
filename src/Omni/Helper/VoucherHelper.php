<?php
declare(strict_types=1);

namespace Ls\Omni\Helper;

use \Ls\Omni\Client\CentralEcommerce\Entity\POSDataEntry;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Helper for voucher support
 */
class VoucherHelper extends AbstractHelperOmni
{
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
            $response = $this->giftCardHelper->getGiftCardBalance($giftCardNo, $pin, 'GIFTCARDNO');
            if ($response instanceof POSDataEntry) {
                return [
                    'response'   => $response,
                    'config'     => [],
                    'entry_type' => 'GIFTCARDNO',
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
}
