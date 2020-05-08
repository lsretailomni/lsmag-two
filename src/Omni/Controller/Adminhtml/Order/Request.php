<?php

namespace Ls\Omni\Controller\Adminhtml\Order;

use Exception;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Backend\App\Action;
use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Class LoadStore
 * @package Ls\Omni\Controller\Adminhtml\Order
 */
class Request extends Action
{
    /**
     * @var OrderRepositoryInterface
     */
    public $orderRepository;

    /**
     * @var CartRepositoryInterface
     */
    public $quoteRepository;

    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * @var ManagerInterface
     */
    public $eventManager;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * Request constructor.
     * @param Action\Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param BasketHelper $basketHelper
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        Action\Context $context,
        OrderRepositoryInterface $orderRepository,
        CartRepositoryInterface $quoteRepository,
        BasketHelper $basketHelper,
        ManagerInterface $eventManager,
        LoggerInterface $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->quoteRepository = $quoteRepository;
        $this->basketHelper    = $basketHelper;
        $this->eventManager    = $eventManager;
        $this->logger          = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        $orderId        = $this->getRequest()->getParam('order_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);
        try {
            /** @var Order $order */
            $order = $this->orderRepository->get($orderId);
            $quote = $this->quoteRepository->get($order->getQuoteId());
            $couponCode = $order->getCouponCode();
            $oneListId = $quote->getLsOnelistId();
            if (!empty($oneListId)) {
                $oneList = $this->basketHelper->get($oneListId);
                if ($oneList) {
                    if (!empty($couponCode)) {
                        $this->basketHelper->couponCode = $couponCode;
                    }
                    $basketData = $this->basketHelper->update($oneList);
                    if ($basketData) {
                        $this->eventManager->dispatch(
                            'sales_order_place_after',
                            ['order' => $order]
                        );
                    }
                }
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $resultRedirect;
    }
}
