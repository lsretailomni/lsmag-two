<?php

namespace Ls\Webhooks\Model\Order;

use \Ls\Webhooks\Logger\Logger;
use \Ls\Webhooks\Helper\Data;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;

/**
 * class to issue credit memo
 */
class CreditMemo
{
    /**
     * @var CreditmemoSender
     */
    private $creditMemoSender;

    /**
     * @var CreditmemoLoader
     */
    private $creditMemoLoader;

    /**
     * @var CreditmemoManagementInterface
     */
    private $creditMemoManagement;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param CreditmemoSender $creditMemoSender
     * @param CreditmemoLoader $creditMemoLoader
     * @param CreditmemoManagementInterface $creditMemoManagement
     * @param Data $helper
     * @param Logger $logger
     */
    public function __construct(
        CreditmemoSender $creditMemoSender,
        CreditmemoLoader $creditMemoLoader,
        CreditmemoManagementInterface $creditMemoManagement,
        Data $helper,
        Logger $logger
    ) {
        $this->creditMemoSender     = $creditMemoSender;
        $this->creditMemoLoader     = $creditMemoLoader;
        $this->creditMemoManagement = $creditMemoManagement;
        $this->helper               = $helper;
        $this->logger               = $logger;
    }

    /**
     * To process refund for that item which is cancelled
     *
     * @param $magOrder
     * @param $items
     * @param $creditMemoData
     * @param $invoice
     * @return array[]|void
     */
    public function refund($magOrder, $items, $creditMemoData, $invoice)
    {
        $orderId = $magOrder->getEntityId();
        foreach ($items as $itemData) {
            foreach ($itemData as $itemData) {
                $item                       = $itemData['item'];
                $orderItemId                = $item->getItemId();
                $itemToCredit[$orderItemId] = ['qty' => $itemData['qty']];
                $creditMemoData['items']    = $itemToCredit;
            }
        }

        try {
            $this->creditMemoLoader->setOrderId($orderId);
            $this->creditMemoLoader->setCreditmemo($creditMemoData);

            $creditMemo = $this->creditMemoLoader->load();
            if ($creditMemo) {
                if (!$creditMemo->isValidGrandTotal()) {
                    throw new LocalizedException(
                        __('The credit memo\'s total must be positive.')
                    );
                }
                if (!empty($creditMemoData['comment_text'])) {
                    $creditMemo->addComment(
                        $creditMemoData['comment_text'],
                        isset($creditMemoData['comment_customer_notify']),
                        isset($creditMemoData['is_visible_on_front'])
                    );

                    $creditMemo->setCustomerNote($creditMemoData['comment_text']);
                    $creditMemo->setCustomerNoteNotify(isset($creditMemoData['comment_customer_notify']));
                }

                if ($invoice) {
                    $creditMemo->setInvoice($invoice);
                }

                $creditMemo->getOrder()->setCustomerNoteNotify(!empty($creditMemoData['send_email']));
                $this->creditMemoManagement->refund($creditMemo, (bool)$creditMemoData['do_offline']);

                if (!empty($creditMemoData['send_email'])) {
                    $this->creditMemoSender->send($creditMemo);
                }
                $message = Status::SUCCESS_MESSAGE;
                return $this->helper->outputMessage(true, $message);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->helper->outputMessage(false, $e->getMessage());
        }
    }

    /**
     * Set credit memo parameters
     *
     * @param $magOrder
     * @param $itemsInfo
     * @param $shippingItemId
     * @return array
     */
    public function setCreditMemoParameters($magOrder, $itemsInfo, $shippingItemId)
    {
        $shippingAmount               = 0;
        $creditMemoData               = [];
        $creditMemoData['do_offline'] = 0;
        $isOffline                    = $magOrder->getPayment()->getMethodInstance()->isOffline();
        if ($isOffline) {
            $creditMemoData['do_offline'] = 1;
        }
        foreach ($itemsInfo as $itemLine) {
            if ($itemLine['ItemId'] == $shippingItemId) {
                $shippingAmount = $itemLine['Amount'];
            }
        }
        $creditMemoData['shipping_amount']     = $shippingAmount;
        $creditMemoData['adjustment_positive'] = 0;
        $creditMemoData['adjustment_negative'] = 0;
        $creditMemoData['comment_text']        = __('REFUNDED ITEM(S) FROM LS CENTRAL THROUGH WEBHOOK');
        $creditMemoData['send_email']          = 1;
        return $creditMemoData;
    }
}
