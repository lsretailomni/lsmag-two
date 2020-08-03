<?php

namespace Ls\Core\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Industry
 * @package Ls\Core\Model\Config\Source
 */
class Industry implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'Retail', 'label' => __('Retail')],
            ['value' => 'Hospitality', 'label' => __('Hospitality')]
        ];
    }
}
