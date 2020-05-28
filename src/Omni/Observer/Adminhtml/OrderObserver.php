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

    /**
     * @var CustomerProxy
     */
    private $customerSession;

    /**
     * @var CheckoutProxy
     */
    private $checkoutSession;

    /** @var Order $orderResourceModel */
    private $orderResourceModel;

    /** @var LSR @var */
    private $lsr;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * OrderObserver constructor.
     * @param BasketHelper $basketHelper
     * @param OrderHelper $orderHelper
     * @param LoggerInterface $logger
     * @param CustomerProxy $customerSession
     * @param CheckoutProxy $checkoutSession
     * @param Order $orderResourceModel
     * @param LSR $LSR
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        BasketHelper $basketHelper,
        OrderHelper $orderHelper,
        LoggerInterface $logger,
        CustomerProxy $customerSession,
        CheckoutProxy $checkoutSession,
        Order $orderResourceModel,
        LSR $LSR,
        ManagerInterface $messageManager
    ) {
        $this->basketHelper       = $basketHelper;
        $this->orderHelper        = $orderHelper;
        $this->logger             = $logger;
        $this->customerSession    = $customerSession;
        $this->checkoutSession    = $checkoutSession;
        $this->orderResourceModel = $orderResourceModel;
        $this->lsr                = $LSR;
        $this->messageManager     = $messageManager;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getData('order');
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR($order->getStoreId())) {
            if (!empty($this->checkoutSession->getOneListCalculation())) {
                $oneListCalculation = $this->checkoutSession->getOneListCalculation();
            }
            if (!empty($oneListCalculation)) {
                try {
                    $request  = $this->orderHelper->prepareOrder($order, $oneListCalculation);
                    $response = $this->orderHelper->placeOrder($request);
                    if (property_exists($response, 'OrderCreateResult')) {
                        if (!empty($response->getResult()->getId())) {
                            $documentId = $response->getResult()->getId();
                            $order->setDocumentId($documentId);
                            $this->checkoutSession->setLastDocumentId($documentId);
                        }
                        $this->messageManager->addSuccessMessage(
                            __('Order request has been sent to LS Central successfully')
                        );
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
                                $this->messageManager->addErrorMessage($response->getMessage());
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
        }
        return $this;
    }
}
