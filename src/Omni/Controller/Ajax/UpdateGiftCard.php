<?php
declare(strict_types=1);

namespace Ls\Omni\Controller\Ajax;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\GiftCardHelper;
use \Ls\Omni\Helper\VoucherHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;

/**
 * Controller for adding/updating/removing gift card or voucher
 */
class UpdateGiftCard implements HttpPostActionInterface
{
    /**
     * @param JsonFactory $resultJsonFactory
     * @param RawFactory $resultRawFactory
     * @param GiftCardHelper $giftCardHelper
     * @param VoucherHelper $voucherHelper
     * @param BasketHelper $basketHelper
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $cartRepository
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param Data $data
     * @param RequestInterface $request
     */
    public function __construct(
        public JsonFactory $resultJsonFactory,
        public RawFactory $resultRawFactory,
        public GiftCardHelper $giftCardHelper,
        public VoucherHelper $voucherHelper,
        public BasketHelper $basketHelper,
        public CheckoutSession $checkoutSession,
        public CartRepositoryInterface $cartRepository,
        public \Magento\Framework\Pricing\Helper\Data $priceHelper,
        public Data $data,
        public RequestInterface $request
    ) {
    }

    /**
     * Add and remove gift card or voucher from checkout page
     *
     * @return Json|Raw
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception|GuzzleException
     */
    public function execute()
    {
        $httpBadRequestCode = 400;
        $resultRaw          = $this->resultRawFactory->create();
        $isPost             = $this->request->isPost();

        if (!$isPost || !$this->request->isXmlHttpRequest()) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        $resultJson         = $this->resultJsonFactory->create();
        $post               = $this->request->getContent();
        $postData           = json_decode($post);
        $giftCardNo         = $postData->gift_card_no;
        $giftCardPin        = $postData->gift_card_pin;
        $giftCardAmount     = $postData->gift_card_amount;
        $cancelVoucherNo    = $postData->cancel_voucher_no ?? null;
        $cancelGiftCardNo   = $postData->cancel_gift_card_no ?? null;
        $cancelAllGiftCards = !empty($postData->cancel_all_gift_cards);
        $cartId             = $this->checkoutSession->getQuoteId();
        $quote              = $this->cartRepository->get($cartId);

        // ── Remove all entries
        if ($cancelAllGiftCards) {
            try {
                $entries   = $this->giftCardHelper->decodeEntries($quote->getLsPosDataEntries());
                $remaining = array_values(array_filter(
                    $entries,
                    fn($e) => strtoupper($e['entry_type'] ?? '') !== 'GIFTCARDNO'
                ));
                $quote->setLsPosDataEntries(empty($remaining) ? null : $this->giftCardHelper->encodeEntries($remaining));
                $this->validateQuote($quote);
                $quote->collectTotals();
                $this->cartRepository->save($quote);
                return $resultJson->setData(['success'    => 'true',
                                             'message'    => __('All gift cards have been removed.'),
                                             'is_voucher' => false
                ]);
            } catch (Exception $e) {
                return $resultJson->setData(['error' => 'true', 'message' => $e->getMessage()]);
            }
        }

        // ── Remove a specific entry (voucher or gift card) from the unified list ──
        $cancelNo = $cancelVoucherNo ?: $cancelGiftCardNo;
        if (!empty($cancelNo)) {
            try {
                $entries          = $this->giftCardHelper->decodeEntries($quote->getLsPosDataEntries());
                $entries          = array_values(array_filter($entries, fn($e) => $e['entry_no'] !== $cancelNo));
                $isVoucherRemoval = !empty($cancelVoucherNo);
                $quote->setLsPosDataEntries(empty($entries) ? null : $this->giftCardHelper->encodeEntries($entries));
                $this->validateQuote($quote);
                $quote->collectTotals();
                $this->cartRepository->save($quote);
                $msg = $isVoucherRemoval ? __('Voucher/Gift Card has been removed.') : __('Gift card has been removed.');
                return $resultJson->setData(['success'    => 'true',
                                             'message'    => $msg,
                                             'is_voucher' => $isVoucherRemoval
                ]);
            } catch (Exception $e) {
                return $resultJson->setData(['error' => 'true', 'message' => $e->getMessage()]);
            }
        }

        // ── Cancel all (amount == 0 or code empty) ──
        if (empty($giftCardNo) || $giftCardAmount == 0) {
            try {
                $quote->setLsPosDataEntries(null);
                $this->validateQuote($quote);
                $quote->collectTotals();
                $this->cartRepository->save($quote);
                return $resultJson->setData([
                    'success'    => 'true',
                    'message'    => __('You have successfully cancelled the entry.'),
                    'is_voucher' => false
                ]);
            } catch (Exception $e) {
                return $resultJson->setData(['error' => 'true', 'message' => $e->getMessage()]);
            }
        }

        // ── Resolve entry type via LS Central balance API ──
        $resolved = $this->voucherHelper->resolveCode($giftCardNo, $giftCardPin);

        if ($resolved === null) {
            return $resultJson->setData([
                'error'   => 'true',
                'message' => __('The gift card / voucher is not valid.')
            ]);
        }

        $giftCardResponse = $resolved['response'];
        $entryType        = $resolved['entry_type'];

        // Prevent applying the same code twice
        $existingEntries = $this->giftCardHelper->decodeEntries($quote->getLsPosDataEntries());
        foreach ($existingEntries as $existing) {
            if (($existing['entry_no'] ?? '') === $giftCardNo) {
                return $resultJson->setData([
                    'error'   => 'true',
                    'message' => __('This GiftCard/Voucher is already applied to your order.')
                ]);
            }
        }

        if (is_object($giftCardResponse)) {
            $convertedGiftCardBalanceArr = $this->giftCardHelper->getConvertedGiftCardBalance($giftCardResponse);
            $giftCardBalanceAmount       = $convertedGiftCardBalanceArr['gift_card_balance_amount'];
            $quotePointRate              = $convertedGiftCardBalanceArr['quote_point_rate'] ?? null;
            $giftCardCurrencyCode        = $convertedGiftCardBalanceArr['gift_card_currency'] ?? null;
        } else {
            $giftCardBalanceAmount = $giftCardResponse;
            $quotePointRate        = null;
            $giftCardCurrencyCode  = null;
        }

        if ($this->giftCardHelper->isGiftCardExpired($giftCardResponse) && $giftCardAmount) {
            return $resultJson->setData([
                'error'   => 'true',
                'message' => __('Unfortunately, we can\'t apply this since it has already expired.')
            ]);
        }

        // Balance check: total of all already-applied entries
        $alreadyAppliedTotal = $this->giftCardHelper->getTotalFromEntries($quote->getLsPosDataEntries());
        $orderBalance        = $this->data->getOrderBalance(
            $alreadyAppliedTotal,
            $quote->getLsPointsSpent(),
            $this->basketHelper->getBasketSessionValue()
        );

        $isGiftCardAmountValid = $this->giftCardHelper->isGiftCardAmountValid(
            $orderBalance,
            (float)$giftCardAmount,
            (float)$giftCardBalanceAmount
        );

        if (!is_numeric($giftCardAmount) || $giftCardAmount < 0 || !$isGiftCardAmountValid) {
            return $resultJson->setData([
                'error'   => 'true',
                'message' => __(
                    'The applied amount %3 is greater than the balance amount (%1) or it is greater than order balance (%2).',
                    $this->priceHelper->currency($giftCardBalanceAmount, true, false),
                    $this->priceHelper->currency($orderBalance, true, false),
                    $this->priceHelper->currency($giftCardAmount, true, false)
                )
            ]);
        }

        try {
            // Append new entry to unified JSON list
            $tenderType        = $this->voucherHelper->getTenderTypeByEntryType($entryType);
            $newEntry          = [
                'entry_type'      => $entryType,
                'entry_no'        => $giftCardNo,
                'pin_code'        => $giftCardPin,
                'amount'          => (float)$giftCardAmount,
                'tender_type'     => $tenderType,
                'currency_code'   => $giftCardCurrencyCode ?? null,
                'currency_factor' => $quotePointRate ?? 0,
            ];
            $existingEntries[] = $newEntry;
            $quote->setLsPosDataEntries($this->giftCardHelper->encodeEntries($existingEntries));
            $this->validateQuote($quote);
            $quote->collectTotals();
            $this->cartRepository->save($quote);
            $response = ['success' => 'true'];
        } catch (Exception $e) {
            $response = ['error' => 'true', 'message' => $e->getMessage()];
        }

        return $resultJson->setData($response);
    }

    /**
     * Validate Quote
     *
     * @param Quote $quote
     * @return void
     * @throws LocalizedException
     */
    protected function validateQuote(Quote $quote)
    {
        if ($quote->getItemsCount() === 0) {
            throw new LocalizedException(
                __('Totals calculation is not applicable to empty cart.')
            );
        }
    }
}
