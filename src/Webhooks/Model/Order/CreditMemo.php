<?php

namespace Ls\Webhooks\Model\Order;

use \Ls\Webhooks\Logger\Logger;
use \Ls\Webhooks\Helper\Data;
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
     * @var Data
     */
    private $helper;

    /**
     * CreditMemo constructor.
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
     * @param $magOrder
     * @param $skus
     * @throws \Exception
     */
    public function refund($magOrder, $itemsInfo, $creditMemoData)
    {
        $orderId             = $magOrder->getEntityId();
        $items               = $this->helper->getItems($magOrder, $itemsInfo);
        $itemsTaxAmountTotal = $creditMemoData['adjustment_negative'];
        foreach ($items as $item) {

            $orderItemId                = $item->getItemId();
            $itemToCredit[$orderItemId] = ['qty' => $item->getQtyInvoiced()];
            $creditMemoData['items']    = $itemToCredit;
            $itemsTaxAmountTotal        += $item->getTaxAmount();
        }

        if ($itemsTaxAmountTotal > 0) {
            $creditMemoData['adjustment_negative'] = $itemsTaxAmountTotal;
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
        $creditMemoData['shipping_amount']     = $magOrder->getShippingAmount();
        $creditMemoData['adjustment_positive'] = 0;
        $creditMemoData['adjustment_negative'] = 0;
        $creditMemoData['comment_text']        = 'Refund Item(s) from LS Central';
        $creditMemoData['send_email']          = 1;
        return $creditMemoData;
    }
}
