<?php
declare(strict_types=1);

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
            $this->stockHelper->validateQty($quantity, $item, null, false, true);
        }

        return $result;
    }
}
