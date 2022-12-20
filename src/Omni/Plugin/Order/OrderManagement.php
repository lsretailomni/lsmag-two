<?php

namespace Ls\Omni\Plugin\Order;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

/**
 * Class for cancelling the order
 */
class OrderManagement
{
    /**
     * @var LSR
     */
    private $lsr;
    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var BasketHelper
     */
    private $basketHelper;

    /**
     * OrderManagement constructor.
     * @param LSR $lsr
     * @param OrderHelper $orderHelper
     * @param OrderRepository $orderRepository
     * @param BasketHelper $basketHelper
     */
    public function __construct(
        LSR $lsr,
        OrderHelper $orderHelper,
        OrderRepository $orderRepository,
        BasketHelper $basketHelper
    ) {
        $this->lsr             = $lsr;
        $this->orderHelper     = $orderHelper;
        $this->orderRepository = $orderRepository;
        $this->basketHelper    = $basketHelper;
    }

    /**
     * Around plugin to cancel the order
     *
     * @param OrderManagementInterface $subject
     * @param $proceed
     * @param $id
     * @return mixed
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function aroundCancel(OrderManagementInterface $subject, $proceed, $id)
    {
        /** @var Order $order */
        $order      = $this->orderRepository->get($id);
        $documentId = $order->getDocumentId();
        $websiteId  = $order->getStore()->getWebsiteId();
        if (!$order->hasInvoices()) {
            /**
             * Adding condition to only process if LSR is enabled.
             */
            if ($this->lsr->isLSR($websiteId, 'website')) {
                if (!empty($documentId)) {
                    $this->basketHelper->setCorrectStoreIdInCheckoutSession($order->getStoreId());
                    $webStore = $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_STORE, $websiteId);
                    $response = $this->orderHelper->orderCancel($documentId, $webStore);

                    $this->orderHelper->formulateOrderCancelResponse($response, $order);
                    $this->basketHelper->unSetCorrectStoreId();
                }
            }
        }

        return $proceed($id);
    }
}
