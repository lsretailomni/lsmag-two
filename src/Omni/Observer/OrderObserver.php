<?php

namespace Ls\Omni\Observer;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
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
     * @throws AlreadyExistsException
     */
    public function execute(Observer $observer)
    {
        $comment = "";
        $check   = false;
        $order   = $observer->getEvent()->getData('order');
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
                        if (property_exists($response, 'OrderCreateResult')) {
                            if (!empty($response->getResult()->getId())) {
                                $documentId = $response->getResult()->getId();
                                $order->setDocumentId($documentId);
                                $this->basketHelper->setLastDocumentIdInCheckoutSession($documentId);
                            }
                            $comment = __('Order request has been sent to LS Central successfully');
                            $oneList = $this->basketHelper->getOneListFromCustomerSession();
                            if ($oneList) {
                                $this->basketHelper->delete($oneList);
                            }
                        } else {
                            if ($response) {
                                if (!empty($response->getMessage())) {
                                    $comment = $response->getMessage();
                                    $this->logger->critical($comment);
                                }
                                $this->basketHelper->unSetLastDocumentId();
                            }
                        }
                        $order->addCommentToStatusHistory($comment);
                        $this->orderResourceModel->save($order);
                    } catch (Exception $e) {
                        $this->logger->error($e->getMessage());
                    }
                }
            }
        } else {
            $comment = __('The service is currently unavailable. Please try again later.');
            $order->addCommentToStatusHistory($comment);
            $this->orderResourceModel->save($order);
            $this->logger->critical($comment);
            $this->basketHelper->unSetLastDocumentId();
        }
        $this->basketHelper->unSetRequiredDataFromCustomerAndCheckoutSessions();
        return $this;
    }
}
