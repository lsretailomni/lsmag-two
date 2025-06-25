<?php
declare(strict_types=1);

namespace Ls\Omni\Controller\Ajax;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\GiftCardHelper;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data;

class CheckGiftCardBalance implements HttpPostActionInterface
{
    /**
     * @param JsonFactory $resultJsonFactory
     * @param RawFactory $resultRawFactory
     * @param GiftCardHelper $giftCardHelper
     * @param Data $priceHelper
     * @param LSR $lsr
     * @param RequestInterface $request
     */
    public function __construct(
        public JsonFactory $resultJsonFactory,
        public RawFactory $resultRawFactory,
        public GiftCardHelper $giftCardHelper,
        public Data $priceHelper,
        public LSR $lsr,
        public RequestInterface $request
    ) {
    }

    /**
     * Entry point for the controller
     *
     * @return Json|Raw
     * @throws NoSuchEntityException|GuzzleException
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
        $giftCardCode = $postData->gift_card_code;
        $giftCardPin = (isset($postData->gift_card_pin)) ? $postData->gift_card_pin : '';
        $data = [];

        if ($giftCardCode != null) {
            $giftCardResponse = $this->giftCardHelper->getGiftCardBalance($giftCardCode, $giftCardPin);

            if (is_object($giftCardResponse)) {
                $convertedGiftCardBalanceArr = $this->giftCardHelper->getConvertedGiftCardBalance($giftCardResponse);

                $data['giftcardbalance'] = $this->priceHelper->currency(
                    $convertedGiftCardBalanceArr['gift_card_balance_amount'],
                    true,
                    false
                );
                $data['expirydate'] = $giftCardResponse->getExpirydate();
            } else {
                $data['giftcardbalance'] = $this->priceHelper->currency($giftCardResponse, true, false);
                $data['expirydate'] = null;
            }

            if (empty($giftCardResponse)) {
                $response = [
                    'error' => 'true',
                    'message' => __(
                        'The gift card is not valid.'
                    )
                ];
            } else {
                $response = [
                    'success' => 'true',
                    'data' => json_encode($data)
                ];
            }
        } else {
            $response = [
                'error' => 'true',
                'message' => __(
                    'The gift card is not valid.'
                )
            ];
        }

        return $resultJson->setData($response);
    }
}
