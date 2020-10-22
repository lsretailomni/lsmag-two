<?php

namespace Ls\Omni\Plugin\Swatches\Block\Product\Renderer;

use Ls\Core\Model\LSR;

/**
 * Class Configurable
 * @package Ls\Omni\Plugin\Swatches\Block\Product\Renderer
 */
class Configurable
{
    /**
     * @param \Magento\Swatches\Block\Product\Renderer\Configurable $subject
     * @param $result
     * @return false|string
     */
    public function afterGetJsonConfig(\Magento\Swatches\Block\Product\Renderer\Configurable $subject, $result)
    {
        $jsonResult           = json_decode($result, true);
        $jsonResult['uomQty'] = [];

        foreach ($subject->getAllowProducts() as $simpleProduct) {
            $jsonResult['uomQty'][$simpleProduct->getId()] = (int)$simpleProduct->getData(LSR::LS_UOM_ATTRIBUTE_QTY);
        }
        $result = json_encode($jsonResult);
        return $result;
    }
}
