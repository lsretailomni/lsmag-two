<?php

namespace Ls\Omni\Controller\Adminhtml\Order;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Send order to Ls Central admin controller
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
     * @var LSR
     */
    public $lsr;

    /**
     * Request constructor.
     * @param Action\Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param BasketHelper $basketHelper
     * @param LoggerInterface $logger
     * @param OrderHelper $orderHelper
     * @param LSR $lsr
     */
    public function __construct(
        Action\Context $context,
        OrderRepositoryInterface $orderRepository,
        BasketHelper $basketHelper,
        LoggerInterface $logger,
        OrderHelper $orderHelper,
        LSR $lsr
    ) {
        $this->orderRepository = $orderRepository;
        $this->basketHelper    = $basketHelper;
        $this->logger          = $logger;
        $this->orderHelper     = $orderHelper;
        $this->messageManager  = $context->getMessageManager();
        $this->lsr             = $lsr;
        parent::__construct($context);
    }

    /**
     * Send order to Ls Central admin controller execute
     *
     * @return ResponseInterface|Redirect|ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $orderId        = $this->getRequest()->getParam('order_id');
        $response       = null;
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $order = $this->orderRepository->get($orderId);
            $this->basketHelper->setCorrectStoreIdInCheckoutSession($order->getStoreId());
            $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);

            if ($this->lsr->isLSR($order->getStoreId())) {

                $oneListCalculation = $this->basketHelper->formulateCentralOrderRequestFromMagentoOrder($order);

                if (!empty($oneListCalculation)) {
                    $request  = $this->orderHelper->prepareOrder($order, $oneListCalculation);
                    $response = $this->orderHelper->placeOrder($request);

                    if ($response) {
                        if (!empty($response->getResult()->getId())) {
                            $documentId = $response->getResult()->getId();
                            $order->setDocumentId($documentId);
                            $this->orderRepository->save($order);
                        }
                        $this->messageManager->addSuccessMessage(
                            __('Order request has been sent to LS Central successfully')
                        );
                    }
                }
            }
            if (!$response) {
                $this->logger->critical(__(
                    'Something terrible happened while placing order %1',
                    $order->getIncrementId()
                ));
                $this->messageManager->addErrorMessage(
                    __('The service is currently unavailable. Please try again later.')
                );
            }
            $this->basketHelper->unSetRequiredDataFromCustomerAndCheckoutSessions();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect;
    }
}
