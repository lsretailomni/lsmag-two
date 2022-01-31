<?php

namespace Ls\Omni\Plugin\Quote;

use Closure;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\TotalsExtensionFactory;
use Magento\Quote\Api\Data\TotalsExtensionInterface;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResourceModel;
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
     * @var QuoteResourceModel
     */
    private $quoteResourceModel;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * CartTotalRepository constructor.
     * @param CartRepositoryInterface $quoteRepository
     * @param TotalsExtensionFactory $totalExtensionFactory
     * @param LoyaltyHelper $helper
     * @param Coupon $coupon
     * @param BasketHelper $basketHelper
     * @param QuoteResourceModel $quoteResourceModel
     * @param LSR $lsr
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        TotalsExtensionFactory $totalExtensionFactory,
        LoyaltyHelper $helper,
        Coupon $coupon,
        BasketHelper $basketHelper,
        QuoteResourceModel $quoteResourceModel,
        LSR $lsr
    ) {
        $this->quoteRepository       = $quoteRepository;
        $this->totalExtensionFactory = $totalExtensionFactory;
        $this->loyaltyHelper         = $helper;
        $this->coupon                = $coupon;
        $this->basketHelper          = $basketHelper;
        $this->quoteResourceModel    = $quoteResourceModel;
        $this->lsr                   = $lsr;
    }

    /**
     * Setting coupon code, gift card, subtotal, tax, discount in the quote and persisting it
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
        $quote = $this->quoteRepository->get($cartId);
        if ($this->lsr->isLSR($quote->getStoreId())) {
            $pointsConfig = [
                'rateLabel' => $quote->getBaseCurrencyCode() . ' ' . round(
                        $this->loyaltyHelper->getPointRate() * 10,
                        2
                    ),
                'balance'   => $this->loyaltyHelper->getMemberPoints(),
            ];

            /** @var TotalsExtensionInterface $totalsExtension */
            $totalsExtension = $quoteTotals->getExtensionAttributes() ?: $this->totalExtensionFactory->create();
            $totalsExtension->setLoyaltyPoints($pointsConfig);
            $couponCode = $quote->getCouponCode();
            $basketData = $this->basketHelper->getBasketSessionValue();
            if (isset($basketData)) {
                $pointDiscount  = $quote->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
                if ($pointDiscount > 0.001) {
                    $quote->setLsPointsDiscount($pointDiscount);
                }

                $amount = -$basketData->getTotalDiscount();
                if ($amount <= 0) {
                    if (!empty($couponCode)) {
                        $quoteTotals->setCouponCode($couponCode);
                        $quote->getShippingAddress()->setCouponCode($couponCode);
                        $quote->getBillingAddress()->setCouponCode($couponCode);
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
        }
        return $quoteTotals;
    }
}
