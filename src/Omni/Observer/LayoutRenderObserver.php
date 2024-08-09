<?php

namespace Ls\Omni\Observer;

use \Ls\Core\Model\LSR;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class LayoutRenderObserver implements ObserverInterface
{
    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @param LSR $lsr
     */
    public function __construct(LSR $lsr)
    {
        $this->lsr = $lsr;
    }

    /**
     * Entry point for the observer
     *
     * @param Observer $observer
     * @return Observer
     */
    public function execute(Observer $observer)
    {
        if ($this->lsr->isPushNotificationsEnabled()) {
            $observer->getLayout()->getUpdate()->addHandle('custom_handle');
        }

        return $observer;
    }
}
