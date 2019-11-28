<?php

namespace Ls\Replication\Model;

use Magento\Framework\Data\OptionSourceInterface;

class UpdatedStatus implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['label' => 'Updated', 'value' => 0],
            ['label' => 'Pending', 'value' => 1]
        ];
        return $options;
    }
}
