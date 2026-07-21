<?php
declare(strict_types=1);

namespace Ls\Omni\Model\GiftCard;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\GiftCardHelper;
use \Ls\Omni\Helper\VoucherHelper;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;

/**
 * GiftCardManagement handles multi-entry gift card / voucher redemption on the quote.
 *
 * Gift cards (entry_type = GIFTCARDNO) and vouchers (any other tender-type mapped entry_type)
 * are stored uniformly in the ls_pos_data_entries JSON column as an array of entries. This is
 * the single implementation shared by the Luma GiftCardUsed controller and the GraphQL
 * PosDataEntry resolvers.
 */
class GiftCardManagement
{
    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param GiftCardHelper $giftCardHelper
     * @param VoucherHelper $voucherHelper
     * @param Data $dataHelper
     * @param BasketHelper $basketHelper
     * @param PriceHelper $priceHelper
     * @param LSR $lsr
     */
    public function __construct(
        public CartRepositoryInterface $quoteRepository,
        public GiftCardHelper $giftCardHelper,
        public VoucherHelper $voucherHelper,
        public Data $dataHelper,
        public BasketHelper $basketHelper,
        public PriceHelper $priceHelper,
        public LSR $lsr
    ) {
    }

    /**
     * Apply (append) a gift card or voucher entry to the quote's ls_pos_data_entries column.
     *
     * Additive: each successful call appends a new entry rather than replacing the previous one.
     * When $entryType is null it is auto-detected via VoucherHelper::resolveCode.
     *
     * @param int $cartId
     * @param string|null $entryType LS Central entry type (e.g. GIFTCARDNO); null to auto-detect
     * @param string $code
     * @param string|null $pin
     * @param float $amount
     * @return bool
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    public function applyEntry(int $cartId, ?string $entryType, string $code, ?string $pin, float $amount): bool
    {
        try {
            /** @var Quote $cartQuote */
            $cartQuote = $this->quoteRepository->get($cartId);
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(__('Could not find a cart with ID %1', $cartId));
        }

        if ($amount < 0) {
            throw new CouldNotSaveException(
                __(
                    'The Gift Card / Voucher Amount "%1" is not valid.',
                    $this->priceHelper->currency($amount, true, false)
                )
            );
        }

        // Resolve the LS Central entry type and balance response.
        if ($entryType === null || $entryType === '') {
            $resolved = $this->voucherHelper->resolveCode($code, $pin);
            if ($resolved === null) {
                throw new CouldNotSaveException(__('The gift card / voucher is not valid.'));
            }
            $giftCardResponse = $resolved['response'];
            $entryType        = $resolved['entry_type'];
        } else {
            $giftCardResponse = $this->giftCardHelper->getGiftCardBalance($code, $pin, $entryType);
            if (empty($giftCardResponse)) {
                throw new CouldNotSaveException(__('The gift card / voucher is not valid.'));
            }
        }

        // Prevent applying the exact same code twice within its entry-type category.
        $raw            = $cartQuote->getLsPosDataEntries();
        $alreadyApplied = strtoupper($entryType) === 'GIFTCARDNO'
            ? $this->giftCardHelper->isGiftCardAlreadyAppliedInEntries($raw, $code)
            : $this->giftCardHelper->isVoucherAlreadyApplied($raw, $code);

        if ($alreadyApplied) {
            throw new CouldNotSaveException(__('This entry is already applied to your order.'));
        }

        if ($this->giftCardHelper->isGiftCardExpired($giftCardResponse) && $amount) {
            throw new CouldNotSaveException(
                __('Unfortunately, we can\'t apply this POS data entry since it has already expired.')
            );
        }

        $quotePointRate = $giftCardCurrencyCode = null;
        if (is_object($giftCardResponse)) {
            $converted             = $this->giftCardHelper->getConvertedGiftCardBalance($giftCardResponse);
            $giftCardBalanceAmount = $converted['gift_card_balance_amount'];
            $quotePointRate        = $converted['quote_point_rate'];
            $giftCardCurrencyCode  = $converted['gift_card_currency'];
        } else {
            $giftCardBalanceAmount = (float) $giftCardResponse;
        }

        // Order balance accounts for the total of ALL currently applied entries.
        $alreadyAppliedTotal = $this->giftCardHelper->getTotalFromEntries($raw);
        $orderBalance        = $this->dataHelper->getOrderBalance(
            $alreadyAppliedTotal,
            $cartQuote->getLsPointsSpent(),
            $this->basketHelper->getBasketSessionValue(),
            $cartQuote
        );

        if (!$this->giftCardHelper->isGiftCardAmountValid($orderBalance, $amount, $giftCardBalanceAmount)) {
            throw new CouldNotSaveException(
                __(
                    'The applied amount %3 is greater than the entry balance amount (%1)' .
                    ' or it is greater than order balance (%2).',
                    $this->priceHelper->currency($giftCardBalanceAmount, true, false),
                    $this->priceHelper->currency($orderBalance, true, false),
                    $this->priceHelper->currency($amount, true, false)
                )
            );
        }

        if (!$cartQuote->getItemsCount()) {
            return false;
        }

        $cartQuote->getShippingAddress()->setCollectShippingRates(true);
        $entries   = $this->giftCardHelper->decodeEntries($raw);
        $entries[] = [
            'entry_type'      => $entryType,
            'entry_no'        => $code,
            'pin_code'        => $pin,
            'amount'          => $amount,
            'currency_code'   => $giftCardCurrencyCode,
            'currency_factor' => $quotePointRate ?? 0,
            'tender_type'     => $this->voucherHelper->getTenderTypeByEntryType($entryType),
        ];
        $cartQuote->setLsPosDataEntries($this->giftCardHelper->encodeEntries($entries))
            ->setTotalsCollectedFlag(false)
            ->collectTotals();
        $this->quoteRepository->save($cartQuote);

        return true;
    }

    /**
     * Remove a specific applied entry by code within its entry-type category and recompute totals.
     *
     * @param int $cartId
     * @param string $entryType
     * @param string $code
     * @return bool
     * @throws NoSuchEntityException
     */
    public function removeEntry(int $cartId, string $entryType, string $code): bool
    {
        try {
            /** @var Quote $cartQuote */
            $cartQuote = $this->quoteRepository->get($cartId);
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(__('Could not find a cart with ID %1', $cartId));
        }

        $entries = $this->giftCardHelper->decodeEntries($cartQuote->getLsPosDataEntries());
        if (empty($entries)) {
            return true;
        }

        $removeIsGiftCard = strtoupper($entryType) === 'GIFTCARDNO';
        $remaining        = array_values(array_filter($entries, function ($entry) use ($code, $removeIsGiftCard) {
            $entryIsGiftCard = strtoupper((string) ($entry['entry_type'] ?? '')) === 'GIFTCARDNO';
            $matches         = (($entry['entry_no'] ?? '') === $code) && ($entryIsGiftCard === $removeIsGiftCard);
            return !$matches;
        }));

        $cartQuote->getShippingAddress()->setCollectShippingRates(true);
        $cartQuote->setLsPosDataEntries(
            empty($remaining) ? null : $this->giftCardHelper->encodeEntries($remaining)
        )
            ->setTotalsCollectedFlag(false)
            ->collectTotals();
        $this->quoteRepository->save($cartQuote);

        return true;
    }

    /**
     * Get all applied entries mapped to the GraphQL/frontend shape.
     *
     * Backend keys entry_no/pin_code are mapped to code/pin.
     *
     * @param int $cartId
     * @return array<int, array{entry_type: ?string, code: ?string, amount: ?float, pin: ?string}>
     * @throws NoSuchEntityException
     */
    public function getEntries(int $cartId): array
    {
        /** @var Quote $cartQuote */
        $cartQuote = $this->quoteRepository->getActive($cartId);
        $entries   = $this->giftCardHelper->decodeEntries($cartQuote->getLsPosDataEntries());

        $result = [];
        foreach ($entries as $entry) {
            $result[] = [
                'entry_type' => $entry['entry_type'] ?? null,
                'code'       => $entry['entry_no'] ?? null,
                'amount'     => isset($entry['amount']) ? (float) $entry['amount'] : null,
                'pin'        => $entry['pin_code'] ?? null,
            ];
        }

        return $result;
    }
}
