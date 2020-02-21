<?php

namespace Ls\Omni\CustomerData;

use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\ItemHelper;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Quote\Model\Quote\Item;
use function array_merge;

/**
 * Default item
 */
class AbstractItem
{
    /**
     * @var BasketHelper
     */
    public $basketHelper;


    /**
     * @var CheckoutHelper
     */
    public $checkoutHelper;

    /**
     * @var ItemHelper
     */
    public $itemHelper;

    public function __construct(
        BasketHelper $basketHelper,
        ItemHelper $itemHelper,
        CheckoutHelper $checkoutHelper
    ) {
        $this->itemHelper     = $itemHelper;
        $this->basketHelper   = $basketHelper;
        $this->checkoutHelper = $checkoutHelper;
    }

    /**
     * {@inheritdoc}
     */

    public function aroundGetItemData(
        \Magento\Checkout\CustomerData\AbstractItem $subject,
        callable $proceed,
        Item $item
    ) {
        $this->item                = $item;
        $discountAmountTextMessage = __("Save");
        if ($this->item->getPrice() <= 0) {
            $this->basketHelper->cart->save();
        }
        $originalPrice = '';
        if ($this->item->getCalculationPrice() == $this->item->getCustomPrice() && $this->item->getCustomPrice() > 0 &&
            $this->item->getDiscountAmount() > 0) {
            $originalPrice  = $this->item->getProduct()->getPrice() * $this->item->getQty();
            $discountAmount = ($this->item->getDiscountAmount() > 0 && $this->item->getDiscountAmount() != null) ?
                $this->checkoutHelper->formatPrice($this->item->getDiscountAmount()) : '';
        } else {
            $originalPrice  = '';
            $discountAmount = '';
            $this->item->setConvertedPrice($this->item->getProduct()->getPrice() * $this->item->getQty());
        }
        $result = $proceed($this->item);

        return array_merge(
            [
                'lsPriceOriginal'  => ($originalPrice != "") ?
                    $this->checkoutHelper->formatPrice($originalPrice) : $originalPrice,
                'lsDiscountAmount' => ($discountAmount != "") ?
                    '(' . __($discountAmountTextMessage) . ' ' . $discountAmount . ')' : $discountAmount
            ],
            $result
        );
    }
}
