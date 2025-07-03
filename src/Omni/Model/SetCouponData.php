<?php
declare(strict_types=1);

namespace Ls\Omni\Model;

use \Ls\Omni\Helper\BasketHelper;
use Magento\Checkout\Controller\Cart\CouponPost;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;

class SetCouponData
{

    /**
     * @param BasketHelper $basketHelper
     * @param RedirectFactory $redirectFactory
     * @param UrlInterface $url
     */
    public function __construct(
        public BasketHelper $basketHelper,
        public RedirectFactory $redirectFactory,
        public UrlInterface $url
    ) {
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
        $lsr = $this->basketHelper->getLsrModel();

        if (!$lsr->isLSR($lsr->getCurrentStoreId(),
            false,
            $lsr->getBasketIntegrationOnFrontend()
        )) {
            return $proceed();
        }
        // redirect to basket
        $redirect = $this->redirectFactory->create();
        return $redirect->setUrl($this->url->getUrl('checkout/cart/index'));
    }
}
