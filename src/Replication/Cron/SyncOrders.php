<?php

namespace Ls\Replication\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\OrderHelper;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Sales\Model\ResourceModel\Order as OrderResourceModel;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;

/** Syncing order through cron*/
class SyncOrders
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

    /**
     * SyncOrders constructor.
     * @param LSR $lsr
     * @param ReplicationHelper $replicationHelper
     * @param OrderHelper $orderHelper
     * @param BasketHelper $basketHelper
     * @param OrderResourceModel $orderResourceModel
     * @param LoggerInterface $logger
     */
    public function __construct(
        LSR $lsr,
        ReplicationHelper $replicationHelper,
        OrderHelper $orderHelper,
        BasketHelper $basketHelper,
        OrderResourceModel $orderResourceModel,
        LoggerInterface $logger
    ) {
        $this->lsr                = $lsr;
        $this->replicationHelper  = $replicationHelper;
        $this->orderHelper        = $orderHelper;
        $this->basketHelper       = $basketHelper;
        $this->orderResourceModel = $orderResourceModel;
        $this->logger             = $logger;
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

        if (!empty($storeData) && $storeData instanceof StoreInterface) {
            $stores = [$storeData];
        } else {
            /** @var StoreInterface[] $stores */
            $stores = $this->lsr->getAllStores();
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
                                $this->basketHelper->setCorrectStoreIdInCheckoutSession($order->getStoreId());
                                $basketData = $this->basketHelper->formulateCentralOrderRequestFromMagentoOrder($order);

                                if (!empty($basketData)) {
                                    $request            = $this->orderHelper->prepareOrder($order, $basketData);
                                    $response           = $this->orderHelper->placeOrder($request);

                                    if ($response) {
                                        if (!empty($response->getResult()->getId())) {
                                            $documentId = $response->getResult()->getId();
                                            $order->setDocumentId($documentId);
                                            $this->orderResourceModel->save($order);
                                        }
                                        $oneList = $this->basketHelper->getOneListFromCustomerSession();

                                        if ($oneList) {
                                            $this->basketHelper->delete($oneList);
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
                            $this->store->getId()
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
