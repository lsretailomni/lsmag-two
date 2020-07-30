<?php

namespace Ls\Omni\Observer;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\ResourceModel\Order;
use Psr\Log\LoggerInterface;

/**
 * Class OrderObserver
 * @package Ls\Omni\Observer
 */
class OrderObserver implements ObserverInterface
{
    /**
     * @var BasketHelper
     */
    private $basketHelper;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Order
     */
    private $orderResourceModel;

    /**
     * @var LSR
     */
    private $lsr;

    /**
     * OrderObserver constructor.
     * @param BasketHelper $basketHelper
     * @param OrderHelper $orderHelper
     * @param LoggerInterface $logger
     * @param Order $orderResourceModel
     * @param LSR $LSR
     */
    public function __construct(
        BasketHelper $basketHelper,
        OrderHelper $orderHelper,
        LoggerInterface $logger,
        Order $orderResourceModel,
        LSR $LSR
    ) {
        $this->basketHelper       = $basketHelper;
        $this->orderHelper        = $orderHelper;
        $this->logger             = $logger;
        $this->orderResourceModel = $orderResourceModel;
        $this->lsr                = $LSR;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $check              = false;
        $response           = null;
        $order              = $observer->getEvent()->getData('order');
        $oneListCalculation = $this->basketHelper->getOneListCalculationFromCheckoutSession();
        if (empty($order->getIncrementId())) {
            $orderIds = $observer->getEvent()->getOrderIds();
            $order    = $this->orderHelper->orderRepository->get($orderIds[0]);
        }
        /*
        * Adding condition to only process if LSR is enabled.
        */
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            //checking for Adyen payment gateway
            $adyen_response = $observer->getEvent()->getData('adyen_response');
            if (!empty($adyen_response)) {
                $order->getPayment()->setLastTransId($adyen_response['pspReference']);
                $order->getPayment()->setCcTransId($adyen_response['pspReference']);
                $order->getPayment()->setCcType($adyen_response['paymentMethod']);
                $order->getPayment()->setCcStatus($adyen_response['authResult']);
                $this->orderHelper->orderRepository->save($order);
                $order = $this->orderHelper->orderRepository->get($order->getEntityId());
            }
            if (!empty($order->getIncrementId())) {
                $paymentMethod = $order->getPayment();
                if (!empty($paymentMethod)) {
                    $paymentMethod = $order->getPayment()->getMethodInstance();
                    $transId       = $order->getPayment()->getLastTransId();
                    $check         = $paymentMethod->isOffline();
                }
            }
            if (!empty($oneListCalculation)) {
                if (($check == true || !empty($transId))) {
                    $request  = $this->orderHelper->prepareOrder($order, $oneListCalculation);
                    $response = $this->orderHelper->placeOrder($request);
                    try {
                        if ($response) {
                            $documentId = $response->getResult()->getId();
                            if (!empty($documentId)) {
                                $order->setDocumentId($documentId);
                                $this->basketHelper->setLastDocumentIdInCheckoutSession($documentId);
                            }
                            $oneList = $this->basketHelper->getOneListFromCustomerSession();
                            if ($oneList) {
                                $this->basketHelper->delete($oneList);
                            }
                            $order->addCommentToStatusHistory(__('Order request has been sent to LS Central successfully'));
                            $this->orderResourceModel->save($order);
                        } else {
                            $this->disasterRecoveryHandler($order);
                        }
                    } catch (Exception $e) {
                        $this->logger->error($e->getMessage());
                    }
                }
            }
        } else {
            $this->disasterRecoveryHandler($order);
        }
        $this->basketHelper->unSetRequiredDataFromCustomerAndCheckoutSessions();
        return $this;
    }

    /**
     * @param $order
     */
    public function disasterRecoveryHandler($order)
    {
        $this->logger->critical(__('Something terrible happened while placing order %1', $order->getIncrementId()));
        $order->addCommentToStatusHistory(__('The service is currently unavailable. Please try again later.'));
        try {
            $this->orderResourceModel->save($order);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
        $this->basketHelper->unSetLastDocumentId();
    }
}
