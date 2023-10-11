<?php

namespace Ls\Omni\Controller\Ajax;

use \Ls\Omni\Helper\GiftCardHelper;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Check pin code configuration is enable or not
 *
 */
class CheckPinCodeEnable implements HttpGetActionInterface
{

    /** @var JsonFactory */
    public $resultJsonFactory;

    /** @var GiftCardHelper */
    private $giftCardHelper;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param GiftCardHelper $giftCardHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        GiftCardHelper $giftCardHelper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->giftCardHelper    = $giftCardHelper;
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $configValue = $this->giftCardHelper->isPinCodeFieldEnable();
        $response    = [
            'success' => true,
            'value'   => (int)$configValue
        ];
        $resultJson  = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}
