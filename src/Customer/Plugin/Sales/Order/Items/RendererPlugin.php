<?php

namespace Ls\Customer\Plugin\Sales\Order\Items;

use Magento\Bundle\Block\Sales\Order\Items\Renderer;

class RendererPlugin
{
    /**
     * After plugin to set the correct price for simple in bundle
     *
     * @param Renderer $subject
     * @param $result
     * @param $item
     * @return mixed
     */
    public function afterGetSelectionAttributes(Renderer $subject, $result, $item)
    {
        if ($item instanceof \Magento\Sales\Model\Order\Item && $result) {
            $result['price'] = $item->getPriceInclTax();
        }

        return $result;
    }
}
