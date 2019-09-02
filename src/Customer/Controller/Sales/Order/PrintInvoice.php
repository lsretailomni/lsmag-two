<?php

namespace Ls\Customer\Controller\Sales\Order;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Controller\AbstractController\OrderLoaderInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Controller\AbstractController\OrderViewAuthorizationInterface;

class PrintInvoice extends \Magento\Sales\Controller\Order\PrintInvoice
{

    /**
     * @var Http $request
     */
    public $request;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    public $orderRepository;

    /** @var \Psr\Log\LoggerInterface */
    public $logger;

    public function __construct(
        Http $request,
        Context $context,
        OrderViewAuthorizationInterface $orderAuthorization,
        \Magento\Framework\Registry $registry,
        PageFactory $resultPageFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository

    ) {
        parent::__construct($context, $orderAuthorization, $registry, $resultPageFactory);
        $this->request = $request;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            if ($this->request->getParam('order_id')) {
                $orderId = $this->request->getParam('order_id');
                $order = $this->getOrder($orderId);
                $documentId = $order->getDocumentId();
            }
            if (empty($documentId)) {
                return parent::execute();
            }
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('customer/order/printinvoice/order_id/' . $documentId);
            return $resultRedirect;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return parent::execute();
        }
    }

    /**
     * @param $id
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function getOrder($id)
    {
        return $this->orderRepository->get($id);
    }
}
