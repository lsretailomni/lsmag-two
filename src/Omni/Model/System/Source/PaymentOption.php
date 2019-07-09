<?php

namespace Ls\Omni\Model\System\Source;

/**
 * Class PaymentOption
 * @package Ls\Omni\Model\System\Source
 */
class PaymentOption implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('Pay at the Store only')],
            ['value' => 1, 'label' => __('All Active Method')]
        ];
    }
}