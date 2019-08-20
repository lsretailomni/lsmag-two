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

    /** @var \Ls\Core\Model\LSR @var  */
    private $lsr;

    /**
     * LogoutObserver constructor.
     * @param ContactHelper $contactHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     */
    public function __construct(
        ContactHelper $contactHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Ls\Core\Model\LSR $LSR
    ) {
        $this->contactHelper = $contactHelper;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->lsr  =   $LSR;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    // @codingStandardsIgnoreStart
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->customerSession->destroy();
        return $this;
    }
    // @codingStandardsIgnoreEnd
}
