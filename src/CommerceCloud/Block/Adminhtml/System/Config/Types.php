<?php

namespace Ls\CommerceCloud\Block\Adminhtml\System\Config;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Source_Model for central type configuration field
 */
class Types implements OptionSourceInterface
{
    /**
     * Options available
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 1, 'label' => __('SaaS')],
            ['value' => 0, 'label' => __('On-Premise')]
        ];
    }
}
