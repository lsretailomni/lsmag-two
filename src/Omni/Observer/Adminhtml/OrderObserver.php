<?php

namespace Ls\Omni\Observer\Adminhtml;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
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
     * @param Order $orderResourceModel
     * @param LSR $LSR
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        BasketHelper $basketHelper,
        OrderHelper $orderHelper,
        LoggerInterface $logger,
        Order $orderResourceModel,
        LSR $LSR,
        ManagerInterface $messageManager
    ) {
        $this->basketHelper       = $basketHelper;
        $this->orderHelper        = $orderHelper;
        $this->logger             = $logger;
        $this->orderResourceModel = $orderResourceModel;
        $this->lsr                = $LSR;
        $this->messageManager     = $messageManager;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     * @throws NoSuchEntityException
     * @throws AlreadyExistsException
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getData('order');
        $oneListCalculation = $this->basketHelper->getOneListCalculationFromCheckoutSession();
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR($order->getStoreId())) {
            if (!empty($oneListCalculation)) {
                    try {
                        $request  = $this->orderHelper->prepareOrder($order, $oneListCalculation);
                        $response = $this->orderHelper->placeOrder($request);
                        if (property_exists($response, 'OrderCreateResult')) {
                            if (!empty($response->getResult()->getId())) {
                                $documentId = $response->getResult()->getId();
                                $order->setDocumentId($documentId);
                            }
                            $this->messageManager->addSuccessMessage(
                                __('Order request has been sent to LS Central successfully')
                            );
                            $oneList = $this->basketHelper->getOneListFromCustomerSession();
                            if ($oneList) {
                                $this->basketHelper->delete($oneList);
                            }
                        } else {
                            if ($response) {
                                if (!empty($response->getMessage())) {
                                    $this->logger->critical(
                                        __('Something terrible happened while placing order')
                                    );
                                    $this->messageManager->addErrorMessage($response->getMessage());
                                }
                            }
                        }
                        $this->orderResourceModel->save($order);
                    } catch (Exception $e) {
                        $this->logger->error($e->getMessage());
                    }
                }
        } else {
            $comment = __('The service is currently unavailable. Please try again later.');
            $order->addCommentToStatusHistory($comment);
            $this->orderResourceModel->save($order);
            $this->messageManager->addErrorMessage($comment);
            $this->logger->critical($comment);
        }
        $this->basketHelper->unSetRequiredDataFromCustomerAndCheckoutSessions();
        return $this;
    }
}
