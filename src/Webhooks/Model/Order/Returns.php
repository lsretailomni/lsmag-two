<?php

namespace Ls\Webhooks\Model\Order;

use \Ls\Webhooks\Helper\Data;
use \Ls\Core\Model\LSR;
use \Ls\Webhooks\Logger\Logger;

/**
 * Class for processing returns
 */
class Returns
{
    /**
     * @var CreditMemo
     */
    public $creditMemo;

    /**
     * @var Data
     */
    public $helper;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * Returns constructor.
     *
     * @param CreditMemo $creditMemo
     * @param Data $helper
     * @param Logger $logger
     */
    public function __construct(
        CreditMemo $creditMemo,
        Data $helper,
        Logger $logger
    ) {
        $this->creditMemo = $creditMemo;
        $this->helper     = $helper;
        $this->logger     = $logger;
    }

    /**
     * Create returns for invoiced items
     *
     * @param $data
     * @return array[]|false|mixed
     */
    public function returns($data)
    {
        try {
            $magOrder       = $this->helper->getOrderByDocumentId($data['OrderId']);
            $shippingItemId = $this->helper->getShippingItemId();

            $orderItemsMap = $this->buildOrderItemsMap($magOrder);

            // Group items by invoice
            $groupResult = $this->groupItemsByInvoice($data['Lines'], $magOrder, $orderItemsMap);

            if (!$groupResult['success']) {
                return $this->helper->outputMessage(false, $groupResult['message']);
            }

            $itemsByInvoice = $groupResult['data'];

            if (empty($itemsByInvoice)) {
                return $this->helper->outputMessage(false, 'No refundable items found.');
            }

            // Process credit memos
            $results = $this->processInvoices($itemsByInvoice, $magOrder, $data, $shippingItemId);

            // Check if all refunds were successful
            if ($this->areAllRefundsSuccessful($results)) {
                return $this->helper->outputMessage(true, 'Return processed successfully.');
            }

            return end($results);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create return: ' . $e->getMessage());
            return $this->helper->outputMessage(false, $e->getMessage());
        }
    }

    /**
     * Build map of order items by LS Item ID for faster lookup
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     */
    private function buildOrderItemsMap($order)
    {
        $itemsMap = [];
        foreach ($order->getAllItems() as $item) {
            $lsItemId = $item->getProduct()->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE);
            if ($lsItemId) {
                $itemsMap[$lsItemId] = $item;
            }
        }
        return $itemsMap;
    }

    /**
     * Group return items by invoice
     *
     * @param array $returnLines
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $orderItemsMap
     * @return array
     */
    private function groupItemsByInvoice($returnLines, $order, $orderItemsMap)
    {
        $itemsByInvoice = [];
        $errors         = [];

        foreach ($returnLines as $returnItem) {
            $orderItem = $orderItemsMap[$returnItem['ItemId']] ?? null;

            if (!$orderItem) {
                $error = "Order item not found for ItemId: {$returnItem['ItemId']}";
                $this->logger->warning($error);
                $errors[] = $error;
                continue;
            }

            $invoice = $this->helper->getItemInvoice($order, $returnItem['ItemId'], $returnItem['VariantId']);

            if (!$invoice || !$invoice->canRefund()) {
                $error = "Invoice cannot be refunded for item: {$returnItem['ItemId']}";
                $this->logger->warning($error);
                $errors[] = $error;
                continue;
            }

            $invoiceItem = $this->findInvoiceItem($invoice, $orderItem->getItemId());

            if (!$invoiceItem) {
                $error = "Invoice item not found for order item: {$returnItem['ItemId']}";
                $this->logger->warning($error);
                $errors[] = $error;
                continue;
            }

            $qtyToRefund = min($returnItem['Qty'], $invoiceItem->getQty());

            $qtyAvailableToRefund = $orderItem->getQtyToRefund();
            if ($qtyAvailableToRefund < $qtyToRefund) {
                $error = "Insufficient refundable quantity for item: {$returnItem['ItemId']}. Available: {$qtyAvailableToRefund}, Requested: {$qtyToRefund}";
                $this->logger->warning($error);
                $errors[] = $error;
                continue;
            }

            if ($qtyToRefund <= 0 || $qtyAvailableToRefund <= 0) {
                $error = "No refundable quantity for item: {$returnItem['ItemId']}";
                $this->logger->warning($error);
                $errors[] = $error;
                continue;
            }

            $invoiceId = $invoice->getId();

            if (!isset($itemsByInvoice[$invoiceId])) {
                $itemsByInvoice[$invoiceId] = [
                    'invoice'         => $invoice,
                    'items'           => [],
                    'itemToCredit'    => [],
                    'invoiceItemsMap' => $this->buildInvoiceItemsMap($invoice)
                ];
            }

            // Maintain nested array structure expected by refund method
            $itemsByInvoice[$invoiceId]['items'][] = [
                [
                    'item' => $orderItem,
                    'qty'  => $qtyToRefund
                ]
            ];

            $itemsByInvoice[$invoiceId]['itemToCredit'][$orderItem->getItemId()] = [
                'qty'           => $qtyToRefund,
                'back_to_stock' => false
            ];
        }

        // If all items failed, return error
        if (empty($itemsByInvoice) && !empty($errors)) {
            return [
                'success' => false,
                'message' => implode('; ', $errors),
                'data'    => []
            ];
        }

        return [
            'success' => true,
            'message' => '',
            'data'    => $itemsByInvoice
        ];
    }

    /**
     * Build map of invoice items by order item ID
     *
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @return array
     */
    private function buildInvoiceItemsMap($invoice)
    {
        $itemsMap = [];
        foreach ($invoice->getAllItems() as $item) {
            $itemsMap[$item->getOrderItemId()] = $item;
        }
        return $itemsMap;
    }

    /**
     * Find invoice item by order item ID
     *
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @param int $orderItemId
     * @return \Magento\Sales\Api\Data\InvoiceItemInterface|null
     */
    private function findInvoiceItem($invoice, $orderItemId)
    {
        foreach ($invoice->getAllItems() as $item) {
            if ($item->getOrderItemId() == $orderItemId) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Process credit memos for each invoice
     *
     * @param array $itemsByInvoice
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $data
     * @param int $shippingItemId
     * @return array
     */
    private function processInvoices($itemsByInvoice, $order, $data, $shippingItemId)
    {
        $results = [];

        foreach ($itemsByInvoice as $invoiceData) {
            $creditMemoData = $this->creditMemo->setCreditMemoParameters(
                $order,
                $data['Lines'],
                $shippingItemId
            );

            $creditMemoData['comment_text'] = __(
                'RETURN PROCESSED FROM LS CENTRAL THROUGH WEBHOOK - Return Type: %1, Invoice: %2',
                $data['ReturnType'],
                $invoiceData['invoice']->getIncrementId()
            );
            $creditMemoData['items']        = $invoiceData['itemToCredit'];
            $creditMemoData['do_offline']   = (strcasecmp($data['ReturnType'], 'Online') !== 0) ? 1 : 0;

            // Apply amount adjustment if provided
            if (isset($data['Amount'])) {
                $this->applyAmountAdjustment($creditMemoData, $invoiceData, $data['Amount']);
            }

            // Ensure shipping amount is positive
            if (isset($creditMemoData['shipping_amount'])) {
                $creditMemoData['shipping_amount'] = abs($creditMemoData['shipping_amount']);
            }

            $result = $this->creditMemo->refund(
                $order,
                $invoiceData['items'],
                $creditMemoData,
                $invoiceData['invoice']
            );

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Apply amount adjustment to credit memo
     *
     * @param array &$creditMemoData
     * @param array $invoiceData
     * @param float $lsCentralAmount
     * @return void
     */
    private function applyAmountAdjustment(&$creditMemoData, $invoiceData, $lsCentralAmount)
    {
        $totalItemsRefund = 0;

        foreach ($invoiceData['itemToCredit'] as $orderItemId => $itemData) {
            $invoiceItem = $invoiceData['invoiceItemsMap'][$orderItemId] ?? null;

            if ($invoiceItem && $invoiceItem->getQty() > 0) {
                $pricePerUnit     = $invoiceItem->getSubTotal() / $invoiceItem->getQty();
                $totalItemsRefund += $pricePerUnit * $itemData['qty'];
            }
        }

        $amountDifference = $totalItemsRefund - abs($lsCentralAmount);

        if ($amountDifference > 0) {
            $creditMemoData['adjustment_negative'] = $amountDifference;
        }
    }

    /**
     * Check if all refunds were successful
     *
     * @param array $results
     * @return bool
     */
    private function areAllRefundsSuccessful($results)
    {
        foreach ($results as $result) {
            if (!is_array($result) || !isset($result['data']['success']) || !$result['data']['success']) {
                return false;
            }
        }
        return true;
    }
}
