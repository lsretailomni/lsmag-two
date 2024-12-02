<?php

namespace Ls\Omni\Controller\Ajax;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class Points
 * @package Ls\Omni\Controller\Ajax
 */
class Points implements HttpGetActionInterface
{

    /** @var JsonFactory */
    public $resultJsonFactory;

    /** @var RawFactory */
    public $resultRawFactory;

    /** @var LoyaltyHelper */
    private $loyaltyHelper;

    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    /**
     * @var CustomerSession
     */
    public $customerSession;

    /**
     * Points constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param RawFactory $resultRawFactory
     * @param CustomerSession $customerSession
     * @param LoyaltyHelper $loyaltyHelper
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        RawFactory $resultRawFactory,
        CustomerSession $customerSession,
        LoyaltyHelper $loyaltyHelper,
        CheckoutSession $checkoutSession
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory  = $resultRawFactory;
        $this->loyaltyHelper     = $loyaltyHelper;
        $this->checkoutSession   = $checkoutSession;
        $this->customerSession   = $customerSession;
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        /** @var Json $resultJson */
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
