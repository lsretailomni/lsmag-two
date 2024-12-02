<?php

namespace Ls\Omni\Observer\Adminhtml;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\OrderHelper;
use \Ls\Omni\Model\Sales\AdminOrder\OrderEdit;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\ResourceModel\Order;
use Psr\Log\LoggerInterface;

/**
 * Observer for order creation and update
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
     * @var OrderEdit
     */
    private $orderEdit;

    /**
     * @param BasketHelper $basketHelper
     * @param OrderHelper $orderHelper
     * @param LoggerInterface $logger
     * @param Order $orderResourceModel
     * @param LSR $LSR
     * @param ManagerInterface $messageManager
     * @param OrderEdit $orderEdit
     */
    public function __construct(
        BasketHelper $basketHelper,
        OrderHelper $orderHelper,
        LoggerInterface $logger,
        Order $orderResourceModel,
        LSR $LSR,
        ManagerInterface $messageManager,
        OrderEdit $orderEdit
    ) {
        $this->basketHelper       = $basketHelper;
        $this->orderHelper        = $orderHelper;
        $this->logger             = $logger;
        $this->orderResourceModel = $orderResourceModel;
        $this->lsr                = $LSR;
        $this->messageManager     = $messageManager;
        $this->orderEdit          = $orderEdit;
    }

    /**
     * Execute method to perform order creation and updates
     *
     * @param Observer $observer
     * @return $this|void
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getData('order');
        $this->orderHelper->storeManager->setCurrentStore($order->getStoreId());
        $this->orderHelper->checkoutSession->setQuoteId($order->getQuoteId());
        $oneListCalculation = $this->basketHelper->getOneListCalculation();
        $response           = null;
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR($order->getStoreId())) {
            try {
                if (!empty($oneListCalculation)) {
                    if (!empty($order->getRelationParentId()) && $this->lsr->getStoreConfig(
                        LSR::LSR_ORDER_EDIT,
                        $order->getStoreId()
                    )) {
                        $oldOrder = $this->orderHelper->getMagentoOrderGivenEntityId($order->getRelationParentId());
                        if ($oldOrder) {
                            $documentId = $oldOrder->getDocumentId();
                            if ($documentId) {
                                $req      = $this->orderEdit->prepareOrder(
                                    $order,
                                    $oneListCalculation,
                                    $oldOrder,
                                    $documentId
                                );
                                $response = $this->orderEdit->orderEdit($req);
                                if ($response) {
                                    $order->setDocumentId($documentId);
                                    $order->setLsOrderEdit(true);
                                    $isClickCollect = false;
                                    $shippingMethod = $order->getShippingMethod(true);
                                    if ($shippingMethod !== null) {
                                        $carrierCode    = $shippingMethod->getData('carrier_code');
                                        $method         = $shippingMethod->getData('method');
                                        $isClickCollect = $carrierCode == 'clickandcollect';
                                    }
                                    if ($isClickCollect) {
                                        $order->setPickupStore($oldOrder->getPickupStore());
                                    }
                                    $this->orderResourceModel->save($order);
                                    $oldOrder->setDocumentId(null);
                                    $this->orderResourceModel->save($oldOrder);
                                    $this->messageManager->addSuccessMessage(
                                        __('Order edit request has been sent to LS Central successfully')
                                    );
                                }
                            }
                        }
                    } else {
                        $request  = $this->orderHelper->prepareOrder($order, $oneListCalculation);
                        $response = $this->orderHelper->placeOrder($request);
                        if ($response) {
                            if (!empty($response->getResult()->getId())) {
                                $documentId = $response->getResult()->getId();
                                $order->setDocumentId($documentId);
                                $this->orderResourceModel->save($order);
                                $this->messageManager->addSuccessMessage(
                                    __('Order request has been sent to LS Central successfully')
                                );
                            }
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
