<?php

namespace Ls\Omni\Block\Adminhtml\System\Config;

use \Ls\Core\Model\LSR;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class TimeFormat
 * @package Magento\Config\Model\Config\Source
 */
class TimeFormat implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => LSR::STORE_HOURS_TIME_FORMAT_12HRS, 'label' => __('12 Hours')],
            ['value' => LSR::STORE_HOURS_TIME_FORMAT_24HRS, 'label' => __('24 Hours')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            LSR::STORE_HOURS_TIME_FORMAT_24HRS => __('24 Hours'),
            LSR::STORE_HOURS_TIME_FORMAT_12HRS => __('12 Hours')
        ];
    }
}
