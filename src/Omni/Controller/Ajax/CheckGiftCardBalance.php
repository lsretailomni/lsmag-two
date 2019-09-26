<?php

namespace Ls\Omni\Controller\Ajax;

use \Ls\Omni\Helper\GiftCardHelper;
use Magento\Framework\App\Action\Context;

/**
 * Class CheckGiftCardBalance
 * @package Ls\Omni\Controller\Ajax
 */
class CheckGiftCardBalance extends \Magento\Framework\App\Action\Action
{

    /** @var \Magento\Framework\Controller\Result\JsonFactory */
    public $resultJsonFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    public $resultRawFactory;

    /** @var GiftCardHelper */
    private $giftCardHelper;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    public $priceHelper;

    /**
     * CheckGiftCardBalance constructor.
     * @param Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param GiftCardHelper $giftCardHelper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        GiftCardHelper $giftCardHelper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->giftCardHelper = $giftCardHelper;
        $this->priceHelper = $priceHelper;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Raw|\Magento\Framework\Controller\ResultInterface
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
        $post = $this->getRequest()->getContent();
        $postData = json_decode($post);
        $giftCardCode = $postData->gift_card_code;
        $data = [];
        if ($giftCardCode != null) {
            $giftCardResponse = $this->giftCardHelper->getGiftCardBalance($giftCardCode);
            if (is_object($giftCardResponse)) {
                $data['giftcardbalance'] = $this->priceHelper->currency($giftCardResponse->getBalance(), true, false);
                $data['expirydate'] = $giftCardResponse->getExpireDate();
            } else {
                $data['giftcardbalance'] = $this->priceHelper->currency($giftCardResponse, true, false);
                $data['expirydate'] = null;
            }
            if (empty($giftCardResponse)) {
                $response = [
                    'error' => 'true',
                    'message' => __(
                        'The gift card code %1 is not valid.',
                        $giftCardCode
                    )
                ];
            } else {
                $response = [
                    'success' => 'true',
                    'data' => json_encode($data)
                ];
            }
            return $resultJson->setData($response);
        } else {
            $response = [
                'error' => 'true',
                'message' => __(
                    'The gift card code %1 is not valid.',
                    $giftCardCode
                )
            ];
            return $resultJson->setData($response);
        }

        return $resultJson->setData($response);
    }
}
