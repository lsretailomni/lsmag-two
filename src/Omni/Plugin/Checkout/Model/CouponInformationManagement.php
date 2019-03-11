<?php

namespace Ls\Omni\Plugin\Checkout\Model;

use \Magento\Quote\Api\CouponManagementInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use \Ls\Omni\Helper\BasketHelper;

/**
 * Class ShippingInformationManagement
 * @package Ls\Omni\Plugin\Checkout\Model
 */
class CouponInformationManagement
{
    /** @var \Magento\Quote\Model\QuoteRepository */
    public $quoteRepository;

    public $basketHelper;

    /**
     * ShippingInformationManagement constructor.
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     */

    public function __construct(
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        BasketHelper $basketHelper
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->basketHelper = $basketHelper;
    }

    public function aroundSet(\Magento\Quote\Model\CouponManagement $subject,$proceed,$cartId,$couponCode)
    {
        $couponCode = trim($couponCode);
        /** @var  \Magento\Quote\Model\Quote $quote */
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
        } else {
            throw new CouldNotSaveException(__($status));
        }
    }

    public function beforeRemove(\Magento\Quote\Model\CouponManagement $subject, $cartId)
    {
        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }
        $quote->getShippingAddress()->setCollectShippingRates(true);
        try {
            $this->basketHelper->setCouponCode('');
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete coupon code'));
        }
    }
}
