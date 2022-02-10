<?php

namespace Ls\Omni\Block\Adminhtml\System\Config;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Source model for basket calculation config
 */
class BasketCalculation implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        return [
            ['value' => '0', 'label' => __('Calculate Real Time')],
            ['value' => '1', 'label' => __('Calculate Once on Checkout')],
            ['value' => '2', 'label' => __('Calculate Real Time & Once on Checkout')]
        ];
    }
}
