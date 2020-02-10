<?php

namespace Ls\WebHooks\Model;

use Exception;
use \Ls\Webhooks\Api\OrderStatusInterface;
use \Ls\Webhooks\Logger\Logger;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class OrderStatus
 * @package Ls\WebHooks\Api\Model
 */
class OrderStatus implements OrderStatusInterface
{
    const SUCCESS = "OK";
    const ERROR = "ERROR";

    /**
     * @var Logger
     */
    public $logger;

    /**
     * OrderStatus constructor.
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * set order status Api.
     *
     * @param string $document_id
     * @param string $status
     *
     *
     *
     * @return String
     * @api
     *
     */
    public function set($document_id, $status)
    {
        try {
            $data = [
                "document_id" => $document_id,
                "status"      => $status
            ];
            $this->logger->info("OrderStatus", $data);
            return self::SUCCESS;
        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage());
            return self::ERROR;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return self::ERROR;
        }
    }
}
