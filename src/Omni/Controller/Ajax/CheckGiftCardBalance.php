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
use Magento\Framework\Exception\LocalizedException;
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
     * @throws GuzzleException
     * @throws LocalizedException
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

        $resultJson   = $this->resultJsonFactory->create();
        $post         = $this->request->getContent();
        $postData     = json_decode($post);
        $giftCardCode = $postData->gift_card_code ?? null;
        $giftCardPin  = $postData->gift_card_pin ?? '';

        if (empty($giftCardCode)) {
            return $resultJson->setData([
                'error'   => 'true',
                'message' => __('The gift card / voucher code is not valid.')
            ]);
        }

        // Try each configured entry type in turn; use the first one that returns a balance.
        $entryTypes       = $this->lsr->getVoucherGiftCardConfiguration();
        $giftCardResponse = null;
        $matchedEntryType = null;

        foreach ($entryTypes as $entryConfig) {
            $entryType        = $entryConfig['code'] ?? '';
            if (empty($entryType)) {
                continue;
            }
            $giftCardResponse = $this->giftCardHelper->getGiftCardBalance($giftCardCode, $giftCardPin, $entryType);
            if ($giftCardResponse !== null) {
                $matchedEntryType = $entryType;
                break;
            }
        }

        if (empty($giftCardResponse)) {
            return $resultJson->setData([
                'error'   => 'true',
                'message' => __('The gift card / voucher code is not valid.')
            ]);
        }

        $data = [];
        if (is_object($giftCardResponse)) {
            $convertedGiftCardBalanceArr = $this->giftCardHelper->getConvertedGiftCardBalance($giftCardResponse);
            $data['giftcardbalance'] = $this->giftCardHelper->formatValue(
                $convertedGiftCardBalanceArr['gift_card_balance_amount'],
                true
            );
            $data['expirydate']  = $this->giftCardHelper->formatExpireDate($giftCardResponse->getExpirydate());
            $data['entry_type']  = $matchedEntryType;
        } else {
            $data['giftcardbalance'] = $this->giftCardHelper->formatValue($giftCardResponse, true);
            $data['expirydate']      = null;
            $data['entry_type']      = $matchedEntryType;
        }

        return $resultJson->setData([
            'success' => 'true',
            'data'    => json_encode($data)
        ]);
    }
}
