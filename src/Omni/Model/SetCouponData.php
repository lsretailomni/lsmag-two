<?php

namespace Ls\Omni\Model;

use \Ls\Omni\Helper\BasketHelper;
use Magento\Checkout\Controller\Cart\CouponPost;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\UrlInterface;

/**
 * Class SetCouponData
 * @package Ls\Omni\Model
 */
class SetCouponData
{
    /** @var BasketHelper */
    public $basketHelper;

    /** @var  RedirectFactory $redirectFactory */
    public $redirectFactory;

    /** @var UrlInterface */
    public $url;

    /**
     * SetCouponData constructor.
     * @param BasketHelper $basketHelper
     * @param RedirectFactory $redirectFactory
     * @param UrlInterface $url
     */
    public function __construct(
        BasketHelper $basketHelper,
        RedirectFactory $redirectFactory,
        UrlInterface $url
    ) {
        $this->basketHelper    = $basketHelper;
        $this->redirectFactory = $redirectFactory;
        $this->url             = $url;
    }

    public function aroundExecute(CouponPost $subject, callable $proceed)
    {
        // redirect to basket
        $redirect = $this->redirectFactory->create();
        return $redirect->setUrl($this->url->getUrl('checkout/cart/index'));
    }
}
