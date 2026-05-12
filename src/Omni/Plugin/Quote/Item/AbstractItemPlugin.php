<?php
declare(strict_types=1);

namespace Ls\Omni\Plugin\Quote\Item;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote\Item\AbstractItem;

/**
 * Interceptor to interceptor methods of AbstractItem
 */
class AbstractItemPlugin
{

    /**
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        public CheckoutSession $checkoutSession
    ) {
    }

    /**
     * After plugin to fix calculation_price_original while creating order from admin
     *
     * @param AbstractItem $subject
     * @param $result
     * @return float
     */
    public function afterGetCalculationPriceOriginal(AbstractItem $subject, $result)
    {
        if ($this->checkoutSession->getData('stopCalcRowTotal')) {
            return $subject->getBaseOriginalPrice();
        }

        return $result;
    }

    /**
     * After plugin to fix base_calculation_price_original while creating order from admin
     *
     * @param AbstractItem $subject
     * @param $result
     * @return float
     */
    public function afterGetBaseCalculationPriceOriginal(AbstractItem $subject, $result)
    {
        if ($this->checkoutSession->getData('stopCalcRowTotal')) {
            return $subject->getBaseOriginalPrice();
        }

        return $result;
    }
}
