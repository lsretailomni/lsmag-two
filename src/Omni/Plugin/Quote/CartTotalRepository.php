<?php
declare(strict_types=1);

namespace Ls\Omni\Plugin\Quote;

use Closure;
use GuzzleHttp\Exception\GuzzleException;
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
     * @param CartRepositoryInterface $quoteRepository
     * @param TotalsExtensionFactory $totalExtensionFactory
     * @param LoyaltyHelper $loyaltyHelper
     * @param LSR $lsr
     */
    public function __construct(
        public CartRepositoryInterface $quoteRepository,
        public TotalsExtensionFactory $totalExtensionFactory,
        public LoyaltyHelper $loyaltyHelper,
        public LSR $lsr
    ) {
    }

    /**
     * Setting coupon code, gift card, subtotal, tax, discount in the quote and persisting it
     *
     * @param CartTotalRepositoryInterface $subject
     * @param Closure $proceed
     * @param $cartId
     * @return TotalsInterface
     * @throws NoSuchEntityException|GuzzleException
     */
    public function aroundGet(CartTotalRepositoryInterface $subject, Closure $proceed, $cartId)
    {
        /** @var TotalsInterface $quoteTotals */
        $quoteTotals = $proceed($cartId);
        /** @var Quote $quote */
        $quote = $this->quoteRepository->get($cartId);

        if ($this->lsr->isLSR($quote->getStoreId())) {
            $pointRate = $this->loyaltyHelper->getPointRate();

            if ($pointRate > 0) {
                $pointsConfig = [
                    'rateLabel' => $this->loyaltyHelper->formatValue(1 / $pointRate),
                    'balance'   => $this->loyaltyHelper->getLoyaltyPointsAvailableToCustomer(),
                ];

                /** @var TotalsExtensionInterface $totalsExtension */
                $totalsExtension = $quoteTotals->getExtensionAttributes() ?: $this->totalExtensionFactory->create();
                $totalsExtension->setLoyaltyPoints($pointsConfig);
                $quoteTotals->setExtensionAttributes($totalsExtension);
            }
        }

        return $quoteTotals;
    }
}
