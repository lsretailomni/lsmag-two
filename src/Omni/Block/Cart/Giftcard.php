<?php
declare(strict_types=1);

namespace Ls\Omni\Block\Cart;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Omni\Helper\GiftCardHelper;
use Magento\Checkout\Block\Cart\AbstractCart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context;

class Giftcard extends AbstractCart
{
    /**
     * @param GiftCardHelper $giftCardHelper
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param array $data
     */
    public function __construct(
        public GiftCardHelper $giftCardHelper,
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
     * Get gift card number
     *
     * @return string
     */
    public function getGiftCardNo()
    {
        return $this->getQuote()->getLsPosDataEntries();
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
}
