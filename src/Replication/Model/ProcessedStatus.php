<?php

namespace Ls\Replication\Model;

use Magento\Framework\Data\OptionSourceInterface;

class ProcessedStatus implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['label' => 'Not Processed', 'value' => 0],
            ['label' => 'Processed', 'value' => 1]
        ];
        return $options;
    }
}
