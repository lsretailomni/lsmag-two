<?php

namespace Ls\Omni\Observer;

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
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order;
use Psr\Log\LoggerInterface;

/**
 * Class OrderObserver
 * @package Ls\Omni\Observer
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
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var CartRepositoryInterface
     */
    public $quoteRepository;
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
        LSR $LSR,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->contactHelper      = $contactHelper;
        $this->basketHelper       = $basketHelper;
        $this->orderHelper        = $orderHelper;
        $this->logger             = $logger;
        $this->customerSession    = $customerSession;
        $this->checkoutSession    = $checkoutSession;
        $this->orderResourceModel = $orderResourceModel;
        $this->lsr                = $LSR;
        $this->messageManager = $messageManager;
        $this->quoteRepository = $quoteRepository;
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
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $success            = false;
            $check              = false;
            $order              = $observer->getEvent()->getData('order');
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
            $event = $observer->getEvent()->getName();
            if ($event != "checkout_onepage_controller_success_action" || $check == false) {
                $oneListCalculation = $this->basketHelper->getOneListCalculation();
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
                        $this->messageManager->addSuccessMessage(__('Request has been sent successfully!'));
                        if ($this->customerSession->getData(LSR::SESSION_CART_ONELIST)) {
                            $oneList = $this->customerSession->getData(LSR::SESSION_CART_ONELIST);
                            $success = $this->basketHelper->delete($oneList);
                            $quote     = $this->quoteRepository->get($order->getQuoteId());
                            $quote->setLsOnelistId('')->save();
                            $this->customerSession->unsetData(LSR::SESSION_CART_ONELIST);
                            // delete checkout session data.
                            $this->basketHelper->unSetOneListCalculation();
                            $this->basketHelper->unsetCouponCode();
                        }
                    } else {
                        // TODO: error handling
                        $this->logger->error($response);
                        $this->logger->critical(
                            __('Something terrible happened while placing order')
                        );
                        $this->checkoutSession->unsetData('member_points');
                        if ($this->customerSession->getData(LSR::SESSION_CART_ONELIST)) {
                            $oneList = $this->customerSession->getData(LSR::SESSION_CART_ONELIST);
                            $this->customerSession->unsetData(LSR::SESSION_CART_ONELIST);
                            // delete checkout session data.
                            $this->basketHelper->unSetOneListCalculation();
                            $this->basketHelper->unsetCouponCode();
                        }
                    }
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
            return $this;
        }
    }
}
