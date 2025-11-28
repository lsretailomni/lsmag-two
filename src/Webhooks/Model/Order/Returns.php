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
            $magOrder     = $this->helper->getOrderByDocumentId($data['OrderId']);
            $items        = [];
            $itemToCredit = [];
            $invoice      = null;

            foreach ($data['Lines'] as $returnItem) {
                $orderItem = null;
                foreach ($magOrder->getAllItems() as $item) {
                    if ($item->getProduct()->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE) == $returnItem['ItemId']) {
                        $orderItem = $item;
                        break;
                    }
                }

                if ($orderItem) {
                    $itemQty    = $returnItem['Qty'];
                    $itemId     = $returnItem['ItemId'];
                    $variantId  = $returnItem['VariantId'];
                    $itemAmount = isset($returnItem['Amount']) ? $returnItem['Amount'] : null;

                    $items[] = [
                        [
                            'item'   => $orderItem,
                            'qty'    => $itemQty,
                            'amount' => $itemAmount
                        ]
                    ];

                    $itemToCredit[$orderItem->getItemId()] = [
                        'qty' => $itemQty
                    ];

                    $invoice = $this->helper->getItemInvoice($magOrder, $itemId, $variantId);

                    if ($itemAmount !== null) {
                        $itemToCredit[$orderItem->getItemId()]['back_to_stock'] = false;
                    }
                }
            }

            $creditMemoData                 = $this->creditMemo->setCreditMemoParameters($magOrder, [], null);
            $creditMemoData['comment_text'] = __('RETURN PROCESSED FROM LS CENTRAL THROUGH WEBHOOK - Return Type: %1',
                $data['ReturnType']);
            $creditMemoData['items']        = $itemToCredit;
            if (strcasecmp($data['ReturnType'], 'Online') === 0) {
                $creditMemoData['do_offline'] = 0;
            }

            $this->logger->info('Creating credit memo for return', [
                'order_id'    => $data['OrderId'],
                'return_type' => $data['ReturnType'],
                'items_count' => count($items),
                'amount'      => $data['Amount'] ?? 'auto'
            ]);

            $result = $this->creditMemo->refund($magOrder, $items, $creditMemoData, $invoice);

            if (is_array($result) && isset($result['success']) && $result['success']) {
                return $this->helper->outputMessage(true, 'Return processed successfully.');
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create return: ' . $e->getMessage());
            return $this->helper->outputMessage(false, $e->getMessage());
        }
    }
}
