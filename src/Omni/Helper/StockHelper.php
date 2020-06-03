<?php

namespace Ls\Omni\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\InventoryResponse;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Replication\Model\ResourceModel\ReplStore\CollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Zend_Json;

/**
 * Class StockHelper
 * @package Ls\Omni\Helper
 */
class StockHelper extends AbstractHelper
{
    /**
     * @var ProductRepositoryInterface
     */
    public $productRepository;
    /**
     * @var CollectionFactory
     */
    public $storeCollectionFactory;

    /** @var  LSR $lsr */
    public $lsr;

    /**
     * StockHelper constructor.
     * @param Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param CollectionFactory $storeCollectionFactory
     * @param LSR $lsr
     */
    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        CollectionFactory $storeCollectionFactory,
        LSR $lsr
    ) {
        $this->productRepository      = $productRepository;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->lsr                    = $lsr;
        parent::__construct($context);
    }

    /**
     * @param $storeId
     * @param $parentProductId
     * @param $childProductId
     * @return InventoryResponse[]|null
     */
    public function getItemStockInStore(
        $storeId,
        $parentProductId,
        $childProductId
    ) {
        $response = null;
        // @codingStandardsIgnoreStart
        $request   = new Operation\ItemsInStockGet();
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
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        if (!empty($response) &&
            !empty($response->getItemsInStockGetResult()) &&
            !empty($response->getItemsInStockGetResult()->getInventoryResponse())) {
            return $response->getItemsInStockGetResult()->getInventoryResponse();
        }
        return null;
    }

    /**
     * @param $storeId
     * @param $items
     * @return Entity\ArrayOfInventoryResponse|Entity\ItemsInStoreGetResponse|ResponseInterface|null
     */
    public function getAllItemsStockInSingleStore(
        $storeId,
        $items
    ) {
        $response = null;
        // @codingStandardsIgnoreStart
        $request                    = new Operation\ItemsInStoreGet();
        $itemStock                  = new Entity\ItemsInStoreGet();
        $inventoryRequestParent     = new Entity\ArrayOfInventoryRequest();
        $inventoryRequestCollection = [];

        foreach ($items as $item) {
            $inventoryRequest = new Entity\InventoryRequest();
            $inventoryRequest->setItemId($item['parent']);
            $inventoryRequest->setVariantId($item['child']);
            $inventoryRequestCollection[] = $inventoryRequest;
        }
        // @codingStandardsIgnoreEnd
        $inventoryRequestParent->setInventoryRequest($inventoryRequestCollection);
        $itemStock->setItems($inventoryRequestParent)->setStoreId($storeId);
        try {
            $response = $request->execute($itemStock);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ?
            $response->getItemsInStoreGetResult() : $response;
    }

    /**
     * @param $simpleProductId
     * @param $parentProductSku
     * @return Entity\ArrayOfInventoryResponse|Entity\ItemsInStockGetResponse|ResponseInterface|null
     * @throws NoSuchEntityException
     */
    public function getAllStoresItemInStock($simpleProductId, $parentProductSku)
    {
        $simpleProductSku = '';
        $response         = null;
        // @codingStandardsIgnoreStart
        $request   = new Operation\ItemsInStockGet();
        $itemStock = new Entity\ItemsInStockGet();
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
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ?
            $response->getItemsInStockGetResult() : $response;
    }

    /**
     * @param $storesNavIds
     * @return \Ls\Replication\Model\ResourceModel\ReplStore\Collection
     */
    public function getAllStoresFromReplTable($storesNavIds)
    {
        $stores        = $this->storeCollectionFactory->create()->addFieldToFilter('nav_id', ['in' => $storesNavIds]);
        $displayStores = $this->lsr->getStoreConfig(LSR::SC_CART_DISPLAY_STORES);
        if (!$displayStores) {
            $stores->addFieldToFilter('ClickAndCollect', 1);
        }
        return $stores;
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
        $items    = [];
        // @codingStandardsIgnoreStart
        $request      = new Operation\ItemsInStoreGet();
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
        } catch (Exception $e) {
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
