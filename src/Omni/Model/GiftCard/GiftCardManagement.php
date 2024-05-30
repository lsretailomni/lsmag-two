<?php

namespace Ls\Omni\Model\GiftCard;

use Ls\Core\Model\LSR;
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
     * @var LSR
     */
    public $lsr;

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
        PriceHelper $priceHelper,
        LSR $lsr
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->giftCardHelper  = $giftCardHelper;
        $this->dataHelper      = $dataHelper;
        $this->basketHelper    = $basketHelper;
        $this->priceHelper     = $priceHelper;
        $this->lsr             = $lsr;
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
            //$pointRate = $storeCurrencyPointRate = $giftCardPointRate = $quotePointRate = 1;
            if (is_object($giftCardResponse)) {
//                if($this->lsr->getStoreCurrencyCode() == $this->giftCardHelper->getLocalCurrencyCode()) {
//                    $pointRate      = $this->giftCardHelper->getPointRate($giftCardResponse->getCurrencyCode());
//                    $quotePointRate = $pointRate;
//                    $case           = 1;
//                } elseif ($this->lsr->getStoreCurrencyCode() != $this->giftCardHelper->getLocalCurrencyCode()) {
//                    $storeCurrencyPointRate = $this->giftCardHelper->getPointRate($this->lsr->getStoreCurrencyCode());
//                    $giftCardPointRate      = $this->giftCardHelper->getPointRate($giftCardResponse->getCurrencyCode());
//                    $quotePointRate         = $giftCardPointRate;
//                    $case                   = 2;
//                }
//
//                if($pointRate > 0 || ($storeCurrencyPointRate > 0 && $giftCardPointRate > 0)) {
//                    $giftCardBalanceAmount = match($case) {
//                        1 => $giftCardResponse->getBalance() / $pointRate,
//                        2 => ($giftCardResponse->getBalance() / $giftCardPointRate) * $storeCurrencyPointRate,
//                        default => $giftCardResponse->getBalance(),
//                    };
//                } else {
//                    $giftCardBalanceAmount = $giftCardResponse->getBalance();
//                }

                $convertedGiftCardBalanceArr = $this->giftCardHelper->getConvertedGiftCardBalance($giftCardResponse);
                $giftCardBalanceAmount       = $convertedGiftCardBalanceArr['gift_card_balance_amount'];
                $quotePointRate              = $convertedGiftCardBalanceArr['quote_point_rate'];
            } else {
                $giftCardBalanceAmount = $giftCardResponse;
            }
        }

        if (empty($giftCardResponse)) {
            throw new CouldNotSaveException(__('The gift card is not valid.'));
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
            $cartQuote->setLsGiftCardAmountUsed($giftCardAmount)->setLsGiftCardNo($giftCardNo)
                ->setLsGiftCardPin($giftCardPin)->setLsGiftCardCnf($quotePointRate)->collectTotals();
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
