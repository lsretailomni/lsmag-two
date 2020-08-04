<?php

namespace Ls\Omni\Observer\Adminhtml;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Checkout\Model\Session\Proxy as CheckoutProxy;
use Magento\Customer\Model\Session\Proxy as CustomerProxy;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\ResourceModel\Order;
use Psr\Log\LoggerInterface;

/**
 * Class OrderObserver
 * @package Ls\Omni\Observer\Adminhtml
 */
class OrderObserver implements ObserverInterface
{

    /** @var BasketHelper */
    private $basketHelper;

    /** @var OrderHelper */
    private $orderHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /** @var Order $orderResourceModel */
    private $orderResourceModel;

    /** @var LSR @var */
    private $lsr;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var CustomerProxy
     */
    private $customerSession;

    /**
     * @var CheckoutProxy
     */
    private $checkoutSession;

    /**
     * OrderObserver constructor.
     * @param BasketHelper $basketHelper
     * @param OrderHelper $orderHelper
     * @param LoggerInterface $logger
     * @param Order $orderResourceModel
     * @param LSR $LSR
     * @param ManagerInterface $messageManager
     * @param CustomerProxy $customerSession
     * @param CheckoutProxy $checkoutSession
     */
    public function __construct(
        BasketHelper $basketHelper,
        OrderHelper $orderHelper,
        LoggerInterface $logger,
        Order $orderResourceModel,
        LSR $LSR,
        ManagerInterface $messageManager,
        CustomerProxy $customerSession,
        CheckoutProxy $checkoutSession
    ) {
        $this->basketHelper       = $basketHelper;
        $this->orderHelper        = $orderHelper;
        $this->logger             = $logger;
        $this->orderResourceModel = $orderResourceModel;
        $this->lsr                = $LSR;
        $this->messageManager     = $messageManager;
        $this->customerSession    = $customerSession;
        $this->checkoutSession    = $checkoutSession;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order              = $observer->getEvent()->getData('order');
        $oneListCalculation = $this->basketHelper->getOneListCalculationFromCheckoutSession();
        $response           = null;
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR($order->getStoreId())) {
            try {
                if (!empty($oneListCalculation)) {
                    $request  = $this->orderHelper->prepareOrder($order, $oneListCalculation);
                    $response = $this->orderHelper->placeOrder($request);
                    if ($response) {
                        if (!empty($response->getResult()->getId())) {
                            $documentId = $response->getResult()->getId();
                            $order->setDocumentId($documentId);
                            $this->orderResourceModel->save($order);
                        }
                        $this->messageManager->addSuccessMessage(
                            __('Order request has been sent to LS Central successfully')
                        );
                        $oneList = $this->basketHelper->getOneListFromCustomerSession();
                        if ($oneList) {
                            $this->basketHelper->delete($oneList);
                        }
                    }
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
        if (!$response) {
            $this->logger->critical(__('Something terrible happened while placing order %1', $order->getIncrementId()));
            $this->messageManager->addErrorMessage(__('The service is currently unavailable. Please try again later.'));
        }
        $this->basketHelper->unSetRequiredDataFromCustomerAndCheckoutSessions();
        return $this;
    }
}
