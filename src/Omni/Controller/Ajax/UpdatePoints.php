<?php

namespace Ls\Omni\Controller\Ajax;

use Magento\Framework\App\Action\Context;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Core\Model\LSR;

/**
 * Class UpdatePoints
 * @package Ls\Omni\Controller\Ajax
 */
class UpdatePoints extends \Magento\Framework\App\Action\Action
{

    /** @var \Magento\Framework\Controller\Result\JsonFactory */
    public $resultJsonFactory;

    /** @var \Magento\Framework\Controller\Result\RawFactory */
    public $resultRawFactory;

    /** @var LoyaltyHelper */
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
     * @var CartRepositoryInterface
     */
    public $cartRepository;

    /**
     * Points constructor.
     * @param Context $context
     * @param \Magento\Framework\Json\Helper\Data $helper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param LoyaltyHelper $loyaltyHelper
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Json\Helper\Data $helper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        LoyaltyHelper $loyaltyHelper,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->loyaltyHelper = $loyaltyHelper;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->cartRepository = $cartRepository;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $httpBadRequestCode = 400;
        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        if ($this->getRequest()->getMethod() !== 'POST' || !$this->getRequest()->isXmlHttpRequest()) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        if (!$this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID)) {
            $response = [
                'error' => 'true',
                'message' => __('Customer session not found.')
            ];
            return $resultJson->setData($response);
        }
        $base_currency = $this->checkoutSession->getQuote()->getBaseCurrencyCode();
        $post = $this->getRequest()->getContent();
        $postData = json_decode($post);
        $loyaltyPoints = (int)$postData->loyaltyPoints;
        $isPointValid = $this->loyaltyHelper->isPointsAreValid($loyaltyPoints);
        if (!is_numeric($loyaltyPoints) || $loyaltyPoints < 0 || !$isPointValid) {
            $response = [
                    'error' => 'true',
                    'message' => __(
                        'The loyalty points "%1" are not valid.',
                        $loyaltyPoints
                    )
                ];
            return $resultJson->setData($response);
        }
        try {
            $cartId = $this->checkoutSession->getQuoteId();
            $quote = $this->cartRepository->get($cartId);            
            $isPointsLimitValid = $this->loyaltyHelper->isPointsLimitValid($quote->getBaseGrandTotal(), $loyaltyPoints);
            if ($isPointsLimitValid) {
                $quote->setLsPointsSpent($loyaltyPoints);
                $this->validateQuote($quote);
                $quote->collectTotals();
                $this->cartRepository->save($quote);
                $response = ['success' => 'true'];
            } else {
                $response = [
                    'error' => 'true',
                    'message' => __(
                        'The loyalty points "%1" are not valid.',
                        $loyaltyPoints
                    )
                ];
            }
        } catch (\Exception $e) {
            $response = ['error' => 'true', 'message' => $e->getMessage()];
        }
        return $resultJson->setData($response);
    }


    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    protected function validateQuote(\Magento\Quote\Model\Quote $quote)
    {
        if ($quote->getItemsCount() === 0) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Totals calculation is not applicable to empty cart.')
            );
        }
    }
}
