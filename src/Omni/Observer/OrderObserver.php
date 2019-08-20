<?php
namespace Ls\Omni\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class OrderObserver
 * @package Ls\Omni\Observer
 */
class OrderObserver implements ObserverInterface
{
    /** @var ContactHelper  */
    private $contactHelper;

    /** @var BasketHelper  */
    private $basketHelper;

    /** @var OrderHelper  */
    private $orderHelper;

    /** @var \Psr\Log\LoggerInterface  */
    private $logger;

    /** @var \Magento\Customer\Model\Session\Proxy $customerSession */
    private $customerSession;

    /** @var \Magento\Checkout\Model\Session\Proxy $checkoutSession */
    private $checkoutSession;

    /** @var bool  */
    private $watchNextSave = false;

    /** @var \Magento\Sales\Model\ResourceModel\Order $orderResourceModel */
    private $orderResourceModel;

    /** @var \Ls\Core\Model\LSR @var  */
    private $lsr;

    /**
     * OrderObserver constructor.
     * @param ContactHelper $contactHelper
     * @param BasketHelper $basketHelper
     * @param OrderHelper $orderHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     * @param \Magento\Sales\Model\ResourceModel\Order $orderResourceModel
     * @param LSR $LSR
     */

    public function __construct(
        ContactHelper $contactHelper,
        BasketHelper $basketHelper,
        OrderHelper $orderHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Magento\Sales\Model\ResourceModel\Order $orderResourceModel,
        LSR $LSR
    ) {
        $this->contactHelper = $contactHelper;
        $this->basketHelper = $basketHelper;
        $this->orderHelper = $orderHelper;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->orderResourceModel = $orderResourceModel;
        $this->lsr  =   $LSR;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     * @throws \Ls\Omni\Exception\InvalidEnumException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $order = $observer->getEvent()->getData('order');
            /** @var Entity\Order $oneListCalculation */
            $oneListCalculation = $this->basketHelper->getOneListCalculation();
            $request = $this->orderHelper->prepareOrder($order, $oneListCalculation);
            $response = $this->orderHelper->placeOrder($request);
            try {
                if ($response) {
                    //delete from Omni.
                    $documentId = $response->getId();
                    $order->setDocumentId($documentId);
                    $this->orderResourceModel->save($order);
                    $this->checkoutSession->setLastDocumentId($documentId);
                    $this->checkoutSession->unsetData('member_points');
                    if ($this->customerSession->getData(LSR::SESSION_CART_ONELIST)) {
                        $onelist = $this->customerSession->getData(LSR::SESSION_CART_ONELIST);
                        $success = $this->basketHelper->delete($onelist);
                        $this->customerSession->unsetData(LSR::SESSION_CART_ONELIST);
                        $this->basketHelper->unSetOneListCalculation();
                    }
                } else {
                    // TODO: error handling
                    $this->logger->critical(
                        __('Something terrible happened while placing the order.')
                    );
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
        return $this;
    }
}
