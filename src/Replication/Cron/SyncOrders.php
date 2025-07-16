<?php

namespace Ls\Replication\Cron;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\OrderHelper;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Sales\Model\ResourceModel\Order as OrderResourceModel;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/** Syncing order through cron*/
class SyncOrders
{

    /** @var StoreInterface $store */
    public $store;

    /**
     * @param LSR $lsr
     * @param ReplicationHelper $replicationHelper
     * @param OrderHelper $orderHelper
     * @param BasketHelper $basketHelper
     * @param OrderResourceModel $orderResourceModel
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        public LSR $lsr,
        public ReplicationHelper $replicationHelper,
        public OrderHelper $orderHelper,
        public BasketHelper $basketHelper,
        public OrderResourceModel $orderResourceModel,
        public LoggerInterface $logger,
        public StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Execute method that run automatically for cron
     *
     * @param null $storeData
     * @return array
     * @throws NoSuchEntityException|GuzzleException
     */
    public function execute($storeData = null)
    {
        $info = [];

        if (!$this->lsr->isSSM()) {
            if (!empty($storeData) && $storeData instanceof StoreInterface) {
                $stores = [$storeData];
            } else {
                $stores = $this->lsr->getAllStores();
            }
        } else {
            $stores = [$this->lsr->getAdminStore()];
        }

        if (!empty($stores)) {
            foreach ($stores as $store) {
                $this->lsr->setStoreId($store->getId());
                $this->store = $store;

                if ($this->lsr->isLSR($this->store->getId())) {
                    $orders = $this->orderHelper->getOrders($this->store->getId());

                    if (!empty($orders)) {
                        foreach ($orders as $order) {
                            try {
                                $documentId = null;
                                if ($order->getRelationParentId()) {
                                    $oldOrder = $this->orderHelper->getMagentoOrderGivenEntityId(
                                        $order->getRelationParentId()
                                    );
                                    if ($oldOrder) {
                                        $documentId = $oldOrder->getDocumentId();
                                    }
                                }

                                if (empty($documentId)) {
                                    $this->basketHelper->setCorrectStoreIdInCheckoutSession($order->getStoreId());
                                    $basketData = $this->basketHelper->formulateCentralOrderRequestFromMagentoOrder(
                                        $order
                                    );

                                    if (!empty($basketData)) {
                                        $request = $this->orderHelper->prepareOrder($order, $basketData);
                                        $response = $this->orderHelper->placeOrder($request);

                                        if ($response && $response->getResponsecode() == "0000") {
                                            $documentId = $this->orderHelper->getDocumentIdFromResponseBasedOnIndustry(
                                                $response
                                            );
                                            $order->setDocumentId($documentId);
                                            $order->addCommentToStatusHistory(
                                                __('Order request has been sent to LS Central successfully by the cron.')
                                            );
                                            $this->orderResourceModel->save($order);
                                        }
                                    }
                                }
                                $this->basketHelper->unSetRequiredDataFromCustomerAndCheckoutSessions();
                            } catch (\Exception $e) {
                                $this->logger->critical($e->getMessage());
                            }
                        }

                        $this->replicationHelper->updateConfigValue(
                            $this->replicationHelper->getDateTime(),
                            LSR::SC_CRON_SYNC_ORDERS_CONFIG_PATH_LAST_EXECUTE,
                            $this->store->getId(),
                            ScopeInterface::SCOPE_STORES
                        );
                    }
                }
                $this->lsr->setStoreId(null);
            }

            $info[] = -1;

            return $info;
        }
    }

    /**
     * Execute method for running cron manually
     *
     * @param null $storeData
     * @return array
     * @throws NoSuchEntityException|GuzzleException
     */
    public function executeManually($storeData = null)
    {
        return $this->execute($storeData);
    }
}
