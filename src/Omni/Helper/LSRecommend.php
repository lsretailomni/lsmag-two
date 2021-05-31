<?php

namespace Ls\Omni\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Omni\Client\ResponseInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session\Proxy;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;

/**
 * All Functionality related to LS Recommend will go here
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

    /** @var ItemHelper $itemHelper */
    private $itemHelper;

    /**
     * @param Context $context
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     * @param Proxy $customerSession
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductRepositoryInterface $productRepository
     * @param LSR $Lsr
     * @param ItemHelper $itemHelper
     */
    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        Proxy $customerSession,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository,
        LSR $Lsr,
        ItemHelper $itemHelper
    ) {
        parent::__construct($context);
        $this->checkoutSession       = $checkoutSession;
        $this->customerSession       = $customerSession;
        $this->lsr                   = $Lsr;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository     = $productRepository;
        $this->itemHelper            = $itemHelper;
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function isLsRecommendEnable()
    {
        return $this->lsr->getStoreConfig(
            LSR::LS_RECOMMEND_ACTIVE,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function isLsRecommendEnableOnProductPage()
    {
        return $this->lsr->getStoreConfig(
            LSR::LS_RECOMMEND_SHOW_ON_PRODUCT,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function isLsRecommendEnableOnCartPage()
    {
        return $this->lsr->getStoreConfig(
            LSR::LS_RECOMMEND_SHOW_ON_CART,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function isLsRecommendEnableOnHomePage()
    {
        return $this->lsr->getStoreConfig(
            LSR::LS_RECOMMEND_SHOW_ON_HOME,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function isLsRecommendEnableOnCheckoutPage()
    {
        return $this->lsr->getStoreConfig(
            LSR::LS_RECOMMEND_SHOW_ON_CHECKOUT,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * @param $product_ids
     * @return Entity\ArrayOfRecommendedItem|Entity\RecommendedItemsGetResponse|ResponseInterface|null
     * @throws NoSuchEntityException
     */
    public function getProductRecommendationFromOmni($product_ids)
    {
        // @codingStandardsIgnoreStart
        if (is_null($product_ids) || empty($product_ids) || $product_ids == '' || !$this->lsr->isLSR($this->lsr->getCurrentStoreId()) ) {
            return null;
        }
        $webStore = $this->lsr->getActiveWebStore();
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\RecommendedItemsGet();

        $entity = new Entity\RecommendedItemsGet();

        //TODO work with UserID.
        $entity->setItems($product_ids)
            ->setStoreId($webStore)
            ->setUserId('');
        try {
            $response = $request->execute($entity);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getRecommendedItemsGetResult() : $response;
        // @codingStandardsIgnoreEnd
    }


    /**
     * @param Entity\ArrayOfRecommendedItem $recommendedProducts
     * @return ProductInterface[]|null
     */
    public function parseProductRecommendation(
        Entity\ArrayOfRecommendedItem $recommendedProducts
    ) {
        if ($recommendedProducts instanceof Entity\ArrayOfRecommendedItem) {
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
     * Get entire collection of product skus in quote
     *
     * @return string|null
     * @throws NoSuchEntityException
     * @throws LocalizedException
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
            $itemsSkusArray = [];

            foreach ($quoteItems as $quoteItem) {
                list($sku) = $this->itemHelper->getComparisonValues($quoteItem);
                $itemsSkus .= $sku . ',';
            }
            $itemsSkus = implode(',', $itemsSkusArray);
        }

        return $itemsSkus;
    }
}
