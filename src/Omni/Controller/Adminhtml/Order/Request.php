<?php

namespace Ls\Omni\Controller\Adminhtml\Order;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Exception\InvalidEnumException;
use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

/** Class for sending order request */
class Request extends Action
{
    /**
     * @var OrderRepositoryInterface
     */
    public $orderRepository;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var ManagerInterface
     */
    public $messageManager;

    /**
     * @var CartRepositoryInterface
     */
    public $cartRepository;

    /**
     * @var LSR
     */
    private $lsr;

    /**
     * Request constructor.
     * @param Action\Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     * @param CartRepositoryInterface $cartRepository
     * @param LSR $lsr
     */
    public function __construct(
        Action\Context $context,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        CartRepositoryInterface $cartRepository,
        LSR $lsr
    ) {
        $this->messageManager  = $context->getMessageManager();
        $this->orderRepository = $orderRepository;
        $this->logger          = $logger;
        $this->lsr             = $lsr;
        $this->cartRepository  = $cartRepository;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $orderId        = $this->getRequest()->getParam('order_id');
        $order          = $this->orderRepository->get($orderId);
        $response       = null;
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);
        if ($this->lsr->isLSR($order->getStoreId())) {
            try {
                $quote = $this->cartRepository->get($order->getQuoteId());
                $this->_eventManager->dispatch(
                    'sales_model_service_quote_submit_before',
                    [
                        'order' => $order,
                        'quote' => $quote
                    ]
                );
                $this->_eventManager->dispatch('sales_order_place_after', ['order' => $order]);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        } else {
            $this->messageManager->addErrorMessage(__('The service is currently unavailable. Please try again later.'));
        }
        return $resultRedirect;
    }
}
