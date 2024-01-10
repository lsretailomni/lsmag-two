<?php

namespace Ls\Omni\Plugin\Bundle\Helper\Catalog\Product;

use Magento\Bundle\Helper\Catalog\Product\Configuration;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface;

class ConfigurationPlugin
{
    /**
     * After plugin to set correct final_price for a simple product in bundle for showing on minicart & cart page
     *
     * @param Configuration $subject
     * @param $result
     * @param ItemInterface $item
     * @param Product $selectionProduct
     * @return float|mixed
     */
    public function afterGetSelectionFinalPrice(
        Configuration $subject,
        $result,
        ItemInterface $item,
        Product $selectionProduct
    ) {
        foreach ($item->getChildren() as $child) {
            if ($child->getProductId() == $selectionProduct->getId()) {
                if ($child->getCustomPrice() > 0) {
                    $result = (float) $child->getCustomPrice();
                }
                break;
            }
        }

        return $result;
    }
}
