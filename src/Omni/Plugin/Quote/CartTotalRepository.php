<?php

namespace Ls\Omni\Plugin\Quote;

use Closure;
use Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Helper\LoyaltyHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\TotalsExtensionFactory;
use Magento\Quote\Api\Data\TotalsExtensionInterface;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Model\Quote;
use Magento\SalesRule\Model\Coupon;

/**
 * Class CartTotalRepository
 * @package Ls\Omni\Plugin\Quote
 */
class CartTotalRepository
{
    /**
     * Quote repository.
     *
     * @var CartRepositoryInterface
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
     * @var Coupon
     */
    private $coupon;

    /**
     * @var BasketHelper
     */
    private $basketHelper;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote
     */
    private $quoteResourceModel;

    /**
     * CartTotalRepository constructor.
     * @param CartRepositoryInterface $quoteRepository
     * @param TotalsExtensionFactory $totalExtensionFactory
     * @param RequestInterface $request
     * @param LoyaltyHelper $helper
     * @param Coupon $coupon
     * @param BasketHelper $basketHelper
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResourceModel
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        TotalsExtensionFactory $totalExtensionFactory,
        RequestInterface $request,
        LoyaltyHelper $helper,
        Coupon $coupon,
        BasketHelper $basketHelper,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResourceModel
    ) {
        $this->quoteRepository       = $quoteRepository;
        $this->totalExtensionFactory = $totalExtensionFactory;
        $this->request               = $request;
        $this->loyaltyHelper         = $helper;
        $this->coupon                = $coupon;
        $this->basketHelper          = $basketHelper;
        $this->quoteResourceModel    = $quoteResourceModel;
    }

    /**
     * Setting couponcode, giftcard, subtotal, tax, discount in the quote and persisting it
     * @param CartTotalRepositoryInterface $subject
     * @param Closure $proceed
     * @param $cartId
     * @return TotalsInterface
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     */
    public function aroundGet(CartTotalRepositoryInterface $subject, Closure $proceed, $cartId)
    {
        /** @var TotalsInterface $quoteTotals */
        $quoteTotals = $proceed($cartId);

        /** @var Quote $quote */
        $quote   = $this->quoteRepository->get($cartId);
        $storeId = $quote->getStoreId();

        $pointsConfig = [
            'rateLabel' => $quote->getBaseCurrencyCode() . ' ' . round($this->loyaltyHelper->getPointRate() * 10, 2),
            'balance'   => $this->loyaltyHelper->getMemberPoints(),
        ];

        /** @var TotalsExtensionInterface $totalsExtension */
        $totalsExtension = $quoteTotals->getExtensionAttributes() ?: $this->totalExtensionFactory->create();
        $totalsExtension->setLoyaltyPoints($pointsConfig);
        $couponCode = $this->basketHelper->checkoutSession->getCouponCode();
        $amount     = 0;
        $basketData = $this->basketHelper->getBasketSessionValue();
        if (isset($basketData)) {
            $pointDiscount  = $quote->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
            $giftCardAmount = $quote->getLsGiftCardAmountUsed();
            if ($pointDiscount > 0.001) {
                $quote->setLsPointsDiscount($pointDiscount);
            }

            $amount = -$basketData->getTotalDiscount();
            if ($amount <= 0) {
                if (!empty($couponCode)) {
                    $quoteTotals->setCouponCode($couponCode);
                    $quote->setCouponCode($couponCode);
                    $quote->getShippingAddress()->setCouponCode($couponCode);
                }
                $quote->getShippingAddress()->setTaxAmount(
                    $basketData->getTotalAmount() - $basketData->getTotalNetAmount()
                );
                $quote->getShippingAddress()->setSubtotal(
                    $basketData->getTotalAmount() + $basketData->getTotalDiscount()
                );
                $quote->getShippingAddress()->setBaseSubtotal(
                    $basketData->getTotalAmount() + $basketData->getTotalDiscount()
                );
                $quote->collectTotals();
                $this->quoteResourceModel->save($quote);
                $quoteTotals->setTaxAmount($basketData->getTotalAmount() - $basketData->getTotalNetAmount());
                $quoteTotals->setBaseTaxAmount($basketData->getTotalAmount() - $basketData->getTotalNetAmount());
                $quoteTotals->setBaseDiscountAmount($amount);
                $quoteTotals->setSubtotal($basketData->getTotalAmount() + $basketData->getTotalDiscount());
                $quoteTotals->setBaseSubtotal($basketData->getTotalAmount() + $basketData->getTotalDiscount());
                $quoteTotals->setDiscountAmount($amount);
            }
        }
        $quoteTotals->setExtensionAttributes($totalsExtension);
        return $quoteTotals;
    }
}
