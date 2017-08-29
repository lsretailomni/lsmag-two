<?php

namespace Ls\Omni\Model;

use Ls\Omni\Helper\BasketHelper;
use \Magento\Checkout\Controller\Cart\CouponPost;

class SetCouponData
{
    protected $basketHelper;
    /** @var  \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory */
    protected $redirectFactory;
    protected $url;

    public function __construct(
        BasketHelper $basketHelper,
        \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory,
        \Magento\Framework\UrlInterface $url)
    {
        $this->basketHelper = $basketHelper;
        $this->redirectFactory = $redirectFactory;
        $this->url = $url;
    }

    public function aroundExecute(CouponPost $subject, callable $proceed) {
        // set coupon code in the quote directly, to circumvent Magento validation
        // TODO: validation?
        $couponCode = $subject->getRequest()->getParam('remove') == 1
            ? ''
            : trim($subject->getRequest()->getParam('coupon_code'));
        $this->basketHelper->setCouponCode($couponCode);

        // redirect to basket
        $redirect = $this->redirectFactory->create();
        return $redirect->setUrl($this->url->getUrl('checkout/cart/index'));
    }
}