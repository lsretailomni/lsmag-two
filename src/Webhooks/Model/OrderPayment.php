<?php
declare(strict_types=1);

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
     * @param Logger $logger
     * @param Payment $payment
     * @param Data $helper
     */
    public function __construct(
        public Logger $logger,
        public Payment $payment,
        public Data $helper
    ) {
    }

    /**
     * @inheritdoc
     */
    public function set(\Ls\Webhooks\Api\Data\OrderPaymentMessageInterface $orderPayment)
    {
        try {
            $data = [
                'OrderId' => $orderPayment->getOrderId(),
                'Status' => $orderPayment->getStatus(),
                'Amount' => $orderPayment->getAmount(),
                'CurrencyCode' => $orderPayment->getCurrencyCode(),
                'Token' => $orderPayment->getToken(),
                'AuthCode' => $orderPayment->getAuthCode(),
                'Reference' => $orderPayment->getReference(),
                'Lines' => $this->formatOrderLines($orderPayment->getLines()),
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

    /**
     * Get formatted lines
     *
     * @param \Ls\Webhooks\Api\Data\OrderLineInterface[] $lines
     * @return array
     */
    public function formatOrderLines(array $lines)
    {
        $formattedLines = [];
        if (!empty($lines)) {
            foreach ($lines as $line) {
                $formattedLines[] = [
                    'NewStatus' => $line->getNewStatus(),
                    'ItemId' => $line->getItemId(),
                    'Quantity' => $line->getQuantity(),
                    'UnitOfMeasureId' => $line->getUnitOfMeasureId(),
                    'VariantId' => $line->getVariantId(),
                    'Amount' => $line->getAmount(),
                ];
            }
        }

        return $formattedLines;
    }
}
