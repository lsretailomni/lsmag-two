<?php

namespace Ls\Replication\Model;

use Magento\Framework\Data\OptionSourceInterface;

class BlockedStatus implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['label' => 'True', 'value' => 1],
        ];
        return $options;
    }
}
