<?php

namespace Ls\Omni\Block\Cart;

use \Ls\Omni\Helper\GiftCardHelper;
use Magento\Checkout\Block\Cart\AbstractCart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context;

/**
 * Get gift card information for cart
 */
class Giftcard extends AbstractCart
{

    /**
     * @var GiftCardHelper
     */
    public $giftCardHelper;

    /**
     * Giftcard constructor.
     * @param GiftCardHelper $giftCardHelper
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param array $data
     */
    public function __construct(
        GiftCardHelper $giftCardHelper,
        Context $context,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $data);
        $this->giftCardHelper = $giftCardHelper;
    }

    /**
     * Get gift card balance
     *
     * @return float|null
     */
    public function getGiftCardBalance()
    {
        return $this->giftCardHelper->getGiftCardBalance();
    }

    /**
     * Get gift card amount used
     *
     * @return mixed
     */
    public function getGiftCardAmountUsed()
    {
        if ($this->getQuote()->getLsGiftCardAmountUsed() > 0) {
            return $this->getQuote()->getLsGiftCardAmountUsed();
        }
    }

    /**
     * Get gift card number
     *
     * @return mixed
     */
    public function getGiftCardNo()
    {
        return $this->getQuote()->getLsGiftCardNo();
    }

    /**
     * Get gift card pin
     *
     * @return mixed
     */
    public function getGiftCardPin()
    {
        return $this->getQuote()->getLsGiftCardPin();
    }

    /**
     * Get gift card is enable on cart page
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getGiftCardActive()
    {
        return $this->giftCardHelper->isGiftCardEnabled('cart');
    }

    /**
     * Get is pin code field enable
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function isPinCodeFieldEnable()
    {
        return $this->giftCardHelper->isPinCodeFieldEnable();
    }
}
