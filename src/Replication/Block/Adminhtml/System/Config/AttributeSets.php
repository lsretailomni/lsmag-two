<?php

namespace Ls\Replication\Block\Adminhtml\System\Config;

use Magento\Framework\Data\OptionSourceInterface;
use \Ls\Core\Model\LSR;

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
            ['value' => LSR::SC_REPLICATION_ATTRIBUTE_SET_PRODUCT_GROUP_ID, 'label' => __('Product Group Id')],
            ['value' => LSR::SC_REPLICATION_ATTRIBUTE_SET_ITEM_CATEGORY_CODE, 'label' => __('Item Category Code')]
        ];
    }
}
