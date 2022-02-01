<?php

namespace Ls\CommerceCloud\Block\Adminhtml\System\Config;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Source_Model for central type compatibility field
 */
class Compatibility implements OptionSourceInterface
{
    /**
     * Options available
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('Newer Odata Implementation')],
            ['value' => 1, 'label' => __('Older Implementation')]
        ];
    }
}
