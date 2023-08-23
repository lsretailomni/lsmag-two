<?php

namespace Ls\Customer\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

/**
 * Class LogoutObserver
 * @package Ls\Customer\Observer
 */
class LogoutObserver implements ObserverInterface
{
    /** @var ContactHelper */
    private $contactHelper;

    /** @var LoggerInterface */
    private $logger;

    /** @var CustomerSession */
    private $customerSession;

    /** @var LSR @var */
    private $lsr;

    /**
     * LogoutObserver constructor.
     * @param ContactHelper $contactHelper
     * @param LoggerInterface $logger
     * @param CustomerSession $customerSession
     * @param LSR $LSR
     */
    public function __construct(
        ContactHelper $contactHelper,
        LoggerInterface $logger,
        CustomerSession $customerSession,
        LSR $LSR
    ) {
        $this->contactHelper   = $contactHelper;
        $this->logger          = $logger;
        $this->customerSession = $customerSession;
        $this->lsr             = $LSR;
    }

    /**
     * @param Observer $observer
     * @return $this
     */
    // @codingStandardsIgnoreStart
    public function execute(Observer $observer)
    {
        $options = [];
        $this->customerSession->destroy($options);
        return $this;
    }
    // @codingStandardsIgnoreEnd
}
