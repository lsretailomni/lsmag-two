<?php

namespace Ls\Omni\Model\Api;

use Exception;
use \Ls\Omni\Api\DiscountManagementInterface;
use \Ls\Omni\Client\Ecommerce\Entity\Order;
use \Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Helper\GiftCardHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;

class DiscountManagement implements DiscountManagementInterface
{
    public const GIFTCARD_TYPE = 'giftcard';
    public const DISCOUNT_TYPE = 'discount';
    public const COUPON_REMARKS = 'coupon';
    public const NON_COUPON_REMARKS = 'non coupon';
    public const GIFTCARD_REMARKS = 'giftcard';

    /**
     * @var QuoteIdMaskFactory
     */
    public $quoteIdMaskFactory;

    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /** @var GiftCardHelper */
    public $giftCardHelper;

    /**
     * @var CartRepositoryInterface
     */
    public $cartRepository;

    /**
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param BasketHelper $basketHelper
     * @param GiftCardHelper $giftCardHelper
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        BasketHelper $basketHelper,
        GiftCardHelper $giftCardHelper,
        CartRepositoryInterface $cartRepository
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->basketHelper       = $basketHelper;
        $this->giftCardHelper     = $giftCardHelper;
        $this->cartRepository     = $cartRepository;
    }

    /**
     * @inheritDoc
     */
    public function checkDiscountValidity($cartId)
    {
        if (!is_numeric($cartId)) {
            $cartId = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id')->getQuoteId();
        }
        $existingBasketCalculation = $this->basketHelper->getOneListCalculation();
        $quote                     = $this->basketHelper->getCartRepositoryObject()->get($cartId);
        //Added this in case if we don't get session values in pwa
        if (empty($existingBasketCalculation)) {
            $basketData = $quote->getBasketResponse();
            /** @var  Order $existingBasketCalculation */
            // phpcs:ignore Magento2.Security.InsecureFunction.FoundWithAlternative
            $existingBasketCalculation = ($basketData) ? unserialize($basketData) : $basketData;
            if ($existingBasketCalculation) {
                $oneList = $this->basketHelper->getOneListAdmin(
                    $quote->getCustomerEmail(),
                    $quote->getStore()->getWebsiteId(),
                    $quote->getCustomerIsGuest()
                );
                $this->basketHelper->setOneListQuote($quote, $oneList);
            }
        }

        $giftCardNo  = $quote->getLsGiftCardNo();
        $giftCardPin = $quote->getLsGiftCardPin();
        $remarks     = self::NON_COUPON_REMARKS;

        if (!$existingBasketCalculation ||
            empty($existingBasketCalculation->getOrderDiscountLines()->getOrderDiscountLine())
        ) {
            $discountsValidity = [
                'valid'   => true,
                'msg'     => '',
                'type'    => self::DISCOUNT_TYPE,
                'remarks' => $remarks
            ];
        } else {
            $existingBasketTotal = $existingBasketCalculation->getTotalAmount();
            $this->basketHelper->setCalculateBasket('1');
            $basketData = $this->basketHelper->syncBasketWithCentral($cartId);

            if (is_string($basketData) &&
                str_contains($basketData, sprintf(' - [1000]-Coupon %s is not valid', $quote->getCouponCode()))
            ) {
                $status = $this->basketHelper->setCouponCode('');

                if ($status === null) {
                    $basketData = $this->basketHelper->getOneListCalculation();
                    $remarks    = self::COUPON_REMARKS;
                }
            }
            $newBasketCalculation = $this->basketHelper->getOneListCalculation();

            /** @var  Order $newBasketCalculation */
            if (is_object($basketData) && $newBasketCalculation) {
                $newBasketTotal    = $newBasketCalculation->getTotalAmount();
                $discountMsg       = $newBasketTotal > $existingBasketTotal ?
                    __('Unfortunately since your discount is no longer valid your order summary has been updated.') :
                    __('Your order summary has been updated.');
                $discountsValidity = [
                    'valid'   => $newBasketTotal == $existingBasketTotal,
                    'msg'     => $discountMsg,
                    'type'    => self::DISCOUNT_TYPE,
                    'remarks' => $remarks
                ];
            } else {
                $discountsValidity = [
                    'valid'   => true,
                    'msg'     => '',
                    'type'    => self::DISCOUNT_TYPE,
                    'remarks' => $remarks
                ];
            }
        }

        $remarks = self::GIFTCARD_REMARKS;

        if (empty($giftCardNo)) {
            $giftCardValidity = [
                'valid'   => true,
                'msg'     => '',
                'type'    => self::GIFTCARD_TYPE,
                'remarks' => $remarks
            ];
        } else {
            $giftCardValidation = $this->validateGiftCardExpiry($quote, $giftCardNo, $giftCardPin);

            $giftCardValidity = [
                'valid'   => $giftCardValidation,
                'msg'     => __(
                    'Unfortunately since your applied gift card has been expired order summary has been updated.'
                ),
                'type'    => self::GIFTCARD_TYPE,
                'remarks' => $remarks
            ];
        }

        return [$discountsValidity, $giftCardValidity];
    }

    /**
     * Check to see if applied gift card is still valid
     *
     * @param $quote
     * @param $giftCardNo
     * @param $giftCardPin
     * @return bool
     * @throws Exception
     */
    public function validateGiftCardExpiry($quote, $giftCardNo, $giftCardPin)
    {
        $giftCardResponse = $this->giftCardHelper->getGiftCardBalance($giftCardNo, $giftCardPin);

        if (is_object($giftCardResponse)) {
            if ($this->giftCardHelper->isGiftCardExpired($giftCardResponse)) {
                $quote->setLsGiftCardNo(null)->setLsGiftCardPin(null)->setLsGiftCardAmountUsed(0);
                $quote->collectTotals();
                $this->cartRepository->save($quote);

                return false;
            }
        }

        return true;
    }
}
