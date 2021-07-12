<?php

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
    /** @var LSR @var */
    private $lsr;

    /**
     * @var StockHelper
     */
    private $stockHelper;

    /**
     * @param LSR $LSR
     * @param StockHelper $stockHelper
     */
    public function __construct(
        LSR $LSR,
        StockHelper $stockHelper
    ) {
        $this->lsr         = $LSR;
        $this->stockHelper = $stockHelper;
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
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId()) && (!$result->getParentItem() || !$result->getId())) {
            $this->stockHelper->validateQty($result->getQty(), $result);
        }

        return $result;
    }
}
