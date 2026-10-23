<?php
declare(strict_types=1);

namespace Ls\Omni\Model\VendorSourcing;

/**
 * Converts the Central LeadTimeCalculation value (e.g. 2D, 3W, 1M, 10) into a number of days
 */
class LeadTimeParser
{
    private const PATTERN = '/^\s*(\d{1,4})\s*([DWM])?\s*$/i';

    private const MAX_DAYS = 3650;

    private const UNIT_DAYS = [
        'D' => 1,
        'W' => 7,
        'M' => 30
    ];

    /**
     * Parse the lead time into days
     *
     * Empty value means 0 days, a bare number means days, unsupported values return null.
     *
     * @param string|null $value
     * @return int|null
     */
    public function parse(?string $value): ?int
    {
        if ($value === null || trim($value) === '') {
            return 0;
        }

        if (!preg_match(self::PATTERN, $value, $matches)) {
            return null;
        }

        $unit = isset($matches[2]) && $matches[2] !== '' ? strtoupper($matches[2]) : 'D';

        $days = (int)$matches[1] * self::UNIT_DAYS[$unit];

        return $days > self::MAX_DAYS ? null : $days;
    }
}
