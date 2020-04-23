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
    const SUCCESS = 'OK';
    const ERROR = 'ERROR';

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
    public function set($document_id, $status)
    {
        try {
            $data = [
                'document_id' => $document_id,
                'status'      => $status
            ];
            $this->logger->info('OrderStatus', $data);
            return self::SUCCESS;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return self::ERROR;
        }
    }
}
