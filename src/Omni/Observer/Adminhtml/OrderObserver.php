<?php
declare(strict_types=1);

namespace Ls\Omni\Observer\Adminhtml;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\OrderHelper;
use \Ls\Omni\Model\Sales\AdminOrder\OrderEdit;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\ResourceModel\Order;
use Psr\Log\LoggerInterface;

/**
 * Observer for order creation and update
 */
class OrderObserver implements ObserverInterface
{
    /**
     * @param BasketHelper $basketHelper
     * @param OrderHelper $orderHelper
     * @param LoggerInterface $logger
     * @param Order $orderResourceModel
     * @param LSR $lsr
     * @param ManagerInterface $messageManager
     * @param OrderEdit $orderEdit
     */
    public function __construct(
        public BasketHelper $basketHelper,
        public OrderHelper $orderHelper,
        public LoggerInterface $logger,
        public Order $orderResourceModel,
        public LSR $lsr,
        public ManagerInterface $messageManager,
        public OrderEdit $orderEdit
    ) {
    }

    /**
     * Execute method to perform order creation and updates
     *
     * @param Observer $observer
     * @return $this
     * @throws NoSuchEntityException
     * @throws GuzzleException
     * @throws InvalidEnumException
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getData('order');
        $this->orderHelper->storeManager->setCurrentStore($order->getStoreId());
        $this->orderHelper->checkoutSession->setQuoteId($order->getQuoteId());
        $this->orderHelper->customerSession->setData('customer_id', $order->getCustomerId());
        $oneListCalculation = $this->basketHelper->calculateOneListFromOrder($order);
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
