<?php

namespace Ls\Replication\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\OrderHelper;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Omni\Model\Sales\AdminOrder\OrderEdit;
use Magento\Sales\Model\ResourceModel\Order as OrderResourceModel;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/** Syncing edited order through cron*/
class SyncOrdersEdit
{
    /**
     * @var ReplicationHelper
     */
    public $replicationHelper;

    /**
     * @var LSR
     */
    public $lsr;

    /** @var StoreInterface $store */
    public $store;

    /**
     * @var OrderHelper
     */
    public $orderHelper;

    /**
     * @var BasketHelper
     */
    private $basketHelper;

    /** @var OrderResourceModel */
    private $orderResourceModel;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /** @var StoreManagerInterface */
    public $storeManager;

    /**
     * @var OrderEdit
     */
    public $orderEdit;

    /**
     * @param LSR $lsr
     * @param ReplicationHelper $replicationHelper
     * @param OrderHelper $orderHelper
     * @param BasketHelper $basketHelper
     * @param OrderResourceModel $orderResourceModel
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param OrderEdit $orderEdit
     */
    public function __construct(
        LSR $lsr,
        ReplicationHelper $replicationHelper,
        OrderHelper $orderHelper,
        BasketHelper $basketHelper,
        OrderResourceModel $orderResourceModel,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        OrderEdit $orderEdit
    ) {
        $this->lsr                = $lsr;
        $this->replicationHelper  = $replicationHelper;
        $this->orderHelper        = $orderHelper;
        $this->basketHelper       = $basketHelper;
        $this->orderResourceModel = $orderResourceModel;
        $this->logger             = $logger;
        $this->storeManager       = $storeManager;
        $this->orderEdit          = $orderEdit;
    }

    /**
     * Execute method that run automatically for cron
     *
     * @param null $storeData
     * @return array
     * @throws NoSuchEntityException
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

                if ($this->lsr->isLSR($this->store->getId()) && $this->lsr->getStoreConfig(
                    LSR::LSR_ORDER_EDIT,
                    $this->store->getId()
                )) {
                    $orders = $this->orderHelper->getOrders($this->store->getId(), -1, true, 0, null, true);
                    if (!empty($orders)) {
                        foreach ($orders as $order) {
                            try {
                                $oldOrder = $this->orderHelper->getMagentoOrderGivenEntityId(
                                    $order->getRelationParentId()
                                );
                                if ($oldOrder) {
                                    $documentId = $oldOrder->getDocumentId();
                                    if ($documentId) {
                                        $this->basketHelper->setCorrectStoreIdInCheckoutSession($order->getStoreId());
                                        $basketData = $this->basketHelper->formulateCentralOrderRequestFromMagentoOrder(
                                            $order
                                        );
                                        $req      = $this->orderEdit->prepareOrder(
                                            $order,
                                            $basketData,
                                            $oldOrder,
                                            $documentId
                                        );
                                        $response = $this->orderEdit->orderEdit($req);
                                        $order->setDocumentId($documentId);
                                        $order->setLsOrderEdit(true);
                                        $isClickCollect = false;
                                        $shippingMethod = $order->getShippingMethod(true);
                                        if ($shippingMethod !== null) {
                                            $carrierCode    = $shippingMethod->getData('carrier_code');
                                            $method         = $shippingMethod->getData('method');
                                            $isClickCollect = $carrierCode == 'clickandcollect';
                                        }
                                        if ($isClickCollect) {
                                            $order->setPickupStore($oldOrder->getPickupStore());
                                        }
                                        $this->orderResourceModel->save($order);
                                        $oldOrder->setDocumentId(null);
                                        $this->orderResourceModel->save($oldOrder);
                                        $this->basketHelper->unSetRequiredDataFromCustomerAndCheckoutSessions();
                                    }
                                }
                            } catch (\Exception $e) {
                                $this->logger->critical($e->getMessage());
                            }
                        }

                        $this->replicationHelper->updateConfigValue(
                            $this->replicationHelper->getDateTime(),
                            LSR::SC_CRON_SYNC_ORDERS_EDIT_CONFIG_PATH_LAST_EXECUTE,
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
     * @throws NoSuchEntityException
     */
    public function executeManually($storeData = null)
    {
        return $this->execute($storeData);
    }
}
