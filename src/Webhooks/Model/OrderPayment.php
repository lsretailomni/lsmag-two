<?php

namespace Ls\WebHooks\Model;

use \Ls\Webhooks\Api\OrderPaymentInterface;
use \Ls\Omni\Helper\OrderHelper;

/**
 * Class OrderPayment
 * @package Ls\WebHooks\Api\Model
 */
class OrderPayment implements OrderPaymentInterface
{
    const SUCCESS = "OK";
    const ERROR = "ERROR";

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
            // @codingStandardsIgnoreStart
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/orderpayment.log');
            $logger = new \Zend\Log\Logger();
            // @codingStandardsIgnoreEnd
            $logger->addWriter($writer);
            $data=[
                "document_id"   => $document_id,
                "status"        => $status,
                "token"         => $token,
                "amount"        => $amount
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
