<?php
declare(strict_types=1);

namespace Ls\Omni\Controller\Ajax;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\GiftCardHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;

class UpdateGiftCard implements HttpPostActionInterface
{
    /**
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
        public JsonFactory $resultJsonFactory,
        public RawFactory $resultRawFactory,
        public GiftCardHelper $giftCardHelper,
        public BasketHelper $basketHelper,
        public CheckoutSession $checkoutSession,
        public CartRepositoryInterface $cartRepository,
        public \Magento\Framework\Pricing\Helper\Data $priceHelper,
        public Data $data,
        public RequestInterface $request
    ) {
    }

    /**
     * Add and remove gift card from checkout page
     *
     * @return Json|Raw
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception|GuzzleException
     */
    public function execute()
    {
        $httpBadRequestCode = 400;
        $resultRaw = $this->resultRawFactory->create();
        $isPost = $this->request->isPost();

        if (!$isPost || !$this->request->isXmlHttpRequest()) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        $resultJson = $this->resultJsonFactory->create();
        $post = $this->request->getContent();
        $postData = json_decode($post);
        $giftCardNo = $postData->gift_card_no;
        $giftCardPin = $postData->gift_card_pin;
        $giftCardAmount = $postData->gift_card_amount;
        $giftCardBalanceAmount = 0;
        $cartId = $this->checkoutSession->getQuoteId();
        $quote = $this->cartRepository->get($cartId);

        if ($giftCardNo != null && $giftCardAmount != 0) {
            $giftCardResponse = $this->giftCardHelper->getGiftCardBalance($giftCardNo, $giftCardPin);

            if (is_object($giftCardResponse)) {
                $convertedGiftCardBalanceArr = $this->giftCardHelper->getConvertedGiftCardBalance($giftCardResponse);
                $giftCardBalanceAmount = $convertedGiftCardBalanceArr['gift_card_balance_amount'];
                $quotePointRate = $convertedGiftCardBalanceArr['quote_point_rate'];
                $giftCardCurrencyCode = $convertedGiftCardBalanceArr['gift_card_currency'];
            } else {
                $giftCardBalanceAmount = $giftCardResponse;
            }

            $quote->setLsGiftCardNo($giftCardNo);
            $quote->setLsGiftCardPin($giftCardPin);
            $quote->setLsGiftCardAmountUsed($giftCardAmount);
            $quote->setLsGiftCardCnyFactor($quotePointRate);
            $quote->setLsGiftCardCnyCode($giftCardCurrencyCode);
            $this->validateQuote($quote);
            $quote->collectTotals();
            $this->cartRepository->save($quote);
        } else {
            try {
                $response = [
                    'success' => 'true',
                    'message' => __(
                        'You have successfully cancelled the gift card.'
                    )
                ];
                $quote->setLsGiftCardNo(null);
                $quote->setLsGiftCardPin(null);
                $quote->setLsGiftCardAmountUsed(0);
                $quote->setLsGiftCardCnyFactor(null);
                $quote->setLsGiftCardCnyCode(null);
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
                'error' => 'true',
                'message' => __(
                    'The gift card is not valid.'
                )
            ];
            return $resultJson->setData($response);
        }

        if ($this->giftCardHelper->isGiftCardExpired($giftCardResponse) && $giftCardAmount) {
            $response = [
                'error' => 'true',
                'message' => __(
                    'Unfortunately, we can\'t apply this gift card since its already expired.'
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
            (float) $giftCardAmount,
            (float) $giftCardBalanceAmount
        );

        if (!is_numeric($giftCardAmount) || $giftCardAmount < 0 || !$isGiftCardAmountValid) {
            $response = [
                'error' => 'true',
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
                    'error' => 'true',
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
