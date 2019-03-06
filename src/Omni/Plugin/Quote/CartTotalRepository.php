<?php

namespace Ls\Omni\Plugin\Quote;

use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\TotalsExtensionFactory;
use Ls\Omni\Helper\LoyaltyHelper;

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
    protected $totalExtensionFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var LoyaltyHelper
     */
    protected $loyaltyHelper;

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
        LoyaltyHelper $helper
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->totalExtensionFactory = $totalExtensionFactory;
        $this->request = $request;
        $this->loyaltyHelper = $helper;
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
        $quoteTotals->setExtensionAttributes($totalsExtension);
        return $quoteTotals;
    }
}