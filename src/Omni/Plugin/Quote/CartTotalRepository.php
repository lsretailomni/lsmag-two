<?php

namespace Ls\Omni\Plugin\Quote;

use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\TotalsExtensionFactory;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Omni\Helper\BasketHelper;

/**
 * Class CartTotalRepository
 * @package Ls\Omni\Plugin\Quote
 */
class CartTotalRepository
{
    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var TotalsExtensionFactory
     */
    private $totalExtensionFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var LoyaltyHelper
     */
    private $loyaltyHelper;

    /**
     * @var \Magento\SalesRule\Model\Coupon
     */
    private $coupon;

    /**
     * @var Ls\Omni\Helper\BasketHelper
     */
    private $basketHelper;

    /**
     * CartTotalRepository constructor.
     * @param CartRepositoryInterface $quoteRepository
     * @param TotalsExtensionFactory $totalExtensionFactory
     * @param RequestInterface $request
     * @param LoyaltyHelper $helper
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        TotalsExtensionFactory $totalExtensionFactory,
        RequestInterface $request,
        LoyaltyHelper $helper,
        \Magento\SalesRule\Model\Coupon $coupon,
        BasketHelper $basketHelper
    )
    {
        $this->quoteRepository = $quoteRepository;
        $this->totalExtensionFactory = $totalExtensionFactory;
        $this->request = $request;
        $this->loyaltyHelper = $helper;
        $this->coupon = $coupon;
        $this->basketHelper = $basketHelper;
    }

    /**
     * @param CartTotalRepositoryInterface $subject
     * @param \Closure $proceed
     * @param $cartId
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundGet(CartTotalRepositoryInterface $subject, \Closure $proceed, $cartId)
    {
        /** @var \Magento\Quote\Api\Data\TotalsInterface $quoteTotals */
        $quoteTotals = $proceed($cartId);

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $storeId = $quote->getStoreId();

        $pointsConfig = [
            'rateLabel' => $quote->getBaseCurrencyCode() . ' ' . round($this->loyaltyHelper->getPointRate() * 10, 2),
            'balance' => $this->loyaltyHelper->getMemberPoints(),
        ];

        /** @var \Magento\Quote\Api\Data\TotalsExtensionInterface $totalsExtension */
        $totalsExtension = $quoteTotals->getExtensionAttributes() ?: $this->totalExtensionFactory->create();
        $totalsExtension->setLoyaltyPoints($pointsConfig);
        $couponCode = $this->basketHelper->checkoutSession->getCouponCode();
        if (!empty($couponCode)) {
            $quoteTotals->setCouponCode($couponCode);
            // @codingStandardsIgnoreLine
            $this->basketHelper->checkoutSession->getQuote()->setCouponCode($couponCode)->save();
        }

        $amount = 0;
        $basketData = $this->basketHelper->getBasketSessionValue();
        if (isset($basketData)) {
            $pointDiscount = $quote->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
            $giftCardAmount = $quote->getLsGiftCardAmountUsed();
            if ($pointDiscount > 0.001) {
                $quote->setLsPointsDiscount($pointDiscount);
            }

            $amount = -$basketData->getTotalDiscount();
            if ($amount <= 0) {
                $quote->getShippingAddress()->setDiscountAmount($basketData->getTotalAmount());
                $quote->getShippingAddress()->setTaxAmount($basketData->getTotalAmount() - $basketData->getTotalNetAmount());
                $quote->getShippingAddress()->setSubtotal($basketData->getTotalAmount() + $basketData->getTotalDiscount());
                $quote->getShippingAddress()->setBaseSubtotal($basketData->getTotalAmount() + $basketData->getTotalDiscount());
                $quote->collectTotals();
                $this->quoteRepository->save($quote);
                // @codingStandardsIgnoreLine
                $this->basketHelper->checkoutSession->getQuote()->setCouponCode($couponCode)->save();
                $quoteTotals->setTaxAmount($basketData->getTotalAmount() - $basketData->getTotalNetAmount());
                $quoteTotals->setBaseTaxAmount($basketData->getTotalAmount() - $basketData->getTotalNetAmount());
                $quoteTotals->setBaseDiscountAmount($amount);
                $quoteTotals->setSubtotal($basketData->getTotalAmount() + $basketData->getTotalDiscount());
                $quoteTotals->setBaseSubtotal($basketData->getTotalAmount() + $basketData->getTotalDiscount());
                $quoteTotals->setDiscountAmount($amount);
                $quoteTotals->setGrandTotal($basketData->getTotalAmount() + $basketData->getTotalDiscount());
                $quoteTotals->setBaseGrandTotal($basketData->getTotalAmount() + $basketData->getTotalDiscount());
            }
        }
        $quoteTotals->setExtensionAttributes($totalsExtension);
        return $quoteTotals;
    }
}