<?php

namespace Ls\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Ls\Omni\Helper\ContactHelper;

/**
 * Class LogoutObserver
 * @package Ls\Customer\Observer
 */
class LogoutObserver implements ObserverInterface
{
    /** @var ContactHelper  */
    private $contactHelper;

    /** @var \Psr\Log\LoggerInterface  */
    private $logger;

    /** @var \Magento\Customer\Model\Session\Proxy  */
    private $customerSession;

    /**
     * LogoutObserver constructor.
     * @param ContactHelper $contactHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     */
    public function __construct(
        ContactHelper $contactHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session\Proxy $customerSession
    ) {
        $this->contactHelper = $contactHelper;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    // @codingStandardsIgnoreStart
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $result = $this->contactHelper->logout();
        if (!$result) {
            $this->logger->debug('Something went wrong while logging out from Omni');
        }
        $this->customerSession->destroy();
        return $this;
    }
    // @codingStandardsIgnoreEnd
}
