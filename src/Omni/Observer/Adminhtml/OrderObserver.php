<?php

namespace Ls\Omni\Observer\Adminhtml;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Customer\Model\Session\Proxy as CustomerProxy;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\ResourceModel\Order;
use Psr\Log\LoggerInterface;

/**
 * Class OrderObserver
 * @package Ls\Omni\Observer\Adminhtml
 */
class OrderObserver implements ObserverInterface
{
    /** @var ContactHelper */
    private $contactHelper;

    /** @var BasketHelper */
    private $basketHelper;

    /** @var OrderHelper */
    private $orderHelper;

    /** @var LoggerInterface */
    private $logger;

    /** @var \Magento\Customer\Model\Session\Proxy $customerSession */
    private $customerSession;

    /** @var Proxy $checkoutSession */
    private $checkoutSession;

    /** @var bool */
    private $watchNextSave = false;

    /** @var Order $orderResourceModel */
    private $orderResourceModel;

    /** @var LSR @var */
    private $lsr;

    /**
     * OrderObserver constructor.
     * @param ContactHelper $contactHelper
     * @param BasketHelper $basketHelper
     * @param OrderHelper $orderHelper
     * @param LoggerInterface $logger
     * @param CustomerProxy $customerSession
     * @param Proxy $checkoutSession
     * @param Order $orderResourceModel
     * @param LSR $LSR
     */

    public function __construct(
        ContactHelper $contactHelper,
        BasketHelper $basketHelper,
        OrderHelper $orderHelper,
        LoggerInterface $logger,
        CustomerProxy $customerSession,
        Proxy $checkoutSession,
        Order $orderResourceModel,
        LSR $LSR
    ) {
        $this->contactHelper      = $contactHelper;
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
     * @throws InvalidEnumException
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws AlreadyExistsException
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getData('order');
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR($order->getStoreId())) {
            $check              = false;
            $oneListCalculation = $this->basketHelper->getOneListCalculation();
            if (!empty($order)) {
                $paymentMethod = $order->getPayment();
                if (!empty($paymentMethod)) {
                    $paymentMethod = $order->getPayment()->getMethodInstance();
                    $transId       = $order->getPayment()->getLastTransId();
                    $check         = $paymentMethod->isOffline();
                }
            }
            if (($check == true || !empty($transId)) && !empty($oneListCalculation)) {
                $request  = $this->orderHelper->prepareOrder($order, $oneListCalculation);
                $response = $this->orderHelper->placeOrder($request);
                try {
                    if ($response) {
                        $documentId = $response->getId();
                        $order->setDocumentId($documentId);
                        $this->orderResourceModel->save($order);
                        $this->checkoutSession->setLastDocumentId($documentId);
                        $this->checkoutSession->unsetData('member_points');
                        if ($this->customerSession->getData(LSR::SESSION_CART_ONELIST)) {
                            $oneList = $this->customerSession->getData(LSR::SESSION_CART_ONELIST);
                            $success = $this->basketHelper->delete($oneList);
                            $this->customerSession->unsetData(LSR::SESSION_CART_ONELIST);
                            // delete checkout session data.
                            $this->basketHelper->unSetOneListCalculation();
                            $this->basketHelper->unsetCouponCode();
                        }
                    } else {
                        $this->logger->error($response);
                        $this->logger->critical(
                            __('Something terrible happened while placing order')
                        );
                    }
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        }
        return $this;
    }
}
