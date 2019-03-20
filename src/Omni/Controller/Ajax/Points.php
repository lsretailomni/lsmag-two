<?php

namespace Ls\Omni\Controller\Ajax;

use Magento\Framework\App\Action\Context;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Core\Model\LSR;

/**
 * Class Points
 * @package Ls\Omni\Controller\Ajax
 */
class Points extends \Magento\Framework\App\Action\Action
{

    /** @var \Magento\Framework\Controller\Result\JsonFactory  */
    public $resultJsonFactory;

    /** @var \Magento\Framework\Controller\Result\RawFactory  */
    public $resultRawFactory;

    /** @var LoyaltyHelper  */
    private $loyaltyHelper;

    /**
     * @var \Magento\Checkout\Model\Session\Proxy
     */
    public $checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session\Proxy
     */
    public $customerSession;

    /**
     * Points constructor.
     * @param Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param LoyaltyHelper $loyaltyHelper
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        LoyaltyHelper $loyaltyHelper,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->loyaltyHelper = $loyaltyHelper;
        $this->checkoutSession = $checkoutSession;
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
        $base_currency = $this->checkoutSession->getQuote()->getBaseCurrencyCode();
        return $resultJson->setData($base_currency . ' ' . $this->loyaltyHelper->convertPointsIntoValues());
    }
}
