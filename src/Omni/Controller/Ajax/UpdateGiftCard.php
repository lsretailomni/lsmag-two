<?php

namespace Ls\Omni\Controller\Ajax;

use Exception;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\GiftCardHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;

/**
 * Update gift card controller
 */
class UpdateGiftCard implements HttpPostActionInterface
{

    /** @var JsonFactory */
    public $resultJsonFactory;

    /** @var RawFactory */
    public $resultRawFactory;

    /** @var GiftCardHelper */
    public $giftCardHelper;

    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    public $cartRepository;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    public $priceHelper;

    /**
     * @var Data
     */
    public $data;

    /**
     * @var RequestInterface
     */
    public RequestInterface $request;

    /**
     * UpdateGiftCard constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param RawFactory $resultRawFactory
     * @param GiftCardHelper $giftCardHelper
     * @param BasketHelper $basketHelper
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $cartRepository
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param Data $data
     * @param RequestInterface $request
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        RawFactory $resultRawFactory,
        GiftCardHelper $giftCardHelper,
        BasketHelper $basketHelper,
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $cartRepository,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        Data $data,
        RequestInterface $request
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory  = $resultRawFactory;
        $this->giftCardHelper    = $giftCardHelper;
        $this->basketHelper      = $basketHelper;
        $this->checkoutSession   = $checkoutSession;
        $this->cartRepository    = $cartRepository;
        $this->priceHelper       = $priceHelper;
        $this->data              = $data;
        $this->request           = $request;
    }

    /**
     * For updating gift card amount
     *
     * @return ResponseInterface|Json|Raw|ResultInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $httpBadRequestCode = 400;
        /** @var Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        $isPost    = $this->request->isPost();
        if (!$isPost || !$this->request->isXmlHttpRequest()) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        /** @var Json $resultJson */
        $resultJson            = $this->resultJsonFactory->create();
        $post                  = $this->request->getContent();
        $postData              = json_decode($post);
        $giftCardNo            = $postData->gift_card_no;
        $giftCardPin           = $postData->gift_card_pin;
        $giftCardAmount        = $postData->gift_card_amount;
        $giftCardBalanceAmount = 0;
        $cartId                = $this->checkoutSession->getQuoteId();
        $quote                 = $this->cartRepository->get($cartId);
        if ($giftCardNo != null && $giftCardAmount != 0) {
            $giftCardResponse = $this->giftCardHelper->getGiftCardBalance($giftCardNo, $giftCardPin);

            if (is_object($giftCardResponse)) {
                $convertedGiftCardBalanceArr = $this->giftCardHelper->getConvertedGiftCardBalance($giftCardResponse);
                $giftCardBalanceAmount       = $convertedGiftCardBalanceArr['gift_card_balance_amount'];
                $quotePointRate              = $convertedGiftCardBalanceArr['quote_point_rate'];
            } else {
                $giftCardBalanceAmount = $giftCardResponse;
            }
        } else {
            try {
                $response = [
                    'success' => 'true',
                    'message' => __(
                        'You have successfully cancelled the gift card.'
                    )
                ];
                $quote->setLsGiftCardNo($giftCardNo);
                $quote->setLsGiftCardPin($giftCardPin);
                $quote->setLsGiftCardAmountUsed($giftCardAmount);
                $quote->setLsGiftCardCnyFactor($quotePointRate);
                $this->validateQuote($quote);
                $quote->collectTotals();
                $this->cartRepository->save($quote);
                return $resultJson->setData($response);
            } catch (Exception $e) {
                $response = ['error' => 'true', 'message' => $e->getMessage()];
                return $resultJson->setData($response);
            }
        }

        if (empty($giftCardResponse)) {
            $response = [
                'error'   => 'true',
                'message' => __(
                    'The gift card is not valid.'
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
                'error'   => 'true',
                'message' => __(
                    'The applied amount %3' .
                    ' is greater than gift card balance amount (%1) or it is greater than order balance (%2).',
                    $this->priceHelper->currency($giftCardBalanceAmount, true, false),
                    $this->priceHelper->currency($orderBalance, true, false),
                    $this->priceHelper->currency($giftCardAmount, true, false)
                )
            ];
            return $resultJson->setData($response);
        }
        try {
            if ($isGiftCardAmountValid) {
                $quote->setLsGiftCardNo($giftCardNo);
                $quote->setLsGiftCardPin($giftCardPin);
                $quote->setLsGiftCardAmountUsed($giftCardAmount);
                $this->validateQuote($quote);
                $quote->collectTotals();
                $this->cartRepository->save($quote);
                $response = ['success' => 'true'];
            } else {
                $response = [
                    'error'   => 'true',
                    'message' => __(
                        'The gift card amount "%1" is not valid.',
                        $this->priceHelper->currency($giftCardAmount, true, false)
                    )
                ];
            }
        } catch (Exception $e) {
            $response = ['error' => 'true', 'message' => $e->getMessage()];
        }
        return $resultJson->setData($response);
    }

    /**
     * Validate Quote
     *
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
