<?php

namespace Ls\Omni\Helper;

use Magento\Framework\App\Helper\Context;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Replication\Model\ResourceModel\ReplStore\CollectionFactory;

/**
 * Class StockHelper
 * @package Ls\Omni\Helper
 */
class StockHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    public $productRepository;
    /**
     * @var CollectionFactory
     */
    public $storeCollectionFactory;

    /**
     * StockHelper constructor.
     * @param Context $context
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param CollectionFactory $storeCollectionFactory
     */
    public function __construct(
        Context $context,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        CollectionFactory $storeCollectionFactory
    ) {
        $this->productRepository = $productRepository;
        $this->storeCollectionFactory = $storeCollectionFactory;
        parent::__construct($context);
    }

    /**
     * getItemStockInStore
     * @param $storeId
     * @param $parentProductId
     * @param $childProductId
     * @return \Ls\Omni\Client\Ecommerce\Entity\ArrayOfInventoryResponse|\Ls\Omni\Client\Ecommerce\Entity\ItemsInStockGetResponse|\Ls\Omni\Client\ResponseInterface
     */
    public function getItemStockInStore(
        $storeId,
        $parentProductId,
        $childProductId
    ) {
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\ItemsInStockGet();
        $itemStock = new Entity\ItemsInStockGet();
        // @codingStandardsIgnoreEnd
        if (!empty($parentProductId) && !empty($childProductId)) {
            $itemStock->setItemId($parentProductId)->
            setVariantId($childProductId)->setStoreId($storeId);
        } else {
            $itemStock->setItemId($parentProductId)->setStoreId($storeId);
        }
        try {
            $response = $request->execute($itemStock);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getItemsInStockGetResult() : $response;
    }

    /**
     * getAllStoresItemInStock
     * @param $simpleProductId
     * @param $parentProductSku
     * @return \Ls\Omni\Client\Ecommerce\Entity\ArrayOfInventoryResponse|\Ls\Omni\Client\Ecommerce\Entity\ItemsInStockGetResponse|\Ls\Omni\Client\ResponseInterface
     * @throws null
     */
    public function getAllStoresItemInStock($simpleProductId, $parentProductSku)
    {
        $simpleProductSku = "";
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\StoresGetbyItemInStock();
        $itemStock = new Entity\StoresGetbyItemInStock();
        // @codingStandardsIgnoreEnd
        if (!empty($simpleProductId)) {
            $simpleProductSku = $this->productRepository->
            getById($simpleProductId)->getSku();
            if (strpos($simpleProductSku, '-') !== false) {
                $parentProductSku = explode('-', $simpleProductSku)[0];
                $simpleProductSku = explode('-', $simpleProductSku)[1];
            }
        }
        $itemStock->setItemId($parentProductSku)->
        setVariantId($simpleProductSku);
        try {
            $response = $request->execute($itemStock);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ?
            $response->getStoresGetbyItemInStockResult() : $response;
    }

    /**
     * getAllStoresFromReplTable
     * @param $storesNavIds
     * @return string
     */
    public function getAllStoresFromReplTable($storesNavIds)
    {
        $stores = $this->storeCollectionFactory
            ->create()
            ->addFieldToFilter('ClickAndCollect', 1)
            ->addFieldToFilter('nav_id', ['in' => $storesNavIds])
            ->toArray();
        return \Zend_Json::encode($stores);
    }

    /**
     * @param $storeId
     * @param $variants
     * @return mixed
     */
    public function getItemsInStore(
        $storeId,
        $variants
    ) {
        $response = array();
        // @codingStandardsIgnoreStart
        $request = new Operation\ItemsInStoreGet();
        $itemsInStore = new Entity\ItemsInStoreGet();
        // @codingStandardsIgnoreEnd
        $items = array();
        foreach ($variants as $variant) {
            $inventoryReq = new Entity\InventoryRequest();
            $inventoryReq->setItemId($variant['ItemId'])->setVariantId($variant['VariantId']);
            $items[] = $inventoryReq;
        }
        $itemsInStore->setStoreId($storeId);
        $itemsInStore->setItems($items);
        try {
            $response = $request->execute($itemsInStore);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        $inventoryResponseArray = $response ? $response->getItemsInStoreGetResult() : $response;
        foreach ($inventoryResponseArray as $inventoryResponse) {
            $sku = $inventoryResponse->getItemId() . '-' . $inventoryResponse->getVariantId();
            $variants[$sku]['Quantity'] = $inventoryResponse->getQtyInventory();
        }
        return $variants;
    }
}
