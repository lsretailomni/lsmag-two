<?php
namespace Ls\Core\Cron;

use Ls\Core\Model\LSR;
use Psr\Log\LoggerInterface;
use Magento\Framework\Notification\NotifierInterface as NotifierPool;

class AdminNotificationTask
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var LSR
     */
    protected $lsr;

    /**
     * @var NotifierPool
     */
    protected $notifierPool;

    /**
     * AdminNotificationTask constructor.
     * @param LoggerInterface $logger
     * @param LSR $LSR
     * @param NotifierPool $notifierPool
     */
    public function __construct(
        LoggerInterface $logger,
        LSR $LSR,
        NotifierPool $notifierPool
    )
    {
        $this->logger = $logger;
        $this->lsr = $LSR;
        $this->notifierPool = $notifierPool;
    }

    /**
     * Check the LS Retail Basic Setup Requirements
     */
    public function execute()
    {
        $this->logger->debug("Checking LS Retail Setup");
        if (!$this->lsr->isLSR()) {
            $this->notifierPool->addMajor('Please define the LS Retail Service Base URL and Web Store to proceed.',
                'Please complete the details under Stores > Settings > Configuration > LS Retail Tab');
        }
        $this->logger->debug("Finished Checking LS Retail Setup");
    }
}