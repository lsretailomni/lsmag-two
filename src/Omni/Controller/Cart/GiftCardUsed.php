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
use \Ls\Omni\Model\GiftCard\GiftCardManagement;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
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
     * @param GiftCardManagement $giftCardManagement
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
        public LSR $lsr,
        public GiftCardManagement $giftCardManagement
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
        $cancelVoucherNo   = $this->getRequest()->getParam('cancelvoucherno');
        $cancelGiftCardNo  = $this->getRequest()->getParam('cancelgiftcardno');
        $cartQuote         = $this->cart->getQuote();
        $cartId            = (int)$cartQuote->getId();

        // Remove a specific voucher
        if (!empty($cancelVoucherNo)) {
            try {
                $this->giftCardManagement->removeEntry(
                    $cartId,
                    $this->resolveVoucherEntryType($cartQuote, $cancelVoucherNo),
                    $cancelVoucherNo
                );
                $this->messageManager->addSuccessMessage(__('Voucher has been removed.'));
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(__('Voucher cannot be removed.'));
            }
            return $this->_goBack();
        }

        // Remove a specific gift card
        if (!empty($cancelGiftCardNo)) {
            try {
                $this->giftCardManagement->removeEntry($cartId, 'GIFTCARDNO', $cancelGiftCardNo);
                $this->messageManager->addSuccessMessage(__('Gift card has been removed.'));
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(__('Gift card cannot be removed.'));
            }
            return $this->_goBack();
        }

        $giftCardNo     = $this->getRequest()->getParam('giftcardno');
        $giftCardPin    = $this->getRequest()->getParam('giftcardpin');
        $giftCardAmount = ((int)$this->getRequest()->getParam('removegiftcard') === 1)
            ? 0.0
            : (float)trim((string)($this->getRequest()->getParam('giftcardamount') ?? ''));

        // Cancel flow (clear all applied entries)
        if ($giftCardAmount == 0) {
            try {
                $quote = $this->_checkoutSession->getQuote();
                $quote->setLsPosDataEntries(null)
                    ->setTotalsCollectedFlag(false)
                    ->collectTotals();
                $this->quoteRepository->save($quote);
                $this->messageManager->addSuccessMessage(__('You have successfully cancelled the POS data entry.'));
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(__('POS data entry cannot be cancelled.'));
            }
            return $this->_goBack();
        }

        if (empty($giftCardNo)) {
            $this->messageManager->addErrorMessage(__('The gift card is not valid.'));
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
            $applied = $this->giftCardManagement->applyEntry(
                $cartId,
                null,
                $giftCardNo,
                $giftCardPin,
                $giftCardAmount
            );

            if ($applied) {
                $entries   = $this->giftCardManagement->getEntries($cartId);
                $lastEntry = end($entries);
                $entryType = $lastEntry ? (string)($lastEntry['entry_type'] ?? '') : '';
                $this->messageManager->addSuccessMessage(__(
                    'You have used "%1" from %2',
                    $this->priceHelper->currency($giftCardAmount, true, false),
                    $entryType
                ));
            } else {
                $this->messageManager->addErrorMessage(__('POS data entry cannot be applied.'));
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__('POS data entry cannot be applied.'));
        }

        return $this->_goBack();
    }

    /**
     * Resolve the stored entry type of an applied voucher by its code.
     *
     * Returns the matching non-gift-card entry's entry_type so removeEntry targets the correct
     * voucher-category entry. Falls back to an empty string (still non-GIFTCARDNO) when absent.
     *
     * @param Quote $cartQuote
     * @param string $code
     * @return string
     */
    private function resolveVoucherEntryType(Quote $cartQuote, string $code): string
    {
        foreach ($this->giftCardHelper->getVoucherEntries($cartQuote->getLsPosDataEntries()) as $entry) {
            if (($entry['entry_no'] ?? '') === $code) {
                return (string)($entry['entry_type'] ?? '');
            }
        }
        return '';
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
