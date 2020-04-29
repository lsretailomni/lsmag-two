<?php

namespace Ls\WebHooks\Model;

use Exception;
use \Ls\Webhooks\Api\OrderStatusInterface;
use \Ls\Webhooks\Logger\Logger;

/**
 * Class OrderStatus
 * @package Ls\WebHooks\Api\Model
 */
class OrderStatus implements OrderStatusInterface
{
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
     * @inheritdoc
     */
    public function set($documentId, $status)
    {
        try {
            $data = [
                'documentId' => $documentId,
                'status'     => $status
            ];
            $this->logger->info('OrderStatus', $data);
            return [
                "data" => [
                    'success' => true,
                    'message' => 'Status updated successfully.'
                ]
            ];
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return [
                "data" => [
                    'success' => false,
                    'message' => $e->getMessage()
                ]
            ];
        }
    }
}
