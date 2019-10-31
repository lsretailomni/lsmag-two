<?php

namespace Ls\Omni\Block\Adminhtml\System\Config;

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
        return [['value' => 1, 'label' => __('12 Hours')], ['value' => 0, 'label' => __('24 Hours')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [0 => __('24 Hours'), 1 => __('12 Hours')];
    }
}
