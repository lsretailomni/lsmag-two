<?php
declare(strict_types=1);

namespace Ls\Replication\Block\Adminhtml\System\Config;

use Magento\Framework\Data\OptionSourceInterface;

class DisplayAllStores implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 1, 'label' => __('Show all the stores')],
            ['value' => 0, 'label' => __('Show the Click and Collect stores only')]
        ];
    }
}
