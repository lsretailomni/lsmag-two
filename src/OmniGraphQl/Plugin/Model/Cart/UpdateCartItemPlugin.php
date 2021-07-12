<?php

namespace Ls\OmniGraphQl\Plugin\Model\Cart;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\StockHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\UpdateCartItem;

/**
 * Interceptor class to intercept update cart item and do stock lookup
 */
class UpdateCartItemPlugin
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
     * After plugin intercepting update cart item model execute method
     *
     * @param UpdateCartItem $subject
     * @param $result
     * @param Quote $cart
     * @param int $cartItemId
     * @param float $quantity
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterExecute(
        UpdateCartItem $subject,
        $result,
        Quote $cart,
        int $cartItemId,
        float $quantity
    ) {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $item = $cart->getItemById($cartItemId);
            $this->stockHelper->validateQty($quantity, $item);
        }

        return $result;
    }
}
