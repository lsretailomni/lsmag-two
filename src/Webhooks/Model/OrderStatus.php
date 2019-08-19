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
     * @var \Ls\Webhooks\Logger\Logger
     */
    public $logger;

    /**
     * OrderStatus constructor.
     * @param \Ls\Webhooks\Logger\Logger $logger
     */
    public function __construct(
        \Ls\Webhooks\Logger\Logger $logger
    ) {
        $this->logger = $logger;
    }

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
            $data=[
                "document_id"   => $document_id,
                "status"        => $status
            ];
            $this->logger->info("OrderStatus", $data);
            return self::SUCCESS;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->error($e->getMessage());
            return self::ERROR;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return self::ERROR;
        }
    }
}
