<?php

namespace Ls\Core\Model\Config\Source;

use \Ls\Core\Model\LSR;
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
            ['value' => LSR::LS_INDUSTRY_VALUE_RETAIL, 'label' => __('Retail')],
            ['value' => LSR::LS_INDUSTRY_VALUE_HOSPITALITY, 'label' => __('Hospitality')]
        ];
    }
}
