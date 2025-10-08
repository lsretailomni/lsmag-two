<?php
declare(strict_types=1);

namespace Ls\Replication\Model;

use Magento\Framework\Data\OptionSourceInterface;

class IsDeletedStatus implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['label' => 'False', 'value' => 0],
            ['label' => 'True', 'value' => 1]
        ];
        return $options;
    }
}
