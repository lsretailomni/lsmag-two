<?php

namespace Ls\Omni\Model\Api;

use \Ls\Omni\Api\DiscountManagementInterface;
use \Ls\Omni\Client\Ecommerce\Entity\Order;
use \Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Helper\GiftCardHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;

class DiscountManagement implements DiscountManagementInterface
{
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

        if (!$existingBasketCalculation ||
            empty($existingBasketCalculation->getOrderDiscountLines()->getOrderDiscountLine())
        ) {
            $discountsValidity = [
                'valid'   => true,
                'msg'     => '',
                'remarks' => 'discount'
            ];
        } else {
            $existingBasketTotal = $existingBasketCalculation->getTotalAmount();
            $this->basketHelper->setCalculateBasket('1');
            $this->basketHelper->syncBasketWithCentral($cartId);

            /** @var  Order $newBasketCalculation */
            if ($newBasketCalculation = $this->basketHelper->getOneListCalculation()) {
                $newBasketTotal    = $newBasketCalculation->getTotalAmount();
                $discountMsg       = $newBasketTotal > $existingBasketTotal ?
                    __('Unfortunately since your discount is no longer valid your order summary has been updated.') :
                    __('Your order summary has been updated.');
                $discountsValidity = [
                    'valid'   => $newBasketTotal == $existingBasketTotal,
                    'msg'     => $discountMsg,
                    'remarks' => 'discount'
                ];
            } else {
                $discountsValidity = [
                    'valid'   => true,
                    'msg'     => '',
                    'remarks' => 'discount'
                ];
            }
        }

        if (empty($giftCardNo)) {
            $giftCardValidity = [
                'valid'   => true,
                'msg'     => '',
                'remarks' => 'giftcard'
            ];
        } else {
            $giftCardValidation = $this->validateGiftCardExpiry($quote, $giftCardNo, $giftCardPin);

            $giftCardValidity = [
                'valid'   => $giftCardValidation,
                'msg'     => __(
                    'Unfortunately since applied gift card has been expired your order summary has been updated.'
                ),
                'remarks' => 'giftcard'
            ];
        }

        return ['discountValidity' => $discountsValidity, 'giftcardValidity' => $giftCardValidity];
    }

    /**
     * Check to see if applied gift card is still valid
     *
     * @param $quote
     * @param $giftCardNo
     * @param $giftCardPin
     * @return bool
     * @throws \Exception
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
