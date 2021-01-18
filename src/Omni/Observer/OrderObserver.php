<?php

namespace Ls\Omni\Observer;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Checkout\Model\Session\Proxy as CheckoutProxy;
use Magento\Customer\Model\Session\Proxy as CustomerProxy;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\ResourceModel\Order;
use Psr\Log\LoggerInterface;

/** Class for order process*/
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
     * @var CustomerProxy
     */
    private $customerSession;

    /**
     * @var CheckoutProxy
     */
    private $checkoutSession;

    /***
     * OrderObserver constructor.
     * @param BasketHelper $basketHelper
     * @param OrderHelper $orderHelper
     * @param LoggerInterface $logger
     * @param Order $orderResourceModel
     * @param LSR $LSR
     * @param CustomerProxy $customerSession
     * @param CheckoutProxy $checkoutSession
     */
    public function __construct(
        BasketHelper $basketHelper,
        OrderHelper $orderHelper,
        LoggerInterface $logger,
        CustomerProxy $customerSession,
        CheckoutProxy $checkoutSession,
        Order $orderResourceModel,
        LSR $LSR
    ) {
        $this->basketHelper       = $basketHelper;
        $this->orderHelper        = $orderHelper;
        $this->logger             = $logger;
        $this->customerSession    = $customerSession;
        $this->checkoutSession    = $checkoutSession;
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
                if (isset($adyen_response['pspReference'])) {
                    $order->getPayment()->setLastTransId($adyen_response['pspReference']);
                    $order->getPayment()->setCcTransId($adyen_response['pspReference']);
                }
                if (isset($adyen_response['paymentMethod'])) {
                    $order->getPayment()->setCcType($adyen_response['paymentMethod']);
                }
                if (isset($adyen_response['authResult'])) {
                    $order->getPayment()->setCcStatus($adyen_response['authResult']);
                }
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
            if (0) {
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
                            $order->addCommentToStatusHistory(
                                __('Order request has been sent to LS Central successfully')
                            );
                            $this->orderResourceModel->save($order);
                        } else {
                            $this->orderHelper->disasterRecoveryHandler($order);
                        }
                    } catch (Exception $e) {
                        $this->logger->error($e->getMessage());
                    }
                    $this->basketHelper->unSetRequiredDataFromCustomerAndCheckoutSessions();
                }
            }
        } else {
            $this->orderHelper->disasterRecoveryHandler($order);
        }
        return $this;
    }
}
