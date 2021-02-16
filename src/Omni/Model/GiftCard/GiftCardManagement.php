<?php

namespace Ls\Omni\Model\GiftCard;

use \Ls\Omni\Helper\GiftCardHelper;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;

/**
 * GiftCardManagement class to handle gift card
 */
class GiftCardManagement
{
    /**
     * Sales quote repository
     *
     * @var CartRepositoryInterface
     */
    public $quoteRepository;

    /**
     * @var GiftCardHelper
     */
    public $giftCardHelper;

    /**
     * @var PriceHelper
     */
    public $priceHelper;

    /**
     * GiftCardManagement constructor.
     * @param CartRepositoryInterface $quoteRepository
     * @param GiftCardHelper $giftCardHelper
     * @param PriceHelper $priceHelper
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        GiftCardHelper $giftCardHelper,
        PriceHelper $priceHelper
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->giftCardHelper = $giftCardHelper;
        $this->priceHelper = $priceHelper;
    }

    /**
     * Get the gift card info from quote
     * @param $cartId
     * @return array
     * @throws NoSuchEntityException
     */
    public function get($cartId)
    {
        /** @var  Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $giftCardNo = $quote->getLsGiftCardNo();
        $giftCardArray = [];
        if (!empty($giftCardNo)) {
            $giftCardArray = ['code' => $giftCardNo, 'amount' => $quote->getLsGiftCardAmountUsed()];
        }
        return $giftCardArray;
    }

    /**
     * Apply the gift card to the quote
     * @param $cartId
     * @param $giftCardNo
     * @param $giftCardAmount
     * @return bool
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    public function apply($cartId, $giftCardNo, $giftCardAmount)
    {
        try {
            /** @var Quote $cart */
            $cartQuote = $this->quoteRepository->get($cartId);
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(
                __('Could not find a cart with ID %1', $cartId)
            );
        }

        $giftCardAmount = (float)$giftCardAmount;
        if (!is_numeric($giftCardAmount) || $giftCardAmount < 0) {
            throw new CouldNotSaveException(
                __(
                    'The gift card Amount "%1" is not valid.',
                    $this->priceHelper->currency($giftCardAmount, true, false)
                )
            );
        }
        if ($giftCardNo != null) {
            $giftCardResponse = $this->giftCardHelper->getGiftCardBalance($giftCardNo);

            if (is_object($giftCardResponse)) {
                $giftCardBalanceAmount = $giftCardResponse->getBalance();
            } else {
                $giftCardBalanceAmount = $giftCardResponse;
            }
        }

        if (empty($giftCardResponse)) {
            throw new CouldNotSaveException(__('The gift card code %1 is not valid.', $giftCardNo));
        }

        $itemsCount = $cartQuote->getItemsCount();
        $orderBalance = $cartQuote->getData('grand_total');

        $isGiftCardAmountValid = $this->giftCardHelper->isGiftCardAmountValid(
            $orderBalance,
            $giftCardAmount,
            $giftCardBalanceAmount
        );

        if ($isGiftCardAmountValid == false) {
            throw new CouldNotSaveException(
                __(
                    'The applied amount %3' .
                    ' is greater than gift card balance amount (%1) or it is greater' .
                    ' than order balance (Excl. Shipping Amount) (%2).',
                    $this->priceHelper->currency(
                        $giftCardBalanceAmount,
                        true,
                        false
                    ),
                    $this->priceHelper->currency(
                        $orderBalance,
                        true,
                        false
                    ),
                    $this->priceHelper->currency(
                        $giftCardAmount,
                        true,
                        false
                    )
                )
            );
        }
        if ($itemsCount && !empty($giftCardResponse) && $isGiftCardAmountValid) {
            $cartQuote->getShippingAddress()->setCollectShippingRates(true);
            $cartQuote->setLsGiftCardAmountUsed($giftCardAmount)->setLsGiftCardNo($giftCardNo);
            $cartQuote->setTotalsCollectedFlag(false)->collectTotals();
            $this->quoteRepository->save($cartQuote);
        }
        return true;
    }

    /**
     * Remove the gift card from quote
     * @param $cartId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function remove($cartId)
    {
        try {
            /** @var Quote $cart */
            $cartQuote = $this->quoteRepository->get($cartId);
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(
                __('Could not find a cart with ID %1', $cartId)
            );
        }
        if ($cartQuote->getLsGiftCardNo()) {
            $giftCardAmount = 0;
            $giftCardNo = null;
            $cartQuote->getShippingAddress()->setCollectShippingRates(true);
            $cartQuote->setLsGiftCardAmountUsed($giftCardAmount)->setLsGiftCardNo($giftCardNo);
            $cartQuote->setTotalsCollectedFlag(false)->collectTotals();
            $this->quoteRepository->save($cartQuote);
        }
        return true;
    }
}
