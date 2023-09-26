<?php

namespace Ls\Core\Cron;

use \Ls\Core\Model\LSR;
use Magento\Framework\Notification\NotifierInterface as NotifierPool;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

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
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * AdminNotificationTask constructor.
     * @param LoggerInterface $logger
     * @param LSR $LSR
     * @param NotifierPool $notifierPool
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        LoggerInterface $logger,
        LSR $LSR,
        NotifierPool $notifierPool,
        StoreManagerInterface $storeManager
    ) {
        $this->logger       = $logger;
        $this->lsr          = $LSR;
        $this->notifierPool = $notifierPool;
        $this->storeManager = $storeManager;
    }

    /**
     * Check the LS Retail Basic Setup Requirements
     */
    public function execute()
    {
        $this->logger->debug('Checking LS Retail Setup');
        $is_lr = false;
        /** @var StoreInterface[] $stores */
        $stores = $this->storeManager->getStores();
        if (!empty($stores)) {
            /** @var StoreInterface $store */
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
        $this->logger->debug('Finished Checking LS Retail Setup');
    }
}
