<?php
declare(strict_types=1);

namespace Ls\Omni\Controller\Ajax;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
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

class UpdatePoints implements HttpPostActionInterface
{
    /**
     * @param JsonFactory $resultJsonFactory
     * @param RawFactory $resultRawFactory
     * @param CustomerSession $customerSession
     * @param LoyaltyHelper $loyaltyHelper
     * @param BasketHelper $basketHelper
     * @param Data $data
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $cartRepository
     * @param RequestInterface $request
     */
    public function __construct(
        public JsonFactory $resultJsonFactory,
        public RawFactory $resultRawFactory,
        public CustomerSession $customerSession,
        public LoyaltyHelper $loyaltyHelper,
        public BasketHelper $basketHelper,
        public Data $data,
        public CheckoutSession $checkoutSession,
        public CartRepositoryInterface $cartRepository,
        public RequestInterface $request
    ) {
    }

    /**
     * Add or remove loyalty points from checkout page
     *
     * @return Json|Raw
     * @throws GuzzleException
     * @throws NoSuchEntityException
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
        if (!$this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID)) {
            $response = [
                'error' => 'true',
                'message' => __('Customer session not found.')
            ];
            return $resultJson->setData($response);
        }
        $post = $this->request->getContent();
        $postData = json_decode($post);
        $loyaltyPoints = (float)$postData->loyaltyPoints;
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
            $orderBalance = $this->data->getOrderBalance(
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
                    'error' => 'true',
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
     * Validate quote
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
