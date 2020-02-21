<?php

namespace Ls\Customer\Controller\Sales\Order;

use Exception;
use Magento\Framework\App\Action;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\AbstractController\OrderLoaderInterface;
use Psr\Log\LoggerInterface;

class Invoice extends \Magento\Sales\Controller\Order\Invoice
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

    /**
     * View constructor.
     * @param Http $request
     * @param Action\Context $context
     * @param OrderLoaderInterface $orderLoader
     * @param PageFactory $resultPageFactory
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Http $request,
        Action\Context $context,
        OrderLoaderInterface $orderLoader,
        PageFactory $resultPageFactory,
        LoggerInterface $logger,
        OrderRepositoryInterface $orderRepository
    ) {
        parent::__construct($context, $orderLoader, $resultPageFactory);
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
            $resultRedirect->setPath('customer/order/invoice/order_id/' . $documentId);
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
