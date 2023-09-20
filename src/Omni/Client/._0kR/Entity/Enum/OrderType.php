<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity\Enum;

use MyCLabs\Enum\Enum;

/**
 * @$method static OrderType SALE()
 * @$method static OrderType CLICK_AND_COLLECT()
 * @$method static OrderType SCAN_PAY_GO()
 * @$method static OrderType SCAN_PAY_GO_SUSPEND()
 */
class OrderType extends Enum
{
    public const SALE = 'Sale';

    public const CLICK_AND_COLLECT = 'ClickAndCollect';

    public const SCAN_PAY_GO = 'ScanPayGo';

    public const SCAN_PAY_GO_SUSPEND = 'ScanPayGoSuspend';
}
