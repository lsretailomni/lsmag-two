<?php
declare(strict_types=1);

namespace Ls\Replication\Model;

use Magento\Framework\Data\OptionSourceInterface;

class FailedStatus implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['label' => 'Pass', 'value' => 0],
            ['label' => 'Failed', 'value' => 1]
        ];
        return $options;
    }
}
