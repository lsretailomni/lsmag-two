<?php

namespace Ls\WebHooks\Model;

use \Ls\Webhooks\Api\OrderStatusInterface;

/**
 * Class OrderStatus
 * @package Ls\WebHooks\Api\Model
 */
class OrderStatus implements OrderStatusInterface
{
    const SUCCESS = "OK";
    const ERROR = "ERROR";

    /**
     * set order status Api.
     *
     * @api
     *
     * @param string $document_id
     * @param string $status
     *
     *
     *
     * @return String
     */
    public function set($document_id, $status)
    {

        try {
            // @codingStandardsIgnoreStart
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/orderstatus.log');
            $logger = new \Zend\Log\Logger();
            // @codingStandardsIgnoreEnd
            $logger->addWriter($writer);
            $data=[
                "document_id"   => $document_id,
                "status"        => $status
            ];
            $logger->info($data);
            return self::SUCCESS;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return self::ERROR;
        } catch (\Exception $e) {
            return self::ERROR;
        }
    }
}
