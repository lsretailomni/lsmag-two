<?php

namespace Ls\Omni\Plugin\ConfigurableProduct\Block\Product\View\Type;

use Ls\Core\Model\LSR;

/**
 * Class Configurable
 * @package Ls\Omni\Plugin\ConfigurableProduct\Block\Product\View\Type
 */
class Configurable
{
    public function afterGetJsonConfig(
        \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $subject,
        $result
    ) {
        $jsonResult           = json_decode($result, true);
        $jsonResult['uomQty'] = [];
        foreach ($subject->getAllowProducts() as $simpleProduct) {
            $jsonResult['uomQty'][$simpleProduct->getId()] = (int)$simpleProduct->getData(LSR::LS_UOM_ATTRIBUTE_QTY);
        }
        $result = json_encode($jsonResult);
        return $result;
    }
}
