<?php

namespace Ls\Webhooks\Model\Notification;

use \Ls\Webhooks\Helper\Data;
use \Ls\Webhooks\Logger\Logger;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;

/**
 * Notification email
 */
class EmailNotification extends AbstractNotification
{
    /**
     * @var TransportBuilder
     */
    public $transportBuilder;

    /**
     * @var StateInterface
     */
    public $inlineTranslation;

    /**
     * @param Data $helper
     * @param Logger $logger
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param ReceiverFactory $receiverFactory
     * @param SenderFactory $senderFactory
     * @param array $data
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        ReceiverFactory $receiverFactory,
        SenderFactory $senderFactory,
        array $data = []
    ) {
        $this->transportBuilder  = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        parent::__construct($helper, $logger, $receiverFactory, $senderFactory, $data);
    }

    /**
     * @inheritDoc
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function notify()
    {
        $sender       = $this->getSender();
        $receiver     = $this->getReceiver();
        $templateVars = $this->getTemplateVars();
        $templateId   = $this->getEmailTemplateId();
        $storeId      = $this->getStoreId();
        $toEmail      = $receiver->getReceiverEmail();
        try {
            if ($templateId) {
                $this->inlineTranslation->suspend();

                $templateOptions = [
                    'area' => Area::AREA_FRONTEND,
                    'store' => $storeId
                ];
                $sender          = [
                    'name' => $sender->getSenderName(),
                    'email' => $sender->getSenderEmail(),
                ];
                $transport       = $this->transportBuilder->setTemplateIdentifier($templateId)
                    ->setTemplateOptions($templateOptions)
                    ->setTemplateVars($templateVars)
                    ->addTo($toEmail)
                    ->setFromByScope($sender, $storeId)
                    ->getTransport();
                $transport->sendMessage();
                $this->inlineTranslation->resume();
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Get email template ID
     *
     * @return string
     */
    public function getEmailTemplateId()
    {
        $type = $this->getNotificationType();
        $storeId = $this->getStoreId();
        $emailTemplateId = null;

        switch ($type) {
            case 0:
                $emailTemplateId = $this->helper->getPickupTemplate($storeId);
                break;
            case 1:
                $emailTemplateId = $this->helper->getCollectedTemplate($storeId);
                break;
            case 2:
                $emailTemplateId = $this->helper->getCancelTemplate($storeId);
                break;
        }

        return $emailTemplateId;
    }

    /**
     * Get template vars
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getTemplateVars()
    {
        $order        = $this->getOrder();
        $magStoreName = $order->getStore()->getFrontEndName();
        $ccStoreName  = $this->helper->getStoreName($order->getPickupStore());
        $receiver     = $this->getReceiver();

        return [
            'order' => $order,
            'items' => $this->getItems(),
            'order_id' => $order->getId(),
            'store' => $order->getStore(),
            'store_name' => $magStoreName,
            'cc_store_name' => $ccStoreName,
            'order_data' => [
                'customer_name' => $receiver->getReceiverName(),
                'email_customer_note' => $order->getEmailCustomerNote(),
            ]
        ];
    }

    /**
     * Is notification enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        $type = $this->getNotificationType();
        $storeId = $this->getStoreId();
        $isEnabled = false;

        switch ($type) {
            case 0:
                $isEnabled = (bool) $this->helper->isPickupNotifyEnabled($storeId);
                break;
            case 1:
                $isEnabled = (bool) $this->helper->isCollectedNotifyEnabled($storeId);
                break;
            case 2:
                $isEnabled = (bool) $this->helper->isCancelNotifyEnabled($storeId);
                break;
        }

        return $isEnabled;
    }
}
