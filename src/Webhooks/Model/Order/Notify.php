<?php

namespace Ls\Webhooks\Model\Order;

use \Ls\Webhooks\Helper\Data;
use \Ls\Webhooks\Logger\Logger;
use Magento\Framework\App\Area;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;

/**
 * Notification email
 */
class Notify
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;
    /**
     * @var StateInterface
     */
    private $inlineTranslation;

    /**
     * @var Data
     */
    private $helper;

    /**
     * Notify constructor.
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $state
     * @param Data $helper
     */
    public function __construct(
        TransportBuilder $transportBuilder,
        StateInterface $state,
        Data $helper,
        Logger $logger
    ) {
        $this->transportBuilder  = $transportBuilder;
        $this->inlineTranslation = $state;
        $this->helper            = $helper;
        $this->logger            = $logger;
    }

    /**
     * For sending email notification
     * @param $templateId
     * @param $templateVars
     * @param $order
     */
    public function sendEmail($templateId, $templateVars, $order)
    {
        $storeId      = $order->getStoreId();
        $toEmail      = $order->getCustomerEmail();
        $storeEmail   = $this->helper->getStoreEmail($storeId);
        $magStoreName = $order->getStore()->getFrontEndName();
        try {
            $this->inlineTranslation->suspend();

            $templateOptions = [
                'area'  => Area::AREA_FRONTEND,
                'store' => $storeId
            ];
            $sender          = [
                'name'  => $magStoreName,
                'email' => $storeEmail,
            ];
            $transport       = $this->transportBuilder->setTemplateIdentifier($templateId)
                ->setTemplateOptions($templateOptions)
                ->setTemplateVars($templateVars)
                ->addTo($toEmail)
                ->setFromByScope($sender, $storeId)
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Set template variable for click and collect email
     * @param $order
     * @param $items
     * @return array
     */
    public function setTemplateVars($order, $items)
    {
        $magStoreName = $order->getStore()->getFrontEndName();
        $ccStoreName  = $this->helper->getStoreName($order->getPickupStore());
        return [
            'order'         => $order,
            'items'         => $items,
            'order_id'      => $order->getId(),
            'store'         => $order->getStore(),
            'store_name'    => $magStoreName,
            'cc_store_name' => $ccStoreName,
            'order_data'    => [
                'customer_name'       => $order->getCustomerName(),
                'email_customer_note' => $order->getEmailCustomerNote(),
            ]
        ];
    }
}
