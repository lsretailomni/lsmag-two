<?php
declare(strict_types=1);

namespace Ls\Omni\Controller\Ajax;

use Ls\Omni\Block\Cart\HyvaCoupons as CouponsBlock;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * Hyvä variant of the Coupons AJAX controller.
 *
 * Renders cart-context coupon recommendations using the Hyvä Alpine template
 * so the Luma controller and template remain unchanged.
 * Reached at omni/ajax/HyvaCoupons via GET (matching the fetch() call in coupons.phtml).
 */
class HyvaCoupons implements HttpGetActionInterface
{
    /**
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param RequestInterface $request
     */
    public function __construct(
        public PageFactory $resultPageFactory,
        public JsonFactory $resultJsonFactory,
        public RedirectFactory $resultRedirectFactory,
        public RequestInterface $request
    ) {
    }

    /**
     * Return rendered Hyvä coupon recommendations HTML as JSON.
     *
     * @return Json|ResultInterface
     */
    public function execute(): Json|ResultInterface
    {
        if (!$this->request->isXmlHttpRequest()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('checkout/cart');
            return $resultRedirect;
        }

        $result     = $this->resultJsonFactory->create();
        $resultPage = $this->resultPageFactory->create();
        $block      = $resultPage->getLayout()
            ->createBlock(CouponsBlock::class)
            ->setTemplate('Ls_Omni::cart/hyva/coupons-listing.phtml')
            ->toHtml();
        $result->setData(['output' => $block]);

        return $result;
    }
}
