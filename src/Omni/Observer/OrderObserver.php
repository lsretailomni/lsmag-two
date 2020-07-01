<?php

namespace Ls\Omni\Observer;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Checkout\Model\Session\Proxy as CheckoutProxy;
use Magento\Customer\Model\Session\Proxy as CustomerProxy;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
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
     * @var CustomerProxy
     */
    private $customerSession;

    /**
     * @var CheckoutProxy
     */
    private $checkoutSession;

    /**
     * @var Order
     */
    private $orderResourceModel;

    /**
     * @var LSR
     */
    private $lsr;

    /**
     * @var State
     */
    private $state;

    /**
     * OrderObserver constructor.
     * @param BasketHelper $basketHelper
     * @param OrderHelper $orderHelper
     * @param LoggerInterface $logger
     * @param CustomerProxy $customerSession
     * @param CheckoutProxy $checkoutSession
     * @param Order $orderResourceModel
     * @param LSR $LSR
     * @param State $state
     */
    public function __construct(
        BasketHelper $basketHelper,
        OrderHelper $orderHelper,
        LoggerInterface $logger,
        CustomerProxy $customerSession,
        CheckoutProxy $checkoutSession,
        Order $orderResourceModel,
        LSR $LSR,
        State $state
    ) {
        $this->basketHelper       = $basketHelper;
        $this->orderHelper        = $orderHelper;
        $this->logger             = $logger;
        $this->customerSession    = $customerSession;
        $this->checkoutSession    = $checkoutSession;
        $this->orderResourceModel = $orderResourceModel;
        $this->lsr                = $LSR;
        $this->state              = $state;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws \Ls\Omni\Exception\InvalidEnumException
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        //only run this at frontend because we have different observer for admin
        if ($this->isAdmin()) {
            return $this;
        }

        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $check = false;
            $order = $observer->getEvent()->getData('order');
            if (empty($order)) {
                $orderIds = $observer->getEvent()->getOrderIds();
                $order    = $this->orderHelper->orderRepository->get($orderIds[0]);
            }
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
            if (!empty($order)) {
                $paymentMethod = $order->getPayment();
                if (!empty($paymentMethod)) {
                    $paymentMethod = $order->getPayment()->getMethodInstance();
                    $transId       = $order->getPayment()->getLastTransId();
                    $check         = $paymentMethod->isOffline();
                }
            }
            if (!empty($this->checkoutSession->getOneListCalculation())) {
                $oneListCalculation = $this->checkoutSession->getOneListCalculation();
            }
            if (($check == true || !empty($transId)) && !empty($oneListCalculation)) {
                $request  = $this->orderHelper->prepareOrder($order, $oneListCalculation);
                $response = $this->orderHelper->placeOrder($request);
                try {
                    if (property_exists($response, 'OrderCreateResult')) {
                        if (!empty($response->getResult()->getId())) {
                            $documentId = $response->getResult()->getId();
                            $order->setDocumentId($documentId);
                            $this->checkoutSession->setLastDocumentId($documentId);
                        }
                        $order->addCommentToStatusHistory(
                            __('Order request has been sent to LS Central successfully')
                        );
                        if ($this->customerSession->getData(LSR::SESSION_CART_ONELIST)) {
                            $oneList = $this->customerSession->getData(LSR::SESSION_CART_ONELIST);
                            $this->basketHelper->delete($oneList);
                        }
                    } else {
                        if ($response) {
                            if (!empty($response->getMessage())) {
                                $this->logger->critical(
                                    __('Something terrible happened while placing order')
                                );
                                $order->addCommentToStatusHistory($response->getMessage());
                            }
                            $this->checkoutSession->unsetData('last_document_id');
                        }
                    }
                    $this->orderResourceModel->save($order);
                    $this->checkoutSession->unsetData('member_points');
                    if ($this->customerSession->getData(LSR::SESSION_CART_ONELIST)) {
                        $this->customerSession->unsetData(LSR::SESSION_CART_ONELIST);
                        // delete checkout session data.
                        $this->basketHelper->unSetOneListCalculation();
                        $this->basketHelper->unsetCouponCode();
                    }
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
            return $this;
        }
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    private function isAdmin()
    {
        $areaCode = $this->state->getAreaCode();
        return $areaCode == Area::AREA_ADMINHTML;
    }
}
