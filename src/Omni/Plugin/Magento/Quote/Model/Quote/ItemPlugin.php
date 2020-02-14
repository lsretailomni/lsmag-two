<?php

namespace Ls\Omni\Plugin\Magento\Quote\Model\Quote;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\StockHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Item;

/**
 * Class ItemPlugin
 * @package Ls\Omni\Plugin\Magento\Quote\Model\Quote
 */
class ItemPlugin
{
    /** @var LSR @var */
    private $lsr;

    /**
     * @var StockHelper
     */
    private $stockHelper;

    /**
     * ItemPlugin constructor.
     * @param LSR $LSR
     * @param StockHelper $stockHelper
     */
    public function __construct(
        LSR $LSR,
        StockHelper $stockHelper
    ) {
        $this->lsr               = $LSR;
        $this->stockHelper       = $stockHelper;
    }

    /**
     * @param Item $subject
     * @param $result
     * @return mixed
     * @throws LocalizedException
     */
    public function afterAddQty(Item $subject, $result)
    {
        if ($this->lsr->isLSR()) {
            if ($this->lsr->inventoryLookupBeforeAddToCartEnabled()) {
                if (!$result->getParentItem() || !$result->getId()) {
                    $storeId          = $this->lsr->getDefaultWebStore();
                    $simpleProductSku = $result->getSku();
                    $qty              = $result->getQty();

                    $parentProductSku = isset(explode('-', $simpleProductSku)[0]) ?
                        explode('-', $simpleProductSku)[0] : "";
                    $childProductSku  = isset(explode('-', $simpleProductSku)[1]) ?
                        explode('-', $simpleProductSku)[1] : "";
                    $stock            = $this->stockHelper->getItemStockInStore(
                        $storeId,
                        $parentProductSku,
                        $childProductSku
                    );
                    if ($stock) {
                        $itemStock = reset($stock);
                        if ($itemStock->getQtyInventory() <= 0) {
                            throw new LocalizedException(__(
                                'Product that you are trying to add is not available.'
                            ));
                        } elseif ($itemStock->getQtyInventory() < $qty) {
                            throw new LocalizedException(__(
                                'Max quantity available for this item is %1',
                                $itemStock->getQtyInventory()
                            ));
                        }
                    }
                }
            }
        }
        return $result;
    }
}
