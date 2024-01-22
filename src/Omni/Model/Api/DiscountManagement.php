<?php

namespace Ls\Omni\Model\Api;

use \Ls\Omni\Api\DiscountManagementInterface;
use \Ls\Omni\Client\Ecommerce\Entity\Order;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Quote\Model\QuoteIdMaskFactory;

class DiscountManagement implements DiscountManagementInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    public $quoteIdMaskFactory;

    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param BasketHelper $basketHelper
     */
    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        BasketHelper $basketHelper
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->basketHelper       = $basketHelper;
    }

    /**
     * @inheritDoc
     */
    public function checkDiscountValidity($cartId)
    {
        if (!is_numeric($cartId)) {
            $cartId = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id')->getQuoteId();
        }

        /** @var  Order $existingBasketCalculation */
        $existingBasketCalculation = $this->basketHelper->getOneListCalculation();

        if (!$existingBasketCalculation) {
            return false;
        }

        if (empty($existingBasketCalculation->getOrderDiscountLines()->getOrderDiscountLine())) {
            return true;
        }

        $existingBasketTotal = $existingBasketCalculation->getTotalAmount();
        $this->basketHelper->setCalculateBasket('1');
        $this->basketHelper->syncBasketWithCentral($cartId);

        /** @var  Order $newBasketCalculation */
        $newBasketCalculation = $this->basketHelper->getOneListCalculation();

        $newBasketTotal = $newBasketCalculation->getTotalAmount();

        return $newBasketTotal == $existingBasketTotal;
    }
}
