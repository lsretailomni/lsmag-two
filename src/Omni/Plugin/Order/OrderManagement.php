<?php
declare(strict_types=1);

namespace Ls\Omni\Plugin\Order;

use GuzzleHttp\Exception\GuzzleException;
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
use Magento\Store\Model\ScopeInterface;
use Magento\Backend\Model\Session\Quote as BackendQuoteSession;

/**
 * Class for cancelling the order
 */
class OrderManagement
{
    /**
     * @param LSR $lsr
     * @param OrderHelper $orderHelper
     * @param OrderRepository $orderRepository
     * @param BasketHelper $basketHelper
     * @param BackendQuoteSession $backendQuoteSession
     */
    public function __construct(
        public LSR $lsr,
        public OrderHelper $orderHelper,
        public OrderRepository $orderRepository,
        public BasketHelper $basketHelper,
        public BackendQuoteSession $backendQuoteSession
    ) {
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
     * @throws NoSuchEntityException|GuzzleException
     */
    public function aroundCancel(OrderManagementInterface $subject, $proceed, $id)
    {
        /** @var Order $order */
        $order      = $this->orderRepository->get($id);
        $documentId = $order->getDocumentId();
        $websiteId  = $order->getStore()->getWebsiteId();
        if (!$order->hasInvoices() && empty($this->backendQuoteSession->getOrder()->getId())) {
            /**
             * Adding condition to only process if LSR is enabled.
             */
            if ($this->lsr->isLSR($websiteId, ScopeInterface::SCOPE_WEBSITE)) {
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
