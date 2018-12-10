<?php

namespace Ls\Omni\Helper;

use Magento\Framework\App\Helper\Context;
use Ls\Omni\Client\Ecommerce\Entity;
use Ls\Omni\Client\Ecommerce\Operation;
use Ls\Replication\Model\ResourceModel\ReplStore\CollectionFactory;

/**
 * Class StockHelper
 * @package Ls\Omni\Helper
 */
class StockHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;
    /**
     * @var CollectionFactory
     */
    protected $_storeCollectionFactory;

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
        $this->_productRepository = $productRepository;
        $this->_storeCollectionFactory = $storeCollectionFactory;
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
        $request = new Operation\ItemsInStockGet();
        $itemStock = new Entity\ItemsInStockGet();
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
        $request = new Operation\StoresGetbyItemInStock();
        $itemStock = new Entity\StoresGetbyItemInStock();
        if (!empty($simpleProductId)) {
            $simpleProductSku = $this->_productRepository->
            getById($simpleProductId)->getSku();
            if (strpos($simpleProductSku, '-') !== false) {
                $parentProductSku = explode('-', $simpleProductSku)[0];
                $simpleProductSku = explode('-', $simpleProductSku)[1];
                $itemStock->setItemId($parentProductSku)->
                setVariantId($simpleProductSku);
            }
        } else {
            $itemStock->setItemId($parentProductSku);
        }
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

            $stores = $this->_storeCollectionFactory
                ->create()
                ->addFieldToFilter('ClickAndCollect', 1)
                ->addFieldToFilter('nav_id', ['in' => $storesNavIds])
                ->toArray();
            return \Zend_Json::encode($stores);
    }
}
