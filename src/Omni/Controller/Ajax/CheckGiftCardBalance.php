<?php

namespace Ls\Omni\Controller\Ajax;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\GiftCardHelper;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Pricing\Helper\Data;

/**
 * Class CheckGiftCardBalance
 */
class CheckGiftCardBalance implements HttpPostActionInterface
{

    /** @var JsonFactory */
    public $resultJsonFactory;

    /**
     * @var RawFactory
     */
    public $resultRawFactory;

    /** @var GiftCardHelper */
    private $giftCardHelper;

    /**
     * @var Data
     */
    public $priceHelper;

    /**
     * @var RequestInterface
     */
    public RequestInterface $request;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * CheckGiftCardBalance constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param RawFactory $resultRawFactory
     * @param GiftCardHelper $giftCardHelper
     * @param Data $priceHelper
     * @param LSR $lsr
     * @param RequestInterface $request
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        RawFactory $resultRawFactory,
        GiftCardHelper $giftCardHelper,
        Data $priceHelper,
        LSR $lsr,
        RequestInterface $request
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory  = $resultRawFactory;
        $this->giftCardHelper    = $giftCardHelper;
        $this->priceHelper       = $priceHelper;
        $this->request           = $request;
        $this->lsr               = $lsr;
    }

    /**
     * Entry point for the controller
     *
     * @return ResponseInterface|Json|Raw|ResultInterface
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
        $resultJson   = $this->resultJsonFactory->create();
        $post         = $this->request->getContent();
        $postData     = json_decode($post);
        $giftCardCode = $postData->gift_card_code;
        $giftCardPin  = (isset($postData->gift_card_pin)) ? $postData->gift_card_pin : '';
        $data         = [];
        if ($giftCardCode != null) {
            $giftCardResponse = $this->giftCardHelper->getGiftCardBalance($giftCardCode, $giftCardPin);
            if (is_object($giftCardResponse)) {
                $convertedGiftCardBalanceArr = $this->giftCardHelper->getConvertedGiftCardBalance($giftCardResponse);

                $data['giftcardbalance'] = $this->priceHelper->currency(
                    $convertedGiftCardBalanceArr['gift_card_balance_amount'],
                    true,
                    false
                );
                $data['expirydate']      = $giftCardResponse->getExpireDate();
            } else {
                $data['giftcardbalance'] = $this->priceHelper->currency($giftCardResponse, true, false);
                $data['expirydate']      = null;
            }
            if (empty($giftCardResponse)) {
                $response = [
                    'error'   => 'true',
                    'message' => __(
                        'The gift card is not valid.'
                    )
                ];
            } else {
                $response = [
                    'success' => 'true',
                    'data'    => json_encode($data)
                ];
            }
        } else {
            $response = [
                'error'   => 'true',
                'message' => __(
                    'The gift card is not valid.'
                )
            ];
        }
        return $resultJson->setData($response);
    }
}
