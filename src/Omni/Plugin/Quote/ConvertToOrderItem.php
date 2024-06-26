<?php
namespace Ls\Omni\Plugin\Quote;

use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\Quote\Item\ToOrderItem;

class ConvertToOrderItem
{
    /**
     * Around plugin to set the LsDiscountAmount in the order item
     *
     * @param ToOrderItem $subject
     * @param \Closure $proceed
     * @param AbstractItem $item
     * @param array $additional
     * @return mixed
     */
    public function aroundConvert(
        ToOrderItem $subject,
        \Closure $proceed,
        AbstractItem $item,
        $additional = []
    ) {
        $orderItem = $proceed($item, $additional);
        $orderItem->setLsDiscountAmount($item->getLsDiscountAmount());

        return $orderItem;
    }
}
