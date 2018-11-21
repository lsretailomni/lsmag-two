<?php

namespace Ls\Omni\Controller\Ajax;

use Magento\Framework\App\Action\Context;
use Ls\Omni\Helper\LoyaltyHelper;
use Magento\Checkout\Model\Session;
use Ls\Core\Model\LSR;

class Points extends \Magento\Framework\App\Action\Action
{

    /** @var \Magento\Framework\Controller\Result\JsonFactory  */
    protected $resultJsonFactory;

    /** @var \Magento\Framework\Controller\Result\RawFactory  */
    protected $resultRawFactory;

    /** @var LoyaltyHelper  */
    private $loyaltyHelper;

    /** @var Session  */
    protected $_checkoutSession;

    /** @var \Magento\Customer\Model\Session  */
    protected $customerSession;

    /**
     * Points constructor.
     * @param Context $context
     * @param \Magento\Framework\Json\Helper\Data $helper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param LoyaltyHelper $loyaltyHelper
     * @param Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Json\Helper\Data $helper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Customer\Model\Session $customerSession,
        LoyaltyHelper $loyaltyHelper,
        Session $checkoutSession
    )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->loyaltyHelper = $loyaltyHelper;
        $this->_checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        if (!$this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID)) {
            // for now lets return empty response
            return $resultJson->setData('');
        }
        // for now its just returning value into the base currency which is expected to be the same as the NAV currency.
        $base_currency = $this->_checkoutSession->getQuote()->getBaseCurrencyCode();
        return $resultJson->setData($base_currency . ' ' . $this->loyaltyHelper->convertPointsIntoValues());
    }
}
