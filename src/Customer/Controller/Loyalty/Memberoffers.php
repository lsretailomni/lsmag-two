<?php

namespace Ls\Customer\Controller\Loyalty;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Memberoffers
 * @package Ls\Customer\Controller\Loyalty
 */
class Memberoffers extends AbstractAccount
{
    /** @var PageFactory */
    public $resultPageFactory;

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * Memberoffers constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param LoyaltyHelper $loyaltyHelper
     * @param LSR $lsr
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        LoyaltyHelper $loyaltyHelper,
        LSR $lsr
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->loyaltyHelper     = $loyaltyHelper;
        $this->lsr = $lsr;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            if ($this->loyaltyHelper->isEnabledLoyaltyElements() && $this->loyaltyHelper->isEnabledShowMemberOffers()) {
                /** Page $resultPage */
                $resultPage = $this->resultPageFactory->create();
                $resultPage->getConfig()->getTitle()->set(
                    __('Member Offers')
                );
                return $resultPage;
            }
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('customer/account/');
        return $resultRedirect;
    }
}
