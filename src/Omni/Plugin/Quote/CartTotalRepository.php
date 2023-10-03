<?php

namespace Ls\Omni\Plugin\Quote;

use Closure;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\TotalsExtensionFactory;
use Magento\Quote\Api\Data\TotalsExtensionInterface;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Model\Quote;

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
     * @var LSR
     */
    public $lsr;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param TotalsExtensionFactory $totalExtensionFactory
     * @param LoyaltyHelper $helper
     * @param LSR $lsr
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        TotalsExtensionFactory $totalExtensionFactory,
        LoyaltyHelper $helper,
        LSR $lsr
    ) {
        $this->quoteRepository       = $quoteRepository;
        $this->totalExtensionFactory = $totalExtensionFactory;
        $this->loyaltyHelper         = $helper;
        $this->lsr                   = $lsr;
    }

    /**
     * Setting coupon code, gift card, subtotal, tax, discount in the quote and persisting it
     *
     * @param CartTotalRepositoryInterface $subject
     * @param Closure $proceed
     * @param $cartId
     * @return TotalsInterface
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
                'balance'   => $this->loyaltyHelper->getLoyaltyPointsAvailableToCustomer(),
            ];

            /** @var TotalsExtensionInterface $totalsExtension */
            $totalsExtension = $quoteTotals->getExtensionAttributes() ?: $this->totalExtensionFactory->create();
            $totalsExtension->setLoyaltyPoints($pointsConfig);
            $quoteTotals->setExtensionAttributes($totalsExtension);
        }

        return $quoteTotals;
    }
}
