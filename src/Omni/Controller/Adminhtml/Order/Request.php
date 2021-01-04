<?php

namespace Ls\Omni\Controller\Adminhtml\Order;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\OrderHelper;
use Ls\Omni\Plugin\Checkout\CustomerData\Cart;
use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Request
 * @package Ls\Omni\Controller\Adminhtml\Order
 */
class Request extends Action
{
    /**
     * @var OrderRepositoryInterface
     */
    public $orderRepository;

    /**
     * @var BasketHelper
     */
    public $basketHelper;


    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var OrderHelper
     */
    public $orderHelper;

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
     * @param BasketHelper $basketHelper
     * @param LoggerInterface $logger
     * @param OrderHelper $orderHelper
     * @param CartRepositoryInterface $cartRepository
     * @param LSR $lsr
     */
    public function __construct(
        Action\Context $context,
        OrderRepositoryInterface $orderRepository,
        BasketHelper $basketHelper,
        LoggerInterface $logger,
        OrderHelper $orderHelper,
        CartRepositoryInterface $cartRepository,
        LSR $lsr
    ) {
        $this->orderRepository = $orderRepository;
        $this->basketHelper    = $basketHelper;
        $this->logger          = $logger;
        $this->orderHelper     = $orderHelper;
        $this->messageManager  = $context->getMessageManager();
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
        }
        if (!$response) {
            $this->logger->critical(__('Something terrible happened while placing order %1', $order->getIncrementId()));
            $this->messageManager->addErrorMessage(__('The service is currently unavailable. Please try again later.'));
        }
        return $resultRedirect;
    }
}
