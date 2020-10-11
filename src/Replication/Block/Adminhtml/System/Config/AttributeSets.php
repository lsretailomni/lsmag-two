<?php

namespace Ls\Replication\Block\Adminhtml\System\Config;

use Magento\Framework\Data\OptionSourceInterface;

/**
 *
 * Provide source model for AttributeSets
 */
class AttributeSets implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 1, 'label' => __('Product Group Id')],
            ['value' => 0, 'label' => __('Item Category Code')]
        ];
    }
}
