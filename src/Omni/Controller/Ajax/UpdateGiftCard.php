<?php

namespace Ls\Omni\Controller\Ajax;

use Magento\Framework\App\Action\Context;
use \Ls\Omni\Helper\GiftCardHelper;
use \Ls\Omni\Helper\Data;
use \Ls\Core\Model\LSR;

/**
 * Class UpdatePoints
 * @package Ls\Omni\Controller\Ajax
 */
class UpdateGiftCard extends \Magento\Framework\App\Action\Action
{

    /** @var \Magento\Framework\Controller\Result\JsonFactory */
    public $resultJsonFactory;

    /** @var \Magento\Framework\Controller\Result\RawFactory */
    public $resultRawFactory;

    /** @var GiftCardHelper */
    public $giftCardHelper;

    /**
     * @var \Ls\Omni\Helper\BasketHelper
     */
    public $basketHelper;

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
     * @var priceHelper
     */
    public $priceHelper;

    /**
     * @var Data
     */
    public $data;

    /**
     * UpdateGiftCard constructor.
     * @param Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param GiftCardHelper $giftCardHelper
     * @param \Ls\Omni\Helper\BasketHelper $basketHelper
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param Data $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        GiftCardHelper $giftCardHelper,
        \Ls\Omni\Helper\BasketHelper $basketHelper,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Ls\Omni\Helper\Data $data
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->giftCardHelper = $giftCardHelper;
        $this->basketHelper = $basketHelper;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->cartRepository = $cartRepository;
        $this->priceHelper = $priceHelper;
        $this->data = $data;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Raw|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
        $base_currency = $this->checkoutSession->getQuote()->getBaseCurrencyCode();
        $post = $this->getRequest()->getContent();
        $postData = json_decode($post);
        $giftCardNo = $postData->gift_card_no;
        $giftCardAmount = $postData->gift_card_amount;
        $giftCardBalanceAmount = 0;
        $cartId = $this->checkoutSession->getQuoteId();
        $quote = $this->cartRepository->get($cartId);
        if ($giftCardNo != null && $giftCardAmount != 0) {
            $giftCardResponse = $this->giftCardHelper->getGiftCardBalance($giftCardNo);

            if (is_object($giftCardResponse)) {
                $giftCardBalanceAmount = $giftCardResponse->getBalance();
            } else {
                $giftCardBalanceAmount = $giftCardResponse;
            }
        } else {
            $response = [
                'success' => 'true',
                'message' => __(
                    'You have successfully cancelled the gift card.'
                )
            ];
            $quote->setLsGiftCardNo($giftCardNo);
            $quote->setLsGiftCardAmountUsed($giftCardAmount);
            $quote->setCouponCode($this->checkoutSession->getCouponCode());
            $this->validateQuote($quote);
            $quote->collectTotals();
            $this->cartRepository->save($quote);
            return $resultJson->setData($response);
        }

        if (empty($giftCardResponse)) {
            $response = [
                'error' => 'true',
                'message' => __(
                    'The gift card code %1 is not valid.', $giftCardNo
                )
            ];
            return $resultJson->setData($response);
        }

        $orderBalance = $this->data->getOrderBalance(
            0,
            $quote->getLsPointsSpent(),
            $this->basketHelper->getBasketSessionValue()
        );

        $isGiftCardAmountValid = $this->giftCardHelper->isGiftCardAmountValid(
            $orderBalance,
            $giftCardAmount,
            $giftCardBalanceAmount
        );

        if (!is_numeric($giftCardAmount) || $giftCardAmount < 0 || !$isGiftCardAmountValid) {
            $response = [
                'error' => 'true',
                'message' => __(
                    'The applied amount ' . $this->priceHelper->currency($giftCardAmount, true, false) .
                    ' is greater than gift card balance amount (%1)
                      or it is greater than order balance (Excl. Shipping Amount) (%2).',
                    $this->priceHelper->currency($giftCardBalanceAmount, true, false),
                    $this->priceHelper->currency($orderBalance, true, false)
                )
            ];
            return $resultJson->setData($response);
        }
        try {

            if ($isGiftCardAmountValid) {
                $quote->setLsGiftCardNo($giftCardNo);
                $quote->setLsGiftCardAmountUsed($giftCardAmount);
                $this->validateQuote($quote);
                $quote->collectTotals();
                $this->cartRepository->save($quote);
                $response = ['success' => 'true'];
            } else {
                $response = [
                    'error' => 'true',
                    'message' => __(
                        'The gift card amount "%1" is not valid.',
                        $this->priceHelper->currency($giftCardAmount, true, false)
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