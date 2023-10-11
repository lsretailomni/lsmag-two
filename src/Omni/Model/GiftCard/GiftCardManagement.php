<?php

namespace Ls\Omni\Model\GiftCard;

use \Ls\Omni\Helper\GiftCardHelper;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
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
     * @var Data
     */
    public $dataHelper;

    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * @var PriceHelper
     */
    public $priceHelper;

    /**
     * GiftCardManagement constructor.
     * @param CartRepositoryInterface $quoteRepository
     * @param GiftCardHelper $giftCardHelper
     * @param Data $dataHelper
     * @param BasketHelper $basketHelper
     * @param PriceHelper $priceHelper
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        GiftCardHelper $giftCardHelper,
        Data $dataHelper,
        BasketHelper $basketHelper,
        PriceHelper $priceHelper
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->giftCardHelper  = $giftCardHelper;
        $this->dataHelper      = $dataHelper;
        $this->basketHelper    = $basketHelper;
        $this->priceHelper     = $priceHelper;
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
        $quote         = $this->quoteRepository->getActive($cartId);
        $giftCardNo    = $quote->getLsGiftCardNo();
        $giftCardArray = [];
        if (!empty($giftCardNo)) {
            $giftCardArray = [
                'code'   => $giftCardNo,
                'amount' => $quote->getLsGiftCardAmountUsed(),
                'pin'    => $quote->getLsGiftCardPin()];
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
    public function apply($cartId, $giftCardNo, $giftCardPin, $giftCardAmount)
    {
        $giftCardBalanceAmount = 0;

        try {
            /** @var Quote $cart */
            $cartQuote = $this->quoteRepository->get($cartId);
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(
                __('Could not find a cart with ID 1 %1', $cartId)
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
            $giftCardResponse = $this->giftCardHelper->getGiftCardBalance($giftCardNo, $giftCardPin);

            if (is_object($giftCardResponse)) {
                $giftCardBalanceAmount = $giftCardResponse->getBalance();
            } else {
                $giftCardBalanceAmount = $giftCardResponse;
            }
        }

        if (empty($giftCardResponse)) {
            throw new CouldNotSaveException(__('The gift card code %1 is not valid.', $giftCardNo));
        }

        $itemsCount            = $cartQuote->getItemsCount();
        $orderBalance          = $this->dataHelper->getOrderBalance(
            0,
            $cartQuote->getLsPointsSpent(),
            $this->basketHelper->getBasketSessionValue()
        );
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
                    ' than order balance (%2).',
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
        if ($itemsCount) {
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
            try {
                $giftCardAmount = 0;
                $giftCardNo     = null;
                $giftCardPin    = null;
                $cartQuote->getShippingAddress()->setCollectShippingRates(true);
                $cartQuote->setLsGiftCardAmountUsed($giftCardAmount)->setLsGiftCardNo($giftCardNo)
                    ->setLsGiftCardPin($giftCardPin);
                $cartQuote->setTotalsCollectedFlag(false)->collectTotals();
                $this->quoteRepository->save($cartQuote);
            } catch (CouldNotSaveException $e) {
                throw new CouldNotSaveException(
                    __('Could not save cart with ID %1', $cartId)
                );
            }

        }
        return true;
    }
}
