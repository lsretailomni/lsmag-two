<?php

namespace Ls\Omni\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class WatchNextCartSaveObserver
 * @package Ls\Omni\Observer
 */
class WatchNextCartSaveObserver implements ObserverInterface
{
    /** @var CartObserver */
    private $cartObserver;

    public function __construct(CartObserver $cartObserver)
    {
        $this->cartObserver = $cartObserver;
    }

    public function execute(Observer $observer)
    {
        // only tell CartObserver to watch next save to speed up things
        // this observer might get called multiple times during a single page load
        // calling CartObserver::execute every time would lead to multiple Omni calls which leads to high
        // page loading times
        $this->cartObserver->watchNextSave(true);
        return $this;
    }
}
