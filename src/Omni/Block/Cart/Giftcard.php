<?php
declare(strict_types=1);

namespace Ls\Omni\Block\Cart;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Omni\Helper\GiftCardHelper;
use \Ls\Omni\Helper\VoucherHelper;
use Magento\Checkout\Block\Cart\AbstractCart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context;

class Giftcard extends AbstractCart
{
    /**
     * @param GiftCardHelper $giftCardHelper
     * @param VoucherHelper $voucherHelper
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param array $data
     */
    public function __construct(
        public GiftCardHelper $giftCardHelper,
        public VoucherHelper $voucherHelper,
        Context $context,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $data);
    }

    /**
     * Get gift card amount used
     *
     * @return string
     */
    public function getGiftCardAmountUsed()
    {
        $total = (float)array_sum(array_column(json_decode((string)$this->getQuote()->getLsPosDataEntries(), true) ?? [], 'amount'));
        return $total > 0 ? $total : "";
    }

    /**
     * Get gift card pin
     *
     * @return string
     */
    public function getGiftCardPin()
    {
        $entries = json_decode((string)$this->getQuote()->getLsPosDataEntries(), true) ?? [];
        return $entries ? (end($entries)['pin_code'] ?? null) : null;
    }

    /**
     * Get gift card is enable on cart page
     *
     * @return bool
     * @throws NoSuchEntityException|GuzzleException
     */
    public function getGiftCardActive()
    {
        return $this->giftCardHelper->isGiftCardEnabled('cart');
    }

    /**
     * Get is pin code field enable
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isPinCodeFieldEnable()
    {
        return $this->giftCardHelper->isPinCodeFieldEnable();
    }

    /**
     * Whether the unified apply component should be shown on the cart page.
     *
     * The single component serves both gift cards and vouchers, so it renders when
     * either surface is enabled for the cart area.
     *
     * @return bool
     * @throws NoSuchEntityException|GuzzleException
     */
    public function isEnabled(): bool
    {
        return $this->giftCardHelper->isGiftCardEnabled('cart')
            || $this->voucherHelper->isVoucherEnabled('cart');
    }

    /**
     * Get all applied POS data entries (gift cards + vouchers) from the unified column.
     *
     * Each entry carries at least {entry_type, entry_no, pin_code, amount}. The template
     * branches on entry_type to render the correct per-entry remove action.
     *
     * @return array
     */
    public function getAppliedEntries(): array
    {
        return $this->giftCardHelper->decodeEntries((string) $this->getQuote()->getLsPosDataEntries());
    }

    /**
     * Whether the given entry is a gift card (GIFTCARDNO) rather than a voucher.
     *
     * @param array $entry
     * @return bool
     */
    public function isGiftCardEntry(array $entry): bool
    {
        return strtoupper((string) ($entry['entry_type'] ?? '')) === 'GIFTCARDNO';
    }

    /**
     * Format an applied entry amount as a localized price string with currency symbol.
     *
     * @param mixed $amount
     * @return string
     */
    public function getFormattedEntryAmount($amount): string
    {
        return (string) $this->giftCardHelper->formatValue((float) $amount, true);
    }
}
