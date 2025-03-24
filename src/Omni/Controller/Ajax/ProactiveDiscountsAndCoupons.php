<?php

namespace Ls\Omni\Controller\Ajax;

use \Ls\Omni\Block\Product\View\Discount\Proactive;
use \Ls\Omni\Helper\SessionHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RedirectFactory;

/**
 * Class ProactiveDiscountsAndCoupons
 * @package Ls\Omni\Controller\Ajax
 */
class ProactiveDiscountsAndCoupons implements HttpGetActionInterface
{

    /**
     * @var PageFactory
     */
    public $resultPageFactory;

    /**
     * @var JsonFactory
     */
    public $resultJsonFactory;

    /**
     * @var RedirectFactory
     */
    public $resultRedirectFactory;

    /**
     * @var SessionHelper
     */
    public $sessionHelper;

    /**
     * @var RequestInterface
     */
    public RequestInterface $request;

    /**
     * @var CustomerSession
     */
    public $customerSession;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param SessionHelper $sessionHelper
     * @param RequestInterface $request
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        RedirectFactory $resultRedirectFactory,
        SessionHelper $sessionHelper,
        CustomerSession $customerSession,
        RequestInterface $request
    ) {
        $this->resultPageFactory     = $resultPageFactory;
        $this->resultJsonFactory     = $resultJsonFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->sessionHelper         = $sessionHelper;
        $this->customerSession       = $customerSession;
        $this->request               = $request;
    }

    /**
     * Entry point for this controller
     *
     * @return ResponseInterface|Json|ResultInterface
     * @throws FileSystemException
     */
    public function execute()
    {
        if (!$this->request->isXmlHttpRequest()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('checkout/cart');

            return $resultRedirect;
        }
        $this->sessionHelper->newSessionHandler("lsproactivediscounts");
        $result            = $this->resultJsonFactory->create();
        $resultPage        = $this->resultPageFactory->create();
        $currentProductSku = $this->request->getParam('currentProduct');
        $data              = ['productSku' => $currentProductSku, 'loggedIn' => false];
        if($this->customerSession->isLoggedIn()) {
            //For logged in users display offer from PublishedOffersGetByCardId
            //to filter and display offers based on Member Attribute and Member
            // Attribute Value configuration in Discount offer setup in central.
            $data['loggedIn'] = true;
            $blockCoupons      = $resultPage->getLayout()
                ->createBlock(Proactive::class)
                ->setTemplate('Ls_Omni::product/view/coupons.phtml')
                ->setData('data', $data)
                ->toHtml();
            $data              = array_merge($data, ['coupons' => $blockCoupons]);
        }
        //For guest users users display offer from DiscountGet
        $block             = $resultPage->getLayout()
            ->createBlock(Proactive::class)
            ->setTemplate('Ls_Omni::product/view/proactive.phtml')
            ->setData('data', $data)
            ->toHtml();
        $result->setData(['output' => $block]);



        return $result;
    }
}
