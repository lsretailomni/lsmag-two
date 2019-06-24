<?php

namespace Ls\Omni\Helper;

use Magento\Framework\App\Helper\Context;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Replication\Model\ResourceModel\ReplStore\CollectionFactory;
use \Ls\Omni\Client\Ecommerce\Entity\InventoryResponse;

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
     * @param $storeId
     * @param $parentProductId
     * @param $childProductId
     * @return Entity\ArrayOfInventoryResponse|null
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
        if ($response && !is_array($response->getItemsInStockGetResult()->getInventoryResponse())) {
            return $response->getItemsInStockGetResult();
        }
        return null;
    }

    /**
     * @param $storeId
     * @param $items
     * @return Entity\ArrayOfInventoryResponse|Entity\ItemsInStoreGetResponse|\Ls\Omni\Client\ResponseInterface|null
     */
    public function getAllItemsStockInSingleStore(
        $storeId,
        $items
    ) {
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\ItemsInStoreGet();
        $itemStock = new Entity\ItemsInStoreGet();
        $invertoryRequestParent = new Entity\ArrayOfInventoryRequest();
        $inventoryRequestCollection = [];

        foreach ($items as $item) {
            $inventoryRequest = new Entity\InventoryRequest();
            $inventoryRequest->setItemId($item["parent"]);
            $inventoryRequest->setVariantId($item["child"]);
            $inventoryRequestCollection[] = $inventoryRequest;
        }
        // @codingStandardsIgnoreEnd
        $invertoryRequestParent->setInventoryRequest($inventoryRequestCollection);
        $itemStock->setItems($invertoryRequestParent)->setStoreId($storeId);
        try {
            $response = $request->execute($itemStock);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ?
            $response->getItemsInStoreGetResult() : $response;
    }

    /**
     * @param $simpleProductId
     * @param $parentProductSku
     * @return Entity\ArrayOfStore|Entity\StoresGetbyItemInStockResponse|\Ls\Omni\Client\ResponseInterface|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
        $response = [];
        $items = [];
        // @codingStandardsIgnoreStart
        $request = new Operation\ItemsInStoreGet();
        $itemsInStore = new Entity\ItemsInStoreGet();
        foreach ($variants as $variant) {
            $inventoryReq = new Entity\InventoryRequest();
            $inventoryReq->setItemId($variant['ItemId'])->setVariantId($variant['VariantId']);
            $items[] = $inventoryReq;
        }
        // @codingStandardsIgnoreEnd
        $itemsInStore->setStoreId($storeId);
        $itemsInStore->setItems($items);
        try {
            $response = $request->execute($itemsInStore);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        $inventoryResponseArray = $response ? $response->getItemsInStoreGetResult() : $response;
        if ($inventoryResponseArray && $inventoryResponseArray->getInventoryResponse()) {
            if (!is_array($inventoryResponseArray->getInventoryResponse()) &&
                $inventoryResponseArray->getInventoryResponse() instanceof InventoryResponse) {
                $tmp = [$inventoryResponseArray->getInventoryResponse()];
                $inventoryResponseArray->setInventoryResponse($tmp);
            }
            if (is_array($inventoryResponseArray->getInventoryResponse())) {
                foreach ($inventoryResponseArray->getInventoryResponse() as $inventoryResponse) {
                    $sku = $inventoryResponse->getItemId() . '-' . $inventoryResponse->getVariantId();
                    $variants[$sku]['Quantity'] = $inventoryResponse->getQtyInventory();
                }
            }
        }
        return $variants;
    }
}
