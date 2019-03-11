<?php

namespace Ls\Omni\Model\Api;

use Magento\Checkout\Api\Data\TotalsInformationInterface;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use \Ls\Omni\Api\PointsManagementInterface;

/**
 * Class PointsManagement
 * @package Mageplaza\RewardPoints\Model\Api
 */
class PointsManagement implements PointsManagementInterface
{

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * Cart total repository.
     *
     * @var \Magento\Quote\Api\CartTotalRepositoryInterface
     */
    protected $cartTotalRepository;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * PointsManagement constructor.
     * @param CartRepositoryInterface $cartRepository
     * @param CartTotalRepositoryInterface $cartTotalRepository
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        CartTotalRepositoryInterface $cartTotalRepository,
        CheckoutSession $checkoutSession
    ) {
        $this->cartRepository = $cartRepository;
        $this->cartTotalRepository = $cartTotalRepository;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * {@inheritdoc}
     */
    public function updatePoints($cartId, $pointSpent)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->cartRepository->get($cartId);
        $quote->setLsPointsSpent($pointSpent);
        $this->validateQuote($quote);
        $quote->collectTotals();
        $this->cartRepository->save($quote);
        return $this->cartTotalRepository->get($quote->getId());
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    protected function validateQuote(\Magento\Quote\Model\Quote $quote)
    {
        if ($quote->getItemsCount() === 0) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Totals calculation is not applicable to empty cart.')
            );
        }
    }
}
