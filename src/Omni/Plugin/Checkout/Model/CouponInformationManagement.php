<?php

namespace Ls\Omni\Plugin\Checkout\Model;

use Exception;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\CouponManagement;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;

/**
 * Class CouponInformationManagement
 * @package Ls\Omni\Plugin\Checkout\Model
 */
class CouponInformationManagement
{
    /** @var QuoteRepository */
    public $quoteRepository;

    /** @var BasketHelper; */
    public $basketHelper;

    /**
     * CouponInformationManagement constructor.
     * @param QuoteRepository $quoteRepository
     * @param BasketHelper $basketHelper
     */
    public function __construct(
        QuoteRepository $quoteRepository,
        BasketHelper $basketHelper
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->basketHelper    = $basketHelper;
    }

    /**
     * @param CouponManagement $subject
     * @param $proceed
     * @param $cartId
     * @param $couponCode
     * @return bool
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     * @throws InvalidEnumException
     */
    // @codingStandardsIgnoreLine
    public function aroundSet(CouponManagement $subject, $proceed, $cartId, $couponCode)
    {
        $couponCode = trim($couponCode);
        /** @var  Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }
        if (!$quote->getStoreId()) {
            throw new NoSuchEntityException(__('Cart isn\'t assigned to correct store'));
        }
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $status = $this->basketHelper->setCouponCode($couponCode);
        if ($status == "success") {
            return true;
        } else {
            throw new NoSuchEntityException(__($status));
        }
    }

    /**
     * @param CouponManagement $subject
     * @param $cartId
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    // @codingStandardsIgnoreLine
    public function beforeRemove(CouponManagement $subject, $cartId)
    {
        /** @var  Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }
        $quote->getShippingAddress()->setCollectShippingRates(true);
        try {
            $this->basketHelper->setCouponCode('');
        } catch (Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete coupon code'));
        }
    }
}
