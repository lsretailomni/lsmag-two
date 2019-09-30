<?php

namespace Ls\Omni\Plugin\Checkout\CustomerData;

use \Ls\Omni\Helper\Data;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Psr\Log\LoggerInterface;

/**
 * Class Cart
 * @package Ls\Omni\Plugin\Checkout\CustomerData
 */
class Cart
{

    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    /**
     * @var $quoteRepository
     */
    public $quoteRepository;

    /**
     * @var $checkoutHelper
     */
    public $checkoutHelper;

    /**
     * @var Data
     */
    public $data;

    /**
     * @var \Ls\Omni\Helper\BasketHelper
     */
    public $basketHelper;

    /** @var LoggerInterface */
    public $logger;


    /**
     * Cart constructor.
     * @param CheckoutSession $checkoutSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Checkout\Helper\Data $checkoutHelper
     * @param Data $data
     * @param \Ls\Omni\Helper\BasketHelper $basketHelper
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        Data $data,
        \Ls\Omni\Helper\BasketHelper $basketHelper,
        LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutHelper = $checkoutHelper;
        $this->data = $data;
        $this->basketHelper = $basketHelper;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Checkout\CustomerData\Cart $subject
     * @param array $result
     * @return array
     */
    public function afterGetSectionData(\Magento\Checkout\CustomerData\Cart $subject, array $result)
    {
        try {
            $quote = $this->checkoutSession->getQuote();
            $grandTotalAmount = $this->data->getOrderBalance(
                $quote->getLsGiftCardAmountUsed(),
                $quote->getLsPointsSpent(),
                $this->basketHelper->getBasketSessionValue()
            );
            if ($grandTotalAmount > 0) {
                $result['subtotalAmount'] = $grandTotalAmount;
                $result['subtotal'] = isset($grandTotalAmount)
                    ? $this->checkoutHelper->formatPrice($grandTotalAmount)
                    : 0;
            }
            return $result;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
