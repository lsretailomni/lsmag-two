<?php

namespace Ls\Omni\Plugin\Product\Type;

use Magento\Catalog\Model\Product\Option\Type\Select;

/**
 * Plugin responsible for removing dashes from sku in case of custom options
 */
class SelectPlugin
{

    /**
     * After plugin to override the sku result
     *
     * @param Select $subject
     * @param $result
     * @return null
     */
    public function afterGetOptionSku(Select $subject, $result)
    {
        return null;
    }
}
