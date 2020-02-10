<?php

namespace Ls\Customer\Controller\Sales\Order;

use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\AbstractController\OrderViewAuthorizationInterface;
use Psr\Log\LoggerInterface;

class PrintInvoice extends \Magento\Sales\Controller\Order\PrintInvoice
{

    /**
     * @var Http $request
     */
    public $request;

    /**
     * @var OrderRepositoryInterface
     */
    public $orderRepository;

    /** @var LoggerInterface */
    public $logger;

    public function __construct(
        Http $request,
        Context $context,
        OrderViewAuthorizationInterface $orderAuthorization,
        Registry $registry,
        PageFactory $resultPageFactory,
        LoggerInterface $logger,
        OrderRepositoryInterface $orderRepository

    ) {
        parent::__construct($context, $orderAuthorization, $registry, $resultPageFactory);
        $this->request         = $request;
        $this->logger          = $logger;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        try {
            if ($this->request->getParam('order_id')) {
                $orderId    = $this->request->getParam('order_id');
                $order      = $this->getOrder($orderId);
                $documentId = $order->getDocumentId();
            }
            if (empty($documentId)) {
                return parent::execute();
            }
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('customer/order/printinvoice/order_id/' . $documentId);
            return $resultRedirect;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return parent::execute();
        }
    }

    /**
     * @param $id
     * @return OrderInterface
     */
    public function getOrder($id)
    {
        return $this->orderRepository->get($id);
    }
}
