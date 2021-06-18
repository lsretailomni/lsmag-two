<?php

namespace Ls\Customer\Controller\Order;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Controller being used for cancelling order
 */
class Cancel implements ActionInterface, HttpPostActionInterface
{
    /**
     * @var ManagerInterface
     */
    public $messageManager;

    /**
     * @var Http $request
     */
    public $request;

    /**
     * @var OrderRepositoryInterface
     */
    public $orderRepository;

    /**
     * @var OrderManagementInterface
     */
    public $orderManagement;

    /**
     * @var RedirectFactory
     */
    public $resultRedirectFactory;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @param Http $request
     * @param ManagerInterface $messageManager
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderManagementInterface $orderManagement
     * @param RedirectFactory $redirectFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Http $request,
        ManagerInterface $messageManager,
        OrderRepositoryInterface $orderRepository,
        OrderManagementInterface $orderManagement,
        RedirectFactory $redirectFactory,
        LoggerInterface $logger
    ) {
        $this->messageManager        = $messageManager;
        $this->request               = $request;
        $this->orderRepository       = $orderRepository;
        $this->orderManagement       = $orderManagement;
        $this->resultRedirectFactory = $redirectFactory;
        $this->logger                = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $magentoOrderId = $this->request->getParam('magento_order_id');
        $centralOrderId = $this->request->getParam('central_order_id');
        $idType = $this->request->getParam('id_type');

        if ($magentoOrderId && $centralOrderId && $idType) {
            try {
                $order = $this->orderRepository->get($magentoOrderId);
                $this->orderManagement->cancel($order->getEntityId());
                $this->messageManager->addSuccessMessage(__('You canceled the order.'));
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->logger->error($e->getMessage());
            }

            return $resultRedirect->setPath(
                'customer/order/view',
                ['order_id' => $centralOrderId, 'type' => $idType]
            );
        }

        return $resultRedirect->setPath('customer/account');
    }
}
