<?php

namespace Ls\Omni\Observer;

use Ls\Omni\Helper\BasketHelper;
use Magento\Framework\Event\ObserverInterface;
use Ls\Omni\Helper\ContactHelper;

class CartObserver implements ObserverInterface
{
    /** @var ContactHelper */
    private $contactHelper;

    /** @var BasketHelper */
    protected $basketHelper;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \Magento\Customer\Model\Session */
    protected $customerSession;

    /** @var \Magento\Checkout\Model\Session */
    protected $checkoutSession;

    /** @var bool */
    protected $watchNextSave = FALSE;

    protected $session;

    /**
     * CartObserver constructor.
     * @param ContactHelper $contactHelper
     * @param BasketHelper $basketHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */

    public function __construct(
        ContactHelper $contactHelper,
        BasketHelper $basketHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        $this->contactHelper = $contactHelper;
        $this->basketHelper = $basketHelper;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     * @throws \Ls\Omni\Exception\InvalidEnumException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->watchNextSave) {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->checkoutSession->getQuote();
            // This will create one list if not created and will return onelist if its already created.
            /** @var \Ls\Omni\Client\Ecommerce\Entity\OneList|null $oneList */
            $oneList = $this->basketHelper->get();

            //TODO if there is any no items, i-e when user only has one item and s/he prefer to remove from cart, then dont calculate basket functionality below.
            // add items from the quote to the oneList and return the updated onelist
            $oneList = $this->basketHelper->setOneListQuote($quote, $oneList);
            // update the onelist to Omni.
            $this->basketHelper->update($oneList);
        }
        return $this;
    }

    public function watchNextSave($value = TRUE)
    {
        $this->watchNextSave = $value;
    }
}
