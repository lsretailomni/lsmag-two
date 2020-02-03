<?php

namespace Ls\Omni\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session\Proxy;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;

/**
 * All Functionality related to LS Recommend will go here.
 * Class BasketHelper
 * @package Ls\Omni\Helper
 */
class LSRecommend extends AbstractHelper
{

    /** @var \Magento\Checkout\Model\Session\Proxy */
    public $checkoutSession;

    /** @var Proxy */
    public $customerSession;

    /** @var SearchCriteriaBuilder */
    public $searchCriteriaBuilder;

    /** @var  LSR $lsr */
    public $lsr;

    /** @var ProductRepositoryInterface */
    public $productRepository;

    /** @var array */
    public $basketDataResponse;

    /**
     * LSRecommend constructor.
     * @param Context $context
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     * @param Proxy $customerSession
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductRepositoryInterface $productRepository
     * @param LSR $Lsr
     */
    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        Proxy $customerSession,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository,
        LSR $Lsr
    ) {
        parent::__construct($context);
        $this->checkoutSession       = $checkoutSession;
        $this->customerSession       = $customerSession;
        $this->lsr                   = $Lsr;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository     = $productRepository;
    }

    /**
     * @return string
     */

    public function isLsRecommendEnable()
    {
        return $this->lsr->getStoreConfig(LSR::LS_RECOMMEND_ACTIVE);
    }

    /**
     * @return string
     */

    public function isLsRecommendEnableOnProductPage()
    {
        return $this->lsr->getStoreConfig(LSR::LS_RECOMMEND_SHOW_ON_PRODUCT);
    }

    /**
     * @return string
     */
    public function isLsRecommendEnableOnCartPage()
    {
        return $this->lsr->getStoreConfig(LSR::LS_RECOMMEND_SHOW_ON_CART);
    }

    /**
     * @return string
     */
    public function isLsRecommendEnableOnHomePage()
    {
        return $this->lsr->getStoreConfig(LSR::LS_RECOMMEND_SHOW_ON_HOME);
    }

    /**
     * @return string
     */
    public function isLsRecommendEnableOnCheckoutPage()
    {
        return $this->lsr->getStoreConfig(LSR::LS_RECOMMEND_SHOW_ON_CHECKOUT);
    }

    // @codingStandardsIgnoreStart
    public function getProductRecommendationfromOmni($product_ids)
    {

        if (is_null($product_ids) || empty($product_ids) || $product_ids == '') {
            return null;
        }
        $store_Id = $this->lsr->getDefaultWebStore();
        $response = null;
        // @codingStandardsIgnoreStart
        /** @var Operation\RecommendedItemsGet $request */
        $request = new Operation\RecommendedItemsGet();

        /** @var Entity\RecommendedItemsGet $entity */
        $entity = new Entity\RecommendedItemsGet();

        //TODO work with UserID.
        $entity->setItems($product_ids)
            ->setStoreId($store_Id)
            ->setUserId('');

        try {
            $response = $request->execute($entity);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getRecommendedItemsGetResult() : $response;
    }
    // @codingStandardsIgnoreEnd

    /**
     * @param Entity\ArrayOfRecommendedItem $recommendedProducts
     * @return ProductInterface[]|null
     */
    public function parseProductRecommendation(
        Entity\ArrayOfRecommendedItem $recommendedProducts
    ) {
        if ($recommendedProducts instanceof Entity\ArrayOfRecommendedItem) {
            /**
             * now we are sure we will get the correct type of
             * data so that we dont mess up handling LS Recommend errors.
             */
            if (empty($recommendedProducts)) {
                return null;
            }
            return $this->getProductCollection(
                $this->getProductIdsFromLsRecommendObject($recommendedProducts)
            );
        }
        return null;
    }

    /**
     * @param Entity\ArrayOfRecommendedItem $recommendedProducts
     * @return array|string
     */

    public function getProductIdsFromLsRecommendObject(
        Entity\ArrayOfRecommendedItem $recommendedProducts
    ) {

        $productIds = [];
        /** @var  Entity\RecommendedItem $recommendedItem */
        foreach ($recommendedProducts as $recommendedItem) {
            $productIds[] = $recommendedItem->getId();
        }
        return $productIds;
    }

    /**
     * @param $productIds
     * @return ProductInterface[]|null
     */
    public function getProductCollection($productIds)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('sku', $productIds, 'in')
            ->create();

        $products = $this->productRepository->getList($searchCriteria);

        if ($products->getTotalCount() > 0) {
            return $products->getItems();
        }
        return null;
    }

    /**
     * @return null|string
     */
    public function getProductSkusFromQuote()
    {
        /** @var Quote $quote */
        $itemsSkus = null;
        $quote     = $this->checkoutSession->getQuote();
        if ($quote->hasItems()) {
            $quoteItems = $this->checkoutSession->getQuote()->getAllVisibleItems();
            /** @var Item $quoteItem */
            //resetting back to null.
            $itemsSkus = '';
            foreach ($quoteItems as $quoteItem) {
                $skuArray  = explode('-', $quoteItem->getSku());
                $sku       = array_shift($skuArray);
                $itemsSkus .= $sku . ',';
            }
        }
        return $itemsSkus;
    }
}
