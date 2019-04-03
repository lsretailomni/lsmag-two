<?php

namespace Ls\Omni\Observer;

use \Ls\Omni\Helper\BasketHelper;
use Magento\Eav\Model\Entity;
use Magento\Framework\Event\ObserverInterface;
use \Ls\Omni\Helper\ContactHelper;

/**
 * Class CartObserver
 * @package Ls\Omni\Observer
 */
class CartObserver implements ObserverInterface
{
    /** @var ContactHelper */
    private $contactHelper;

    /** @var BasketHelper */
    private $basketHelper;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var \Magento\Customer\Model\Session\Proxy  $customerSession */
    private $customerSession;

    /** @var \Magento\Checkout\Model\Session\Proxy $checkoutSession */
    private $checkoutSession;

    /** @var bool */
    private $watchNextSave = false;

    /** @var \Ls\Core\Model\LSR @var  */
    private $lsr;

    /**
     * CartObserver constructor.
     * @param ContactHelper $contactHelper
     * @param BasketHelper $basketHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     */
    public function __construct(
        ContactHelper $contactHelper,
        BasketHelper $basketHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Ls\Core\Model\LSR $LSR
    ) {
        $this->contactHelper = $contactHelper;
        $this->basketHelper = $basketHelper;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->lsr  =   $LSR;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     * @throws \Ls\Omni\Exception\InvalidEnumException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /*
          * Adding condition to only process if LSR is enabled.
          */
        if ($this->lsr->isLSR()) {
            if ($this->watchNextSave) {
                /** @var \Magento\Quote\Model\Quote $quote */
                $quote = $this->checkoutSession->getQuote();
                $couponCode = $this->checkoutSession->getCouponCode();
                // This will create one list if not created and will return onelist if its already created.
                /** @var \Ls\Omni\Client\Ecommerce\Entity\OneList|null $oneList */
                $oneList = $this->basketHelper->get();

                //TODO if there is any no items, i-e when user only has one item and s/he prefer to remove from cart,
                // then dont calculate basket functionality below.
                // add items from the quote to the oneList and return the updated onelist
                $oneList = $this->basketHelper->setOneListQuote($quote, $oneList);
                /** @var \Ls\Omni\Client\Ecommerce\Entity\Order $basketData */
                $basketData = $this->basketHelper->update($oneList);
                $status = $this->basketHelper->setCouponCode($couponCode);
                if (!is_object($status)) {
                    $this->basketHelper->unsetCouponCode('');
                }
                $itemlist = $quote->getAllVisibleItems();
                $counter = 0;
                foreach ($itemlist as $item) {
                    $orderLines = $basketData->getOrderLines()->getOrderLine();
                    if (is_array($orderLines)) {
                        if ($orderLines[$counter]->getDiscountAmount() != "0.00") {
                            $item->setCustomPrice($orderLines[$counter]->getAmount());
                            $item->setDiscountPercent($orderLines[$counter]->getDiscountPercent());
                        }
                        $counter++;
                    }
                }
                $this->checkoutSession->getQuote()->setLsPointsEarn($basketData->getPointsRewarded())->save();
            }
        }
        return $this;
    }

    public function watchNextSave($value = true)
    {
        $this->watchNextSave = $value;
    }
}
