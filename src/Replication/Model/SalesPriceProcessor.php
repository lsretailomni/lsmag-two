<?php

declare(strict_types=1);

namespace Ls\Replication\Model;

use Ls\Replication\Model\Central\ReplSalesPrice;

/**
 * Stateless helper that evaluates the date-validity of a ReplSalesPrice record.
 *
 * Mirrors the future/valid logic used by SyncPrice for repl_price, but WITHOUT
 * any status guard: ReplSalesPrice has no status field.
 */
class SalesPriceProcessor
{
    private const INVALID_DATE = '1900-01-01T00:00:00';

    private const INVALID_DATE_ALT = '1900-01-01';

    private const INVALID_DATE_MIN = '0001-01-01';

    /**
     * Returns true if the sales price record is not yet active (start date is in the future).
     *
     * @param ReplSalesPrice $record
     * @return bool
     */
    public function isFutureSalesPrice(ReplSalesPrice $record): bool
    {
        $startingDate = $record->getStartingDate();

        if ($this->isInvalidDate($startingDate)) {
            return false;
        }

        try {
            $currentDate = time();
            $startDateTime = strtotime($startingDate);

            if ($startDateTime === false) {
                return false;
            }

            return $currentDate < $startDateTime;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Returns true if the price is currently applicable.
     *
     * No-date fields are treated as open-ended (valid).
     *
     * @param ReplSalesPrice $record
     * @return bool
     */
    public function isValidSalesPrice(ReplSalesPrice $record): bool
    {
        $startingDate = $record->getStartingDate();
        $endingDate = $record->getEndingDate();

        $isStartingDateInvalid = $this->isInvalidDate($startingDate);
        $isEndingDateInvalid = $this->isInvalidDate($endingDate);

        // No date restrictions at all - open-ended price.
        if ($isStartingDateInvalid && $isEndingDateInvalid) {
            return true;
        }

        try {
            $currentDate = time();

            // Only start date is set - valid once the start date has been reached.
            if (!$isStartingDateInvalid && $isEndingDateInvalid) {
                $startDateTime = strtotime($startingDate);
                if ($startDateTime === false) {
                    return true;
                }
                return $currentDate >= $startDateTime;
            }

            // Only end date is set - valid until the end date has passed.
            if ($isStartingDateInvalid && !$isEndingDateInvalid) {
                $endDateTime = strtotime($endingDate);
                if ($endDateTime === false) {
                    return true;
                }
                return $currentDate <= $endDateTime;
            }

            // Both dates set - valid only within the range.
            $startDateTime = strtotime($startingDate);
            $endDateTime = strtotime($endingDate);
            if ($startDateTime === false || $endDateTime === false) {
                return true;
            }

            return $currentDate >= $startDateTime && $currentDate <= $endDateTime;
        } catch (\Exception $e) {
            // On date parsing error, allow the price.
            return true;
        }
    }

    /**
     * Selects the best price from multiple valid candidates for the same item/variant/UOM key.
     *
     * Priority: AC2 (currency-specific > blank) → AC5 (nearest Starting Date) → AC6 (PriceListCode+LineNo desc).
     * Never selects by lowest price.
     *
     * @param object[] $candidates objects exposing getCurrencyCode(), getStartingDate(), getPriceListCode(), getLineNumber()
     * @param string $storeCurrency the store's base currency code; blank disables currency matching
     * @return object|null
     */
    public function selectBestPrice(array $candidates, string $storeCurrency = ''): ?object
    {
        if (empty($candidates)) {
            return null;
        }

        // AC2: currency matching against the store's base currency
        // Prefer lines with matching currency; fall back to blank-currency lines;
        // lines with a non-matching specific currency are excluded entirely.
        if ($storeCurrency !== '') {
            $matchingCurrency = array_values(array_filter(
                $candidates,
                fn($c) => (string)($c->getCurrencyCode() ?? '') === $storeCurrency
            ));
            if (!empty($matchingCurrency)) {
                $pool = $matchingCurrency;
            } else {
                // No matching-currency line — fall back to blank-currency lines only (non-matching specific currencies excluded)
                $pool = array_values(array_filter(
                    $candidates,
                    fn($c) => (string)($c->getCurrencyCode() ?? '') === ''
                ));
            }
        } else {
            // No store currency provided — legacy behaviour: any specific currency beats blank
            $currencySpecific = array_values(array_filter(
                $candidates,
                fn($c) => (string)($c->getCurrencyCode() ?? '') !== ''
            ));
            $pool = !empty($currencySpecific) ? $currencySpecific : array_values($candidates);
        }

        if (empty($pool)) {
            return null;
        }
        if (count($pool) === 1) {
            return $pool[0];
        }

        // AC5 + AC6: sort descending by starting date (nearest = most recent = highest timestamp),
        // then descending by PriceListCode, then descending by LineNumber
        usort($pool, function ($a, $b) {
            // AC5: null/empty/unparseable starting date treated as epoch (loses to any real date)
            $tsA = !empty($a->getStartingDate()) ? (strtotime($a->getStartingDate()) ?: 0) : 0;
            $tsB = !empty($b->getStartingDate()) ? (strtotime($b->getStartingDate()) ?: 0) : 0;
            if ($tsA !== $tsB) {
                return $tsB <=> $tsA; // descending: most recent Starting Date wins (AC5)
            }
            // AC6 tiebreaker: PriceListCode descending
            $codeCompare = strcmp((string)($b->getPriceListCode() ?? ''), (string)($a->getPriceListCode() ?? ''));
            if ($codeCompare !== 0) {
                return $codeCompare;
            }
            // AC6 tiebreaker: LineNumber descending
            return ((int)($b->getLineNumber() ?? 0)) - ((int)($a->getLineNumber() ?? 0));
        });

        return $pool[0];
    }

    /**
     * A date is invalid when it is empty or matches the LS Central 1900-01-01 sentinel.
     *
     * @param string|null $date
     * @return bool
     */
    private function isInvalidDate(?string $date): bool
    {
        return empty($date)
            || strpos($date, self::INVALID_DATE) === 0
            || strpos($date, self::INVALID_DATE_ALT) === 0
            || strpos($date, self::INVALID_DATE_MIN) === 0;
    }
}
