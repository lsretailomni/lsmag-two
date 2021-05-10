<?php

namespace Ls\Omni\Plugin\Product\Type;

use Magento\Catalog\Model\Product\Type\AbstractType;

/**
 * Plugin responsible for removing dashes from sku in case of custom options
 */
class AbstractTypePlugin
{

    /**
     * After plugin to override the sku result
     *
     * @param AbstractType $subject
     * @param $result
     * @param $product
     * @return mixed
     */
    public function afterGetSku(AbstractType $subject, $result, $product)
    {
        return $product->getData('sku');
    }
}
