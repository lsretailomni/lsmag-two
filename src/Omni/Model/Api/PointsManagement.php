<?php

namespace Ls\Omni\Model\Api;

use \Ls\Omni\Api\PointsManagementInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Exception;
use Psr\Log\LoggerInterface;

class PointsManagement implements PointsManagementInterface
{

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * Cart total repository.
     *
     * @var CartTotalRepositoryInterface
     */
    protected $cartTotalRepository;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * PointsManagement constructor.
     * @param CartRepositoryInterface $cartRepository
     * @param CartTotalRepositoryInterface $cartTotalRepository
     * @param CheckoutSession $checkoutSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        CartTotalRepositoryInterface $cartTotalRepository,
        CheckoutSession $checkoutSession,
        LoggerInterface $logger
    ) {
        $this->cartRepository      = $cartRepository;
        $this->cartTotalRepository = $cartTotalRepository;
        $this->checkoutSession     = $checkoutSession;
        $this->logger              = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function updatePoints($cartId, $pointSpent)
    {
        try {
            /** @var Quote $quote */
            $quote = $this->cartRepository->get($cartId);
            $quote->setLsPointsSpent($pointSpent);
            $this->validateQuote($quote);
            $quote->collectTotals();
            $this->cartRepository->save($quote);
            return $this->cartTotalRepository->get($quote->getId());
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @param Quote $quote
     * @return void
     * @throws LocalizedException
     */
    protected function validateQuote(Quote $quote)
    {
        if ($quote->getItemsCount() == 0) {
            throw new LocalizedException(
                __('Totals calculation is not applicable to empty cart.')
            );
        }
    }
}
