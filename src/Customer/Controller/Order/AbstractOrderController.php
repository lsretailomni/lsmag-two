<?php
declare(strict_types=1);

namespace Ls\Customer\Controller\Order;

use \Ls\Omni\Client\Ecommerce\Entity\Enum\DocumentIdType;
use \Ls\Omni\Client\CentralEcommerce\Operation\GetSelectedSalesDoc_GetSelectedSalesDoc;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\PageFactory;

class AbstractOrderController
{
    /**
     * @param PageFactory $resultPageFactory
     * @param Http $request
     * @param OrderHelper $orderHelper
     * @param ResultFactory $result
     * @param ManagerInterface $messageManager
     * @param ResultFactory $resultFactory
     * @param UrlInterface $url
     */
    public function __construct(
        public PageFactory $resultPageFactory,
        public Http $request,
        public OrderHelper $orderHelper,
        public ResultFactory $result,
        public ManagerInterface $messageManager,
        public ResultFactory $resultFactory,
        public UrlInterface $url
    ) {
    }

    /**
     * Register Values in registry
     *
     * @return ResultInterface|void
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function registerValuesInRegistry()
    {
        if ($this->request->getParam('order_id')) {
            $orderId = $this->request->getParam('order_id');
            $type    = $this->request->getParam('type');

            if (empty($type)) {
                $type = DocumentIdType::ORDER;
            }
            $response = $this->fetchAndSetCurrentOrderInRegistry($orderId, $type);

            if (empty($response) || !$this->orderHelper->isAuthorizedForOrder($response)) {
                $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $redirect->setUrl($this->url->getUrl('sales/order/history'));

                return $redirect;
            }
            $this->setHasReturnSales($response);

            if (is_array($response)) {
                $response = current($response);
            }

            $this->orderHelper->setCurrentMagOrderInRegistry($response);
        } else {
            $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $redirect->setUrl($this->url->getUrl('sales/order/history'));

            return $redirect;
        }
    }

    /**
     * Fetch and set current order in registry
     *
     * @param $orderId
     * @param $type
     * @return GetSelectedSalesDoc_GetSelectedSalesDoc|null
     * @throws InvalidEnumException
     */
    public function fetchAndSetCurrentOrderInRegistry($orderId, $type)
    {
        $response = $this->orderHelper->fetchOrder($orderId, $type);

        if ($response) {
            $this->orderHelper->setOrderInRegistry($response);
        }

        return $response;
    }

    /**
     * Set has return sales
     *
     * @param $order
     * @return void
     */
    public function setHasReturnSales($order)
    {
        $refundExists = false;

        $lscMemberSalesBuffer = is_array($order->getLscMemberSalesBuffer()) ?
            $order->getLscMemberSalesBuffer() :
            [$order->getLscMemberSalesBuffer()];

        foreach ($lscMemberSalesBuffer as $transaction) {
            if (!empty($transaction->getRefundReceiptNo())) {
                $refundExists = true;
                break;
            }
        }

        $this->orderHelper->registerGivenValueInRegistry('has_return_sales', $refundExists);
    }

    /**
     * Get current transaction
     *
     * @return array
     */
    public function getCurrentTransaction()
    {
        $order = $this->orderHelper->getOrder(true);
        $documentId = $this->request->getParam('order_id');
        $requiredTransaction = [];
        $lscMemberSalesBuffer = is_array($order->getLscMemberSalesBuffer()) ?
            $order->getLscMemberSalesBuffer() :
            [$order->getLscMemberSalesBuffer()];

        foreach ($lscMemberSalesBuffer as $transaction) {
            if ($transaction->getDocumentId() == $documentId) {
                $requiredTransaction[] = $transaction;
                break;
            }
        }

        return $requiredTransaction;
    }
}
