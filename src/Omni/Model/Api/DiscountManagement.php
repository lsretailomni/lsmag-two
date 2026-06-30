<?php
declare(strict_types=1);

namespace Ls\Omni\Model\Api;

use Exception;
use \Ls\Omni\Api\DiscountManagementInterface;
use \Ls\Omni\Client\Ecommerce\Entity\Order;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\GiftCardHelper;
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
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param BasketHelper $basketHelper
     * @param GiftCardHelper $giftCardHelper
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        public QuoteIdMaskFactory $quoteIdMaskFactory,
        public BasketHelper $basketHelper,
        public GiftCardHelper $giftCardHelper,
        public CartRepositoryInterface $cartRepository
    ) {
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

        // Decode entries and use the first entry's code/pin for expiry validation
        $entries     = json_decode((string)$quote->getLsPosDataEntries(), true) ?: [];
        $firstEntry  = !empty($entries) ? $entries[0] : [];
        $giftCardNo  = $firstEntry['entry_no'] ?? null;
        $giftCardPin = $firstEntry['pin_code'] ?? null;
        $remarks     = self::NON_COUPON_REMARKS;

        if (!$existingBasketCalculation ||
            empty($existingBasketCalculation->getMobiletransdiscountline())
        ) {
            $discountsValidity = [
                'valid'   => true,
                'msg'     => '',
                'type'    => self::DISCOUNT_TYPE,
                'remarks' => $remarks
            ];
        } else {
            $mobileTransaction = current((array) $existingBasketCalculation->getMobiletransaction());
            $existingBasketTotal = $mobileTransaction->getGrossamount();
            $this->basketHelper->setCalculateBasket('1');
            $basketData = $this->basketHelper->syncBasketWithCentral($cartId);

            if (is_string($basketData) &&
                str_contains($basketData, sprintf('Coupon %s is not valid', $quote->getCouponCode()))
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
                $newMobileTransaction = current((array) $newBasketCalculation->getMobiletransaction());
                $newBasketTotal    = $newMobileTransaction->getGrossamount();
                $discountMsg       = $newBasketTotal > $existingBasketTotal ?
                    __($this->basketHelper->getLsrModel()->getDiscountValidationMsg()) :
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
            // Validate all entries; if any is expired, clear the whole list
            $allValid = true;
            foreach ($entries as $entry) {
                if (!$this->validateGiftCardExpiry($quote, $entry['entry_no'] ?? '', $entry['pin_code'] ?? null, $entry['entry_type'] ?? 'GIFTCARDNO')) {
                    $allValid = false;
                    break;
                }
            }

            $giftCardValidity = [
                'valid'   => $allValid,
                'msg'     => __(
                    $this->basketHelper->getLsrModel()->getGiftCardValidationMsg()
                ),
                'type'    => self::GIFTCARD_TYPE,
                'remarks' => $remarks
            ];
        }

        return [$discountsValidity, $giftCardValidity];
    }

    /**
     * Check to see if applied gift card / voucher entry is still valid
     *
     * @param $quote
     * @param $giftCardNo
     * @param $giftCardPin
     * @param string $entryType
     * @return bool
     * @throws Exception
     */
    public function validateGiftCardExpiry($quote, $giftCardNo, $giftCardPin, string $entryType = 'GIFTCARDNO')
    {
        $giftCardResponse = $this->giftCardHelper->getGiftCardBalance($giftCardNo, $giftCardPin, $entryType);

        if (is_object($giftCardResponse)) {
            if ($this->giftCardHelper->isGiftCardExpired($giftCardResponse)) {
                $quote->setLsPosDataEntries(null);
                $quote->collectTotals();
                $this->cartRepository->save($quote);

                return false;
            }
        }

        return true;
    }
}
