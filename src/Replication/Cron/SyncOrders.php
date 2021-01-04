<?php

namespace Ls\Replication\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\OneList;
use \Ls\Omni\Client\Ecommerce\Entity\Order;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Sales\Model\ResourceModel\Order as OrderResourceModel;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;

/**
 * Class SyncOrders
 * @package Ls\Replication\Cron
 */
class SyncOrders
{

    /**
     * @var ReplicationHelper
     */
    public $replicationHelper;

    /**
     * @var Data
     */
    public $helper;

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

    /** @var OrderResourceModel $orderResourceModel */
    private $orderResourceModel;

    /**
     * @var ItemHelper
     */
    private $itemHelper;

    /**
     * @var CartRepositoryInterface
     */
    public $cartRepository;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * SyncOrders constructor.
     * @param LSR $lsr
     * @param Data $helper
     * @param ReplicationHelper $replicationHelper
     * @param OrderHelper $orderHelper
     * @param BasketHelper $basketHelper
     * @param ItemHelper $itemHelper
     * @param OrderResourceModel $orderResourceModel
     * @param CartRepositoryInterface $cartRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        LSR $lsr,
        Data $helper,
        ReplicationHelper $replicationHelper,
        OrderHelper $orderHelper,
        BasketHelper $basketHelper,
        ItemHelper $itemHelper,
        OrderResourceModel $orderResourceModel,
        CartRepositoryInterface $cartRepository,
        LoggerInterface $logger
    ) {

        $this->lsr                = $lsr;
        $this->helper             = $helper;
        $this->replicationHelper  = $replicationHelper;
        $this->orderHelper        = $orderHelper;
        $this->basketHelper       = $basketHelper;
        $this->itemHelper         = $itemHelper;
        $this->orderResourceModel = $orderResourceModel;
        $this->cartRepository     = $cartRepository;
        $this->logger             = $logger;
    }

    /**
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
                                $quote      = $this->cartRepository->get($order->getQuoteId());
                                $couponCode = $quote->getCouponCode();
                                /** @var OneList|null $oneList */
                                $oneList = $this->basketHelper->getOneListAdmin(
                                    $quote->getCustomerEmail(),
                                    $quote->getStore()->getWebsiteId()
                                );
                                $oneList = $this->basketHelper->setOneListQuote($quote, $oneList);
                                if (!empty($couponCode)) {
                                    $status = $this->basketHelper->setCouponCode($couponCode);
                                    if (!is_object($status)) {
                                        $quote->setCouponCode('');
                                    }
                                }
                                /** @var Order $basketData */
                                $basketData = $this->basketHelper->update($oneList);
                                $this->itemHelper->setDiscountedPricesForItems($quote, $basketData);
                                if (!empty($basketData)) {
                                    $quote->setLsPointsEarn($basketData->getPointsRewarded())->save();
                                }
                                if ($quote->getLsGiftCardAmountUsed() > 0 ||
                                    $quote->getLsPointsSpent() > 0) {
                                    $this->data->orderBalanceCheck(
                                        $quote->getLsGiftCardNo(),
                                        $quote->getLsGiftCardAmountUsed(),
                                        $quote->getLsPointsSpent(),
                                        $basketData
                                    );
                                }
                                $oneListCalculation = $this->basketHelper->getOneListCalculationFromCheckoutSession();
                                $request            = $this->orderHelper->prepareOrder($order, $oneListCalculation);
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
     * @param null $storeData
     * @return array
     * @throws NoSuchEntityException
     */
    public function executeManually($storeData = null)
    {
        $info = $this->execute($storeData);
        return $info;
    }
}
