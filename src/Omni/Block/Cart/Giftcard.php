<?php

namespace Ls\Omni\Block\Cart;

use \Ls\Omni\Helper\GiftCardHelper;
use Magento\Checkout\Block\Cart\AbstractCart;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Giftcard
 * @package Ls\Omni\Block\Cart
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
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param Proxy $checkoutSession
     * @param array $data
     */
    public function __construct(
        GiftCardHelper $giftCardHelper,
        Context $context,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        Proxy $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $data);
        $this->giftCardHelper  = $giftCardHelper;
        $this->_isScopePrivate = true;
    }

    /**
     * @return float|null
     */
    public function getGiftCardBalance()
    {
        return $this->giftCardHelper->getGiftCardBalance();
    }

    /**
     * @return mixed
     */
    public function getGiftCardAmountUsed()
    {
        if ($this->getQuote()->getLsGiftCardAmountUsed() > 0) {
            return $this->getQuote()->getLsGiftCardAmountUsed();
        }
    }

    /**
     * @return mixed
     */
    public function getGiftCardNo()
    {
        return $this->getQuote()->getLsGiftCardNo();
    }

    /**
     * @return string
     */
    public function getGiftCardActive()
    {
        return $this->giftCardHelper->isGiftCardEnableOnCartPage();
    }
}
