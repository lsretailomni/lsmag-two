<?php

namespace Ls\Webhooks\Model\Order;

use \Ls\Webhooks\Logger\Logger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;

/**
 * class to cancel order through webhook
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
     * CreditMemo constructor.
     * @param CreditmemoSender $creditMemoSender
     * @param CreditmemoLoader $creditMemoLoader
     * @param CreditmemoManagementInterface $creditMemoManagement
     * @param Logger $logger
     */
    public function __construct(
        CreditmemoSender $creditMemoSender,
        CreditmemoLoader $creditMemoLoader,
        CreditmemoManagementInterface $creditMemoManagement,
        Logger $logger
    ) {
        $this->creditMemoSender     = $creditMemoSender;
        $this->creditMemoLoader     = $creditMemoLoader;
        $this->creditMemoManagement = $creditMemoManagement;
        $this->logger               = $logger;
    }

    /**
     * To process refund for that item which is cancelled
     * @param $magOrder
     * @param $skus
     * @throws \Exception
     */
    public function refund($magOrder, $skus, $creditMemoData)
    {
        $orderId      = $magOrder->getEntityId();
        $invoiceItems = $magOrder->getInvoiceCollection()->getItems();

        foreach ($invoiceItems as $invoiceItem) {

            if ($invoiceItem->getParentItem()) {
                continue;
            }

            $invoiceSku = $invoiceItem->getSku();
            if (array_key_exists($invoiceSku, $skus)) {
                $orderItemId                = $invoiceItem->getOrderItemId();
                $itemToCredit[$orderItemId] = ['qty' => $skus[$invoiceSku]['qty']];
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

                $creditMemo->getOrder()->setCustomerNoteNotify(!empty($creditMemoData['send_email']));
                $this->creditMemoManagement->refund($creditMemo, (bool)$creditMemoData['do_offline']);

                if (!empty($creditMemoData['send_email'])) {
                    $this->creditMemoSender->send($creditMemo);
                }

            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * set credit memo parameters
     * @param $magOrder
     * @return array
     */
    public function setCreditMemoParameters($magOrder)
    {
        $creditMemoData               = [];
        $creditMemoData['do_offline'] = 0;
        $isOffline                    = $magOrder->getPayment()->getMethodInstance()->isOffline();
        if ($isOffline) {
            $creditMemoData['do_offline'] = 1;
        }
        $creditMemoData['shipping_amount']     = 0;
        $creditMemoData['adjustment_positive'] = 0;
        $creditMemoData['adjustment_negative'] = 0;
        $creditMemoData['comment_text']        = 'Cancelled Item(s) from LS Central';
        $creditMemoData['send_email']          = 1;
        return $creditMemoData;
    }
}
