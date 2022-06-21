<?php

namespace Ls\Omni\Controller\Ajax;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;

/**
 * Class UpdatePoints
 * @package Ls\Omni\Controller\Ajax
 */
class UpdatePoints extends Action
{

    /** @var JsonFactory */
    public $resultJsonFactory;

    /** @var RawFactory */
    public $resultRawFactory;

    /** @var LoyaltyHelper */
    public $loyaltyHelper;

    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * @var Proxy
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
     * @var Data
     */
    public $data;

    /**
     * UpdatePoints constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param RawFactory $resultRawFactory
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param LoyaltyHelper $loyaltyHelper
     * @param BasketHelper $basketHelper
     * @param Data $data
     * @param Proxy $checkoutSession
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        RawFactory $resultRawFactory,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        LoyaltyHelper $loyaltyHelper,
        BasketHelper $basketHelper,
        Data $data,
        Proxy $checkoutSession,
        CartRepositoryInterface $cartRepository
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory  = $resultRawFactory;
        $this->loyaltyHelper     = $loyaltyHelper;
        $this->checkoutSession   = $checkoutSession;
        $this->customerSession   = $customerSession;
        $this->cartRepository    = $cartRepository;
        $this->basketHelper      = $basketHelper;
        $this->data              = $data;
    }

    /**
     * For updating loyalty points amount
     *
     * @return $this|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $httpBadRequestCode = 400;
        $resultRaw          = $this->resultRawFactory->create();
        if ($this->getRequest()->getMethod() !== 'POST' || !$this->getRequest()->isXmlHttpRequest()) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        $resultJson = $this->resultJsonFactory->create();
        if (!$this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID)) {
            $response = [
                'error'   => 'true',
                'message' => __('Customer session not found.')
            ];
            return $resultJson->setData($response);
        }
        $post          = $this->getRequest()->getContent();
        $postData      = json_decode($post);
        $loyaltyPoints = (float)$postData->loyaltyPoints;
        $isPointValid  = $this->loyaltyHelper->isPointsAreValid($loyaltyPoints);
        if (!is_numeric($loyaltyPoints) || $loyaltyPoints < 0 || !$isPointValid) {
            $response = [
                'error'   => 'true',
                'message' => __(
                    'The loyalty points "%1" are not valid.',
                    $loyaltyPoints
                )
            ];
            return $resultJson->setData($response);
        }
        try {
            $cartId             = $this->checkoutSession->getQuoteId();
            $quote              = $this->cartRepository->get($cartId);
            $orderBalance       = $this->data->getOrderBalance(
                $quote->getLsGiftCardAmountUsed(),
                0,
                $this->basketHelper->getBasketSessionValue()
            );
            $isPointsLimitValid = $this->loyaltyHelper->isPointsLimitValid($orderBalance, $loyaltyPoints);
            if ($isPointsLimitValid) {
                $quote->setLsPointsSpent($loyaltyPoints);
                $this->validateQuote($quote);
                $quote->collectTotals();
                $this->cartRepository->save($quote);
                $response = ['success' => 'true'];
            } else {
                $response = [
                    'error'   => 'true',
                    'message' => __(
                        'The loyalty points "%1" are exceeding order total amount.',
                        $loyaltyPoints
                    )
                ];
            }
        } catch (Exception $e) {
            $response = ['error' => 'true', 'message' => $e->getMessage()];
        }
        return $resultJson->setData($response);
    }

    /**
     * @param Quote $quote
     * @return void
     * @throws LocalizedException
     */
    protected function validateQuote(Quote $quote)
    {
        if ($quote->getItemsCount() === 0) {
            throw new LocalizedException(
                __('Totals calculation is not applicable to empty cart.')
            );
        }
    }
}
