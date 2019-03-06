<?php

namespace Ls\Omni\Model;

use \Ls\Omni\Helper\BasketHelper;
use Magento\Checkout\Controller\Cart\CouponPost;

/**
 * Class SetCouponData
 * @package Ls\Omni\Model
 */
class SetCouponData
{
    /** @var BasketHelper  */
    public $basketHelper;

    /** @var  \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory */
    public $redirectFactory;

    /** @var \Magento\Framework\UrlInterface  */
    public $url;

    /**
     * SetCouponData constructor.
     * @param BasketHelper $basketHelper
     * @param \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory
     * @param \Magento\Framework\UrlInterface $url
     */
    public function __construct(
        BasketHelper $basketHelper,
        \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->basketHelper = $basketHelper;
        $this->redirectFactory = $redirectFactory;
        $this->url = $url;
    }

    public function aroundExecute(CouponPost $subject, callable $proceed)
    {
        // redirect to basket
        $redirect = $this->redirectFactory->create();
        return $redirect->setUrl($this->url->getUrl('checkout/cart/index'));
    }
}
