<?php

namespace Ls\Replication\Model\System\Source;

use Magento\Framework\Data\OptionSourceInterface;

class UomProductType implements OptionSourceInterface
{
    const SIMPLE = 'simple';
    const CONFIGURABLE = 'configurable';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::SIMPLE, 'label' => __('Simple')],
            ['value' => self::CONFIGURABLE, 'label' => __('Configurable')]
        ];
    }
}
