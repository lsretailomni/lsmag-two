<?php

namespace Ls\Omni\Observer;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\ResourceModel\Order;
use Psr\Log\LoggerInterface;

/**
 * This observer is responsible for order integration
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

    /***
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
     * Entry point for the observer
     *
     * @param Observer $observer
     * @return $this
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws InvalidEnumException
     * @throws LocalizedException
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     */
    public function execute(Observer $observer)
    {
        $check              = false;
        $order              = $observer->getEvent()->getData('order');

        $oneListCalculation = $this->basketHelper->getOneListCalculationFromCheckoutSession();
        if (empty($order->getIncrementId())) {
            $orderIds = $observer->getEvent()->getOrderIds();
            $order    = $this->orderHelper->orderRepository->get($orderIds[0]);
        }

        if (!$this->orderHelper->isAllowed($order)) {
            $this->basketHelper->unSetLastDocumentId();
            return $this;
        }

        /*
        * Adding condition to only process if LSR is enabled.
        */
        if ($this->lsr->isLSR(
            $this->lsr->getCurrentStoreId(),
            false,
            $this->lsr->getOrderIntegrationOnFrontend()
        )) {
            if (empty($oneListCalculation) && empty($order->getDocumentId())) {
                $oneListCalculation = $this->basketHelper->formulateCentralOrderRequestFromMagentoOrder($order);
            }
            //checking for Adyen payment gateway
            $adyenResponse = $observer->getEvent()->getData('adyen_response');
            $order         = $this->orderHelper->setAdyenParameters($adyenResponse, $order);
            if (!empty($order->getIncrementId())) {
                $paymentMethod = $order->getPayment();
                if (!empty($paymentMethod)) {
                    $paymentMethod = $order->getPayment()->getMethodInstance();
                    $transId       = $order->getPayment()->getLastTransId();
                    $check         = $paymentMethod->isOffline();
                    if ($paymentMethod->getCode() === 'free') {
                        $check = true;
                    }
                }
            }
            //add condition for free payment method when nothing is required i-e Payment is done through
            // Loyalty Points/Gift card
            if (!empty($oneListCalculation)) {
                if (($check || !empty($transId))) {
                    $request  = $this->orderHelper->prepareOrder($order, $oneListCalculation);
                    $response = $this->orderHelper->placeOrder($request);
                    try {
                        if ($response) {
                            $documentId = $response->getResult()->getId();
                            if (!empty($documentId)) {
                                $order->setDocumentId($documentId);
                                $this->basketHelper->setLastDocumentIdInCheckoutSession($documentId);
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
