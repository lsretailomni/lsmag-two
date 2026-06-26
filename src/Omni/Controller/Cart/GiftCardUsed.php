<?php
declare(strict_types=1);

namespace Ls\Omni\Controller\Cart;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\GiftCardHelper;
use \Ls\Omni\Helper\VoucherHelper;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class GiftCardUsed extends \Magento\Checkout\Controller\Cart
{
    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param CheckoutSession $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param Cart $cart
     * @param CartRepositoryInterface $quoteRepository
     * @param GiftCardHelper $giftCardHelper
     * @param VoucherHelper $voucherHelper
     * @param BasketHelper $basketHelper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param Data $data
     * @param LSR $lsr
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        CheckoutSession $checkoutSession,
        StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        Cart $cart,
        public CartRepositoryInterface $quoteRepository,
        public GiftCardHelper $giftCardHelper,
        public VoucherHelper $voucherHelper,
        public BasketHelper $basketHelper,
        public \Magento\Framework\Pricing\Helper\Data $priceHelper,
        public Data $data,
        public LSR $lsr
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
    }

    /**
     * Add and remove gift card or voucher from cart page.
     * Resolves the correct LS Central entry type by trying each configured code in admin config.
     *
     * @return Redirect
     * @throws GuzzleException
     */
    public function execute()
    {
        $giftCardNo     = $this->getRequest()->getParam('giftcardno');
        $giftCardPin    = $this->getRequest()->getParam('giftcardpin');
        $giftCardAmount = $this->getRequest()->getParam('removegiftcard') == 1
            ? 0 : trim($this->getRequest()->getParam('giftcardamount'));
        $giftCardAmount    = (float)$giftCardAmount;
        $cancelVoucherNo   = $this->getRequest()->getParam('cancelvoucherno');
        $cancelGiftCardNo  = $this->getRequest()->getParam('cancelgiftcardno');
        $cartQuote         = $this->cart->getQuote();

        // Remove a specific voucher
        if (!empty($cancelVoucherNo)) {
            try {
                $vouchers = $this->voucherHelper->decodeVouchers($cartQuote->getLsPosDataEntries());
                $vouchers = array_values(array_filter($vouchers, fn($v) => $v['entry_no'] !== $cancelVoucherNo));
                $cartQuote->setLsPosDataEntries(empty($vouchers) ? null : $this->voucherHelper->encodeVouchers($vouchers))
                    ->collectTotals();
                if (empty($vouchers)) {
                    $cartQuote->setLsPosDataEntries(null);
                }
                $this->quoteRepository->save($cartQuote);
                $this->messageManager->addSuccessMessage(__('Voucher has been removed.'));
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(__('Voucher cannot be removed.'));
            }
            return $this->_goBack();
        }

        // Remove a specific gift card
        if (!empty($cancelGiftCardNo)) {
            try {
                $cards    = $this->giftCardHelper->decodeEntries($cartQuote->getLsPosDataEntries());
                $cards    = array_values(array_filter($cards, fn($c) => $c['entry_no'] !== $cancelGiftCardNo));
                $cartQuote->setLsPosDataEntries(empty($cards) ? null : $this->giftCardHelper->encodeEntries($cards))
                    ->collectTotals();
                $this->quoteRepository->save($cartQuote);
                $this->messageManager->addSuccessMessage(__('Gift card has been removed.'));
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(__('Gift card cannot be removed.'));
            }
            return $this->_goBack();
        }

        // Cancel flow
        if ($giftCardAmount == 0) {
            try {
                $this->_checkoutSession->getQuote()
                    ->setLsPosDataEntries(null)->save();
                $this->messageManager->addSuccessMessage(__('You have successfully cancelled the POS data entry.'));
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(__('POS data entry cannot be cancelled.'));
            }
            return $this->_goBack();
        }

        if (!is_numeric($giftCardAmount) || $giftCardAmount < 0) {
            $this->messageManager->addErrorMessage(
                __('The Gift Card / Voucher Amount "%1" is not valid.',
                    $this->priceHelper->currency($giftCardAmount, true, false))
            );
            return $this->_goBack();
        }

        try {
            // Try each configured entry type (admin config code field) against LS Central balance API
            $resolved = $this->voucherHelper->resolveCode($giftCardNo, $giftCardPin);

            if ($resolved === null) {
                $this->messageManager->addErrorMessage(__('The gift card / voucher is not valid.'));
                return $this->_goBack();
            }

            $giftCardResponse = $resolved['response'];
            $entryType        = $resolved['entry_type'];

            // Prevent applying the exact same code twice
            $existingEntries = json_decode((string)$cartQuote->getLsPosDataEntries(), true) ?? [];
            foreach ($existingEntries as $e) {
                if (($e['entry_no'] ?? '') === $giftCardNo) {
                    $this->messageManager->addErrorMessage(__('This entry is already applied to your order.'));
                    return $this->_goBack();
                }
            }

            if ($this->giftCardHelper->isGiftCardExpired($giftCardResponse) && $giftCardAmount) {
                $this->messageManager->addErrorMessage(
                    __('Unfortunately, we can\'t apply this POS data entry since it has already expired.')
                );
                return $this->_goBack();
            }

            $giftCardBalanceAmount = 0;
            $quotePointRate = $giftCardCurrencyCode = null;
            if (is_object($giftCardResponse)) {
                $converted            = $this->giftCardHelper->getConvertedGiftCardBalance($giftCardResponse);
                $giftCardBalanceAmount = $converted['gift_card_balance_amount'];
                $quotePointRate       = $converted['quote_point_rate'];
                $giftCardCurrencyCode = $converted['gift_card_currency'];
            } else {
                $giftCardBalanceAmount = (float)$giftCardResponse;
            }

            // Use total of ALL existing entries for order balance calculation
            $alreadyAppliedTotal = $this->giftCardHelper->getTotalFromEntries($cartQuote->getLsPosDataEntries());
            $orderBalance = $this->data->getOrderBalance(
                $alreadyAppliedTotal,
                $cartQuote->getLsPointsSpent(),
                $this->basketHelper->getBasketSessionValue()
            );

            $isGiftCardAmountValid = $this->giftCardHelper->isGiftCardAmountValid(
                $orderBalance, $giftCardAmount, $giftCardBalanceAmount
            );

            if ($isGiftCardAmountValid === false) {
                $this->messageManager->addErrorMessage(__(
                    'The applied amount %3 is greater than the entry balance amount (%1) or it is greater than order balance (%2).',
                    $this->priceHelper->currency($giftCardBalanceAmount, true, false),
                    $this->priceHelper->currency($orderBalance, true, false),
                    $this->priceHelper->currency($giftCardAmount, true, false)
                ));
                return $this->_goBack();
            }

            if ($cartQuote->getItemsCount() && $isGiftCardAmountValid) {
                $cartQuote->getShippingAddress()->setCollectShippingRates(true);
                // All entry types stored uniformly — no voucher/gift card branching needed
                $entries   = $this->giftCardHelper->decodeEntries($cartQuote->getLsPosDataEntries());
                $entries[] = [
                    'entry_type'      => $entryType,
                    'entry_no'        => $giftCardNo,
                    'pin_code'        => $giftCardPin,
                    'amount'          => (float)$giftCardAmount,
                    'currency_code'   => $giftCardCurrencyCode,
                    'currency_factor' => $quotePointRate ?? 0,
                    'tender_type'     => $this->voucherHelper->getTenderTypeByEntryType($entryType),
                ];
                $encoded = $this->giftCardHelper->encodeEntries($entries);
                $cartQuote->setLsPosDataEntries($encoded)->collectTotals();
                $this->_checkoutSession->getQuote()->setLsPosDataEntries($encoded)->save();
                $this->quoteRepository->save($cartQuote);
                $this->messageManager->addSuccessMessage(__(
                    'You have used "%1" from %2',
                    $this->priceHelper->currency($giftCardAmount, true, false),
                    $entryType
                ));
            } else {
                $this->messageManager->addErrorMessage(__('POS data entry cannot be applied.'));
            }
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__('POS data entry cannot be applied.'));
        }

        return $this->_goBack();
    }

    /**
     * Get base currency code
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getBaseCurrencyCode()
    {
        return $this->_checkoutSession->getQuote()->getBaseCurrencyCode();
    }
}
