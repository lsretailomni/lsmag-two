<?php
declare(strict_types=1);

namespace Ls\Omni\Observer;

use Ls\Omni\Helper\VendorSourcingHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Item;
use Psr\Log\LoggerInterface;
use WeakMap;

/**
 * Enforces full online prepayment when the quote contains at least one vendor sourced item
 * (including mixed carts): every offline payment method, Pay at the Store included, is made
 * unavailable. Only payment methods that are enabled and online remain selectable. The free
 * method is kept so zero total orders can still be placed.
 *
 * Must be registered after HidePaymentMethods in each events.xml so its verdict is final.
 */
class EnforcePrepaymentForVendorSourcedItems implements ObserverInterface
{
    private const FREE_METHOD_CODE = 'free';

    /**
     * @var WeakMap|null quote => bool, so the quote is evaluated once per request, not once per method
     */
    private ?WeakMap $resultCache = null;

    /**
     * @param VendorSourcingHelper $vendorSourcingHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly VendorSourcingHelper $vendorSourcingHelper,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Entry point for the observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $method = $observer->getEvent()->getMethodInstance();
            $quote  = $observer->getEvent()->getQuote();

            if (!$method || !$quote instanceof CartInterface
                || $method->getCode() === self::FREE_METHOD_CODE
                || !$method->isOffline()
            ) {
                return;
            }

            if ($this->hasVendorSourcedItem($quote)) {
                $observer->getEvent()->getResult()->setData('is_available', false);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Vendor sourcing prepayment check failed: ' . $e->getMessage());
        }
    }

    /**
     * Check whether any quote item (configurable child and bundle children included) is vendor sourced
     *
     * @param CartInterface $quote
     * @return bool
     */
    private function hasVendorSourcedItem(CartInterface $quote): bool
    {
        $this->resultCache ??= new WeakMap();

        if (isset($this->resultCache[$quote])) {
            return $this->resultCache[$quote];
        }

        $result = false;

        /** @var Item $item */
        foreach ($quote->getAllItems() as $item) {
            $parent = $item->getProduct();

            if (!$parent) {
                continue;
            }

            $simple = $item->getOptionByCode('simple_product');

            if ($this->vendorSourcingHelper->isVendorSourced($parent, $simple ? $simple->getProduct() : null)) {
                $result = true;
                break;
            }
        }

        return $this->resultCache[$quote] = $result;
    }
}
