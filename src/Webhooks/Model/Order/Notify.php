<?php
declare(strict_types=1);

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
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $state
     * @param Data $helper
     * @param Logger $logger
     */
    public function __construct(
        public TransportBuilder $transportBuilder,
        public StateInterface $state,
        public Data $helper,
        public Logger $logger
    ) {
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
        $storeName    = $this->helper->getSenderName($storeId);
        try {
            $this->inlineTranslation->suspend();

            $templateOptions = [
                'area'  => Area::AREA_FRONTEND,
                'store' => $storeId
            ];
            $sender          = [
                'name'  => $storeName,
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
