<?php

namespace Ls\Replication\Block\Adminhtml\System\Config;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class OrderCreationMethod
 * @package Ls\Replication\Block\Adminhtml\System\Config
 */
class OrderCreationMethod implements ArrayInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $methods = [];

        $methods[] = [
            'value' => '0',
            'label' => __('On Demand')
        ];
        $methods[] = [
            'value' => '1',
            'label' => __('Batch Job')
        ];

        return $methods;
    }
}
