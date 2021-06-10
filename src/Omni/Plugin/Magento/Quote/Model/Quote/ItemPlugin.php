<?php

namespace Ls\Omni\Plugin\Magento\Quote\Model\Quote;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Helper\StockHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Item;

/**
 * Interceptor class to intercept quote_item and do stock lookup
 */
class ItemPlugin
{
    /** @var LSR @var */
    private $lsr;

    /**
     * @var StockHelper
     */
    private $stockHelper;

    /** @var ItemHelper $itemHelper */
    private $itemHelper;

    /**
     * @param LSR $LSR
     * @param StockHelper $stockHelper
     * @param ItemHelper $itemHelper
     */
    public function __construct(
        LSR $LSR,
        StockHelper $stockHelper,
        ItemHelper $itemHelper
    ) {
        $this->lsr         = $LSR;
        $this->stockHelper = $stockHelper;
        $this->itemHelper  = $itemHelper;
    }

    /**
     * After plugin intercepting addQty of each quote_item
     *
     * @param Item $subject
     * @param $result
     * @return mixed
     * @throws LocalizedException
     */
    public function afterAddQty(Item $subject, $result)
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            if ($this->lsr->inventoryLookupBeforeAddToCartEnabled()) {
                if (!$result->getParentItem() || !$result->getId()) {
                    $storeId = $this->lsr->getActiveWebStore();
                    $qty     = $result->getQty();
                    $uomQty  = $result->getProduct()->getData(LSR::LS_UOM_ATTRIBUTE_QTY);

                    if (!empty($uomQty)) {
                        $qty = $qty * $uomQty;
                    }
                    list($parentProductSku, $childProductSku) = $this->itemHelper->getComparisonValues(
                        $result->getProductId(),
                        $result->getSku()
                    );

                    $stock = $this->stockHelper->getItemStockInStore(
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
