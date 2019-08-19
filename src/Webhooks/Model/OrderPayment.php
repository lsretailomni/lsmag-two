<?php

namespace Ls\WebHooks\Model;

use \Ls\Webhooks\Api\OrderPaymentInterface;

/**
 * Class OrderPayment
 * @package Ls\WebHooks\Api\Model
 */
class OrderPayment implements OrderPaymentInterface
{
    const SUCCESS = "OK";
    const ERROR = "ERROR";

    /**
     * @var \Ls\Webhooks\Logger\Logger
     */
    public $logger;

    /**
     * OrderPayment constructor.
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
     * @param string $status (Unchanged=0,Changed=1,Cancelled=2)
     * @param string $token
     * @param double $amount
     *
     * @return String
     */
    public function set($document_id, $status, $token, $amount)
    {
        try {
            $data=[
                "document_id"   => $document_id,
                "status"        => $status,
                "token"         => $token,
                "amount"        => $amount
            ];
            $this->logger->info("OrderPayment", $data);
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
