<?php
namespace Ls\Webhooks\Observer\GiftCard;

use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Currency\Exception\CurrencyException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\GiftCard\Model\Giftcard;
use Magento\Store\Model\ScopeInterface;

class SendNotification implements ObserverInterface
{
    /**
     * Scope config
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var CurrencyInterface
     */
    private $localeCurrency;

    /**
     * @param CurrencyInterface $localeCurrency
     * @param TransportBuilder $transportBuilder
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        CurrencyInterface $localeCurrency,
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->localeCurrency = $localeCurrency;
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Entry point for the observer
     *
     *
     * @param Observer $observer
     * @return $this
     * @throws CurrencyException
     * @throws LocalizedException
     * @throws MailException
     */
    public function execute(Observer $observer)
    {
        $giftCardOrderItem = $observer->getEvent()->getGiftCardOrderItem();
        $salesEntryLine = $observer->getEvent()->getSalesEntryLine();
        $storeId = $giftCardOrderItem->getStoreId();
        $sender = $giftCardOrderItem->getProductOptionByCode('giftcard_sender_name');
        $senderName = $giftCardOrderItem->getProductOptionByCode('giftcard_sender_name');
        $senderEmail = $giftCardOrderItem->getProductOptionByCode('giftcard_sender_email');
        $extraInformation = $salesEntryLine->getExtraInformation();
        list($code, $pin) = $this->getGiftCardDetails($extraInformation);

        if ($senderEmail) {
            $sender = "{$sender} <{$senderEmail}>";
        }
        $amount = $giftCardOrderItem->getPrice();
        $baseCurrencyCode = $giftCardOrderItem->getStore()
            ->getBaseCurrencyCode();
        $balance = $this->localeCurrency->getCurrency($baseCurrencyCode)
            ->toCurrency($amount);

        $templateData = [
            'name' => $giftCardOrderItem->getProductOptionByCode('giftcard_recipient_name'),
            'email' => $giftCardOrderItem->getProductOptionByCode('giftcard_recipient_email'),
            'sender_name_with_email' => $sender,
            'sender_name' => $senderName,
            'gift_message' => $giftCardOrderItem->getProductOptionByCode('giftcard_message'),
            'balance' => $balance,
            'store' => $giftCardOrderItem->getStore(),
            'store_name' => $giftCardOrderItem->getStore()->getName(),
            'code' => $code,
            'pin' => $pin
        ];

        $emailIdentity = $this->scopeConfig->getValue(
            Giftcard::XML_PATH_EMAIL_IDENTITY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $templateOptions = [
            'area' => Area::AREA_FRONTEND,
            'store' => $storeId,
        ];
        $recipientAddress = $giftCardOrderItem->getProductOptionByCode('giftcard_recipient_email');
        $recipientName = $giftCardOrderItem->getProductOptionByCode('giftcard_recipient_name');
        $template = $giftCardOrderItem->getProductOptionByCode('giftcard_email_template');

        $transport = $this->transportBuilder->setTemplateIdentifier($template)
            ->setTemplateOptions($templateOptions)
            ->setTemplateVars($templateData)
            ->setFrom($emailIdentity)
            ->addTo($recipientAddress, $recipientName)
            ->getTransport();
        $transport->sendMessage();

        return $this;
    }

    /**
     * Get gift card details
     *
     * @param $extraInformation
     * @return array
     */
    public function getGiftCardDetails($extraInformation)
    {
        $extraInformationArray = explode(' ', $extraInformation);
        $code = $pin = '';

        foreach ($extraInformationArray as $info) {
            if (str_contains($info, 'Code:')) {
                $code = explode('Code:', $info)[1];
            }
            if (str_contains($info, 'Pin:')) {
                $pin = explode('Pin:', $info)[1];
            }
        }

        return [$code, $pin];
    }
}
