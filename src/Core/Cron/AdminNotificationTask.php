<?php

namespace Ls\Core\Cron;

use \Ls\Core\Model\LSR;
use Psr\Log\LoggerInterface;
use Magento\Framework\Notification\NotifierInterface as NotifierPool;

/**
 * Class AdminNotificationTask
 * @package Ls\Core\Cron
 */
class AdminNotificationTask
{
    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var NotifierPool
     */
    public $notifierPool;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    /**
     * AdminNotificationTask constructor.
     * @param LoggerInterface $logger
     * @param LSR $LSR
     * @param NotifierPool $notifierPool
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        LoggerInterface $logger,
        LSR $LSR,
        NotifierPool $notifierPool,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->logger = $logger;
        $this->lsr = $LSR;
        $this->notifierPool = $notifierPool;
        $this->storeManager = $storeManager;
    }

    /**
     * Check the LS Retail Basic Setup Requirements
     */
    public function execute()
    {
        $this->logger->debug("Checking LS Retail Setup");

        /**
         * The Idea is for Multi Store, if any of the store has isLSR setup? then in that case we dont need to thorw this error.
         */

        $is_lr = false;

        /** @var \Magento\Store\Api\Data\StoreInterface[] $stores */
        $stores = $this->storeManager->getStores();
        if (!empty($stores)) {
            /** @var \Magento\Store\Api\Data\StoreInterface $store */
            foreach ($stores as $store) {
                if ($this->lsr->isLSR($store->getId())) {
                    $is_lr = true;
                    break;
                }
            }
        }
        if (!$is_lr) {
            $this->notifierPool->addMajor(
                'Please define the LS Retail Service Base URL and Web Store to proceed.',
                'Please complete the details under Stores > Settings > Configuration > LS Retail Tab'
            );
        }
        $this->logger->debug("Finished Checking LS Retail Setup");
    }
}
