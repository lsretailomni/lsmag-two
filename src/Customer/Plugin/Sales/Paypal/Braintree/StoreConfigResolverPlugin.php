<?php

namespace Ls\Customer\Plugin\Sales\Paypal\Braintree;

use PayPal\Braintree\Model\StoreConfigResolver;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class StoreConfigResolverPlugin
{
    /**
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        public StoreManagerInterface $storeManager,
        public LoggerInterface $logger,
    ) {
    }

    /**
     * Around plugin to fix NoSuchEntityException when order ID is invalid
     *
     * @param StoreConfigResolver $subject
     * @param \Closure $proceed
     * @return false|int|mixed
     */
    public function aroundGetStoreId(StoreConfigResolver $subject, \Closure $proceed)
    {
        try {
            return $proceed();
        } catch (NoSuchEntityException $e) {
            // If order_id is not a valid Magento order, fall back to current store
            try {
                return (int)$this->storeManager->getStore()->getId();
            } catch (\Exception $e) {
                $this->logger->error('Unable to determine fallback store ID: ' . $e->getMessage());
                return false;
            }
        }
    }
}
