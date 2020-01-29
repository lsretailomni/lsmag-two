<?php

namespace Ls\Replication\Block\Adminhtml\System\Config;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class InventoryPerStore
 * @package Ls\Replication\Block\Adminhtml\System\Config
 */
class InventoryPerStore implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $methods = [];

        $methods[] = [
            'value' => '0',
            'label' => __('Inventory Per Store')
        ];
        $methods[] = [
            'value' => '1',
            'label' => __('Inventory Totals')
        ];

        return $methods;
    }
}
