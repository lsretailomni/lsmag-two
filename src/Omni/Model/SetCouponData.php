<?php

namespace Ls\Omni\Model;

use \Ls\Omni\Helper\BasketHelper;
use Magento\Checkout\Controller\Cart\CouponPost;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;

class SetCouponData
{
    /** @var BasketHelper */
    public $basketHelper;

    /** @var  RedirectFactory $redirectFactory */
    public $redirectFactory;

    /** @var UrlInterface */
    public $url;

    /**
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

    /**
     * Around execute
     *
     * @param CouponPost $subject
     * @param callable $proceed
     * @return Redirect
     * @throws NoSuchEntityException
     */
    public function aroundExecute(CouponPost $subject, callable $proceed)
    {
        if (!$this->basketHelper->lsr->isEnabled()) {
            return $proceed();
        }
        // redirect to basket
        $redirect = $this->redirectFactory->create();
        return $redirect->setUrl($this->url->getUrl('checkout/cart/index'));
    }
}
