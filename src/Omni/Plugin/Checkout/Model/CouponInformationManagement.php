<?php

namespace Ls\Omni\Plugin\Checkout\Model;

use \Magento\Quote\Api\CouponManagementInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Core\Model\LSR;

/**
 * Class CouponInformationManagement
 * @package Ls\Omni\Plugin\Checkout\Model
 */
class CouponInformationManagement
{
    /** @var \Magento\Quote\Model\QuoteRepository */
    public $quoteRepository;

    /** @var \Ls\Omni\Helper\BasketHelper; */
    public $basketHelper;

    /**
     * CouponInformationManagement constructor.
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param BasketHelper $basketHelper
     */
    public function __construct(
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        BasketHelper $basketHelper
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->basketHelper = $basketHelper;
    }

    /**
     * @param \Magento\Quote\Model\CouponManagement $subject
     * @param $proceed
     * @param $cartId
     * @param $couponCode
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
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

    /**
     * @param \Magento\Quote\Model\CouponManagement $subject
     * @param $cartId
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
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
