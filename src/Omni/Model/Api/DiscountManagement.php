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
        $existingBasketCalculation = $this->basketHelper->getOneListCalculation();

        //Added this in case if we don't get session values in pwa
        if (empty($existingBasketCalculation)) {
            $quote      = $this->basketHelper->getCartRepositoryObject()->get($cartId);
            $basketData = $quote->getBasketResponse();
            /** @var  Order $existingBasketCalculation */
            // phpcs:ignore Magento2.Security.InsecureFunction.FoundWithAlternative
            $existingBasketCalculation = ($basketData) ? unserialize($basketData) : $basketData;
            if ($existingBasketCalculation) {
                $oneList = $this->basketHelper->getOneListAdmin(
                    $quote->getCustomerEmail(),
                    $quote->getStore()->getWebsiteId(),
                    $quote->getCustomerIsGuest()
                );
                $this->basketHelper->setOneListQuote($quote, $oneList);
            }
        }

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
