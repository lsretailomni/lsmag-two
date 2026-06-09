<?php
declare(strict_types=1);

namespace Ls\Customer\Block\Order\Hyva;

/**
 * This class is overriding in hospitality module
 *
 * Block being used for various sections on order detail
 */
class Info extends \Ls\Customer\Block\Order\Info
{
    /**
     * @var string
     */
    // @codingStandardsIgnoreStart
    protected $_template = 'Ls_Customer::order/info-hyva.phtml';
    // @codingStandardsIgnoreEnd
}
