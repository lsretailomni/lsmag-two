<?php
declare(strict_types=1);

namespace Ls\Webhooks\Model\Config\Source;

use \Ls\Core\Model\LSR;
use Magento\Framework\Data\OptionSourceInterface;

class ExpectedOrderStatus implements OptionSourceInterface
{
    /**
     * Get all expected order statuses
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('-- Please Select --')],
            ['value' => LSR::LS_STATE_PICKED, 'label' => __('Order Pickup for Click and Collect')],
            ['value' => LSR::LS_STATE_COLLECTED, 'label' => __('Order Collected for Click and Collect')],
            ['value' => LSR::LS_STATE_CANCELED, 'label' => __('Order Cancelled')],
            ['value' => LSR::LS_STATE_MISC, 'label' => __('Miscellaneous')]
        ];
    }
}
