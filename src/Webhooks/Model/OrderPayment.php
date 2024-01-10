<?php

namespace Ls\Webhooks\Model;

use \Ls\Webhooks\Api\OrderPaymentInterface;
use \Ls\Webhooks\Model\Order\Payment;
use \Ls\Webhooks\Helper\Data;
use \Ls\Webhooks\Logger\Logger;

/**
 * Class for handling order payment and invoice
 */
class OrderPayment implements OrderPaymentInterface
{

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var Payment
     */
    public $payment;

    /**
     * @var Data
     */
    public $helper;

    /**
     * OrderPayment constructor.
     * @param Logger $logger
     * @param Payment $payment
     * @param Data $helper
     */
    public function __construct(
        Logger $logger,
        Payment $payment,
        Data $helper
    ) {
        $this->logger  = $logger;
        $this->payment = $payment;
        $this->helper  = $helper;
    }

    /**
     * @inheritdoc
     */
    public function set($orderId, $status, $amount, $currencyCode, $token, $authCode, $reference, $lines)
    {
        try {
            $data = [
                'OrderId'      => $orderId,
                'Status'       => $status,
                'Amount'       => $amount,
                'CurrencyCode' => $currencyCode,
                'Token'        => $token,
                'AuthCode'     => $authCode,
                'Reference'    => $reference,
                'Lines'        => $lines

            ];
            $this->logger->info('OrderPayment = ', $data);
            if (!empty($data['OrderId'])) {
                return $this->payment->generateInvoice($data);
            }
            return $this->helper->outputMessage(false, 'Document Id is not valid.');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->helper->outputMessage(false, $e->getMessage());
        }
    }
}
