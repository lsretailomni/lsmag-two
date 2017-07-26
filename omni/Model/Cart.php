<?php
namespace Ls\Omni\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session;

class Cart extends \Magento\Checkout\Model\Cart
{
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\ResourceModel\Cart $resourceCart,
        Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        parent::__construct($eventManager, $scopeConfig, $storeManager, $resourceCart, $checkoutSession,
            $customerSession, $messageManager, $stockRegistry, $stockState, $quoteRepository, $productRepository,
            $data);
    }

    public function aroundSave($subject, callable $proceed) {
        // we can't intercept $quote->collectTotals(), so we need to intercept this::save() to call all other functions
        // and our own collectTotals()
        $this->_eventManager->dispatch('checkout_cart_save_before', ['cart' => $this]);

        $this->getQuote()->getBillingAddress();
        $this->getQuote()->getShippingAddress()->setCollectShippingRates(true);
        $this->getQuote()->collectTotals();
        $this->quoteRepository->save($this->getQuote());
        $this->_checkoutSession->setQuoteId($this->getQuote()->getId());
        /**
         * Cart save usually called after changes with cart items.
         */
        $this->_eventManager->dispatch('checkout_cart_save_after', ['cart' => $this]);
        $this->reinitializeState();
        return $this;
    }
}