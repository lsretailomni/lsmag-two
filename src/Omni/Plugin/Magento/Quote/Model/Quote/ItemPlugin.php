<?php
declare(strict_types=1);

namespace Ls\Omni\Plugin\Magento\Quote\Model\Quote;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\StockHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Item;

/**
 * Interceptor class to intercept quote_item and do stock lookup
 */
class ItemPlugin
{
    /**
     * @param LSR $lsr
     * @param StockHelper $stockHelper
     */
    public function __construct(
        public LSR $lsr,
        public StockHelper $stockHelper
    ) {
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
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId()) && (!$result->getParentItem())) {
            return $this->stockHelper->validateQty($result->getQty(), $result);
        }

        return $result;
    }
}
