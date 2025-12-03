<?php

namespace Ls\Webhooks\Model\Order;

use \Ls\Webhooks\Helper\Data;
use \Ls\Core\Model\LSR;
use \Ls\Webhooks\Logger\Logger;
use Magento\Framework\Exception\LocalizedException;

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
     * Create return for order
     *
     * @param array $data
     * @return array[]
     * @throws LocalizedException
     */
    public function returns($data)
    {
        try {
            $magOrder       = $this->helper->getOrderByDocumentId($data['OrderId']);
            $shippingItemId = $this->helper->getShippingItemId();
            $itemsByInvoice = [];

            foreach ($data['Lines'] as $returnItem) {
                $orderItem = null;
                foreach ($magOrder->getAllItems() as $item) {
                    if ($item->getProduct()->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE) == $returnItem['ItemId']) {
                        $orderItem = $item;
                        break;
                    }
                }

                if ($orderItem) {
                    $itemQty   = $returnItem['Qty'];
                    $itemId    = $returnItem['ItemId'];
                    $variantId = $returnItem['VariantId'];

                    $invoice = $this->helper->getItemInvoice($magOrder, $itemId, $variantId);

                    if ($invoice) {
                        $invoiceId = $invoice->getId();

                        if (!isset($itemsByInvoice[$invoiceId])) {
                            $itemsByInvoice[$invoiceId] = [
                                'invoice'          => $invoice,
                                'items'            => [],
                                'itemToCredit'     => [],
                                'totalItemsRefund' => 0
                            ];
                        }

                        $itemsByInvoice[$invoiceId]['items'][] = [
                            [
                                'item' => $orderItem,
                                'qty'  => $itemQty
                            ]
                        ];

                        $itemsByInvoice[$invoiceId]['itemToCredit'][$orderItem->getItemId()] = [
                            'qty'           => $itemQty,
                            'back_to_stock' => false
                        ];

                        $itemsByInvoice[$invoiceId]['totalItemsRefund'] += $invoice->getSubtotalInclTax();
                    }
                }
            }

            $results = [];
            foreach ($itemsByInvoice as $invoiceData) {
                $creditMemoData = $this->creditMemo->setCreditMemoParameters(
                    $magOrder,
                    $data['Lines'],
                    $shippingItemId
                );

                $creditMemoData['comment_text'] = __('RETURN PROCESSED FROM LS CENTRAL THROUGH WEBHOOK - Return Type: %1, Invoice: %2',
                    $data['ReturnType'],
                    $invoiceData['invoice']->getIncrementId()
                );
                $creditMemoData['items']        = $invoiceData['itemToCredit'];

                if (strcasecmp($data['ReturnType'], 'Online') === 0) {
                    $creditMemoData['do_offline'] = 0;
                } else {
                    $creditMemoData['do_offline'] = 1;
                }

                if (isset($data['Amount'])) {
                    $amountDifference = $invoiceData['totalItemsRefund'] - abs($data['Amount']);
                    if ($amountDifference > 0) {
                        $creditMemoData['adjustment_negative'] = $amountDifference;
                    }
                }

                if (isset($creditMemoData['shipping_amount'])) {
                    $creditMemoData['shipping_amount'] = abs($creditMemoData['shipping_amount']);
                }

                $result    = $this->creditMemo->refund($magOrder, $invoiceData['items'], $creditMemoData,
                    $invoiceData['invoice']);
                $results[] = $result;
            }

            $allSuccess = true;
            foreach ($results as $result) {
                if (!is_array($result) || !isset($result['data']['success']) || !$result['data']['success']) {
                    $allSuccess = false;
                    break;
                }
            }

            if ($allSuccess) {
                return $this->helper->outputMessage(true, 'Return processed successfully for all invoices.');
            }

            return end($results);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create return: ' . $e->getMessage());
            return $this->helper->outputMessage(false, $e->getMessage());
        }
    }
}
