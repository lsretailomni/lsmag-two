<?php
declare(strict_types=1);

namespace Ls\Webhooks\Model\Notification;

use \Ls\Core\Model\LSR;
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

    /** @var LSR */
    public $lsr;

    /**
     * @param Data $helper
     * @param Logger $logger
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param ReceiverFactory $receiverFactory
     * @param SenderFactory $senderFactory
     * @param LSR $lsr
     * @param array $data
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        ReceiverFactory $receiverFactory,
        SenderFactory $senderFactory,
        LSR $lsr,
        array $data = []
    ) {
        $this->transportBuilder  = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->lsr = $lsr;
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
        $type    = $this->getNotificationType();
        $storeId = $this->getStoreId();

        return $this->helper->getNotificationTemplate(
            LSR::LS_NOTIFICATION_EMAIL,
            $type,
            $storeId
        );
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
        $centralStoreId = $order->getPickupStore() ??
            $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_STORE, $order->getStore()->getWebsiteId());
        $ccStoreName  = $this->helper->getStoreName($centralStoreId);

        $receiver     = $this->getReceiver();
        $status       = $this->getNotificationType();

        return [
            'order' => $order,
            'items' => $this->getItems(),
            'order_id' => $order->getId(),
            'store' => $order->getStore(),
            'store_name' => $magStoreName,
            'cc_store_name' => $ccStoreName,
            'status' => $status,
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
        $type      = $this->getNotificationType();
        $storeId   = $this->getStoreId();

        switch ($type) {
            case LSR::LS_STATE_PICKED:
                $isEnabled = (bool)$this->helper->isNotifyEnabled(
                    LSR::LS_NOTIFICATION_EMAIL,
                    LSR::LS_STATE_PICKED,
                    $storeId
                );
                break;
            case LSR::LS_STATE_COLLECTED:
                $isEnabled = (bool)$this->helper->isNotifyEnabled(
                    LSR::LS_NOTIFICATION_EMAIL,
                    LSR::LS_STATE_COLLECTED,
                    $storeId
                );
                break;
            case LSR::LS_STATE_CANCELED:
                $isEnabled = (bool)$this->helper->isNotifyEnabled(
                    LSR::LS_NOTIFICATION_EMAIL,
                    LSR::LS_STATE_CANCELED,
                    $storeId
                );
                break;
            default:
                $isEnabled = (bool)$this->helper->isNotifyEnabled(
                    LSR::LS_NOTIFICATION_EMAIL,
                    LSR::LS_STATE_MISC,
                    $storeId
                );
                break;
        }

        return $isEnabled;
    }
}
