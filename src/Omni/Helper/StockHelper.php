<?php

namespace Ls\Omni\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\InventoryResponse;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Replication\Model\ResourceModel\ReplStore\Collection;
use \Ls\Replication\Model\ResourceModel\ReplStore\CollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Item;

/**
 * Stock related operation helper
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
     * @var ItemHelper
     */
    public $itemHelper;

    /**
     * StockHelper constructor.
     *
     * @param Context                    $context
     * @param ProductRepositoryInterface $productRepository
     * @param CollectionFactory          $storeCollectionFactory
     * @param LSR                        $lsr
     * @param ItemHelper                 $itemHelper
     */
    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        CollectionFactory $storeCollectionFactory,
        LSR $lsr,
        ItemHelper $itemHelper
    ) {
        $this->productRepository      = $productRepository;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->lsr                    = $lsr;
        $this->itemHelper             = $itemHelper;
        parent::__construct($context);
    }

    /**
     * Getting items stock in store
     *
     * @param $storeId
     * @param $parentProductId
     * @param $childProductId
     * @return InventoryResponse[]|null
     */
    public function getItemStockInStore($storeId, $parentProductId, $childProductId)
    {
        if ($this->checkVersion()) {
            $items[] = ['parent' => $parentProductId, 'child' => $childProductId];
            return $this->getItemsStockInStoreFromSourcingLocation($storeId, $items);
        }

        $response  = null;
        $request   = new Operation\ItemsInStockGet();
        $itemStock = new Entity\ItemsInStockGet();
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
     * This function is overriding in hospitality module
     *
     * Get stock for all the given items in given store
     *
     * @param $items
     * @param $storeId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getGivenItemsStockInGivenStore($items, $storeId = '')
    {
        $stockCollection = [];

        foreach ($items as &$item) {
            $itemQty = $item->getQty();
            list($parentProductSku, $childProductSku, , , $uomQty) = $this->itemHelper->getComparisonValues(
                $item->getProductId(),
                $item->getSku()
            );

            if (!empty($uomQty)) {
                $itemQty = $itemQty * $uomQty;
            }
            $sku = $item->getSku();

            $stockCollection[] = ['sku' => $sku, 'name' => $item->getName(), 'qty' => $itemQty];

            $item = ['parent' => $parentProductSku, 'child' => $childProductSku];
        }

        return [$this->getAllItemsStockInSingleStore($storeId, $items), $stockCollection];
    }

    /**
     * This function is overriding in hospitality module
     *
     * For sourcing location of inventory
     *
     * @param $storeId
     * @param $items
     * @return InventoryResponse[]|null
     */
    public function getItemsStockInStoreFromSourcingLocation($storeId, $items)
    {
        $response  = null;
        $request   = new Operation\ItemsInStoreGetEx();
        $itemStock = new Entity\ItemsInStoreGetEx();
        $itemStock->setStoreId($storeId);
        $itemStock->setUseSourcingLocation(true);
        $itemStock->setLocationId('');
        foreach ($items as $item) {
            $inventoryRequest = new Entity\InventoryRequest();
            $parentProductId  = reset($item);
            $childProductId   = end($item);
            if (!empty($parentProductId) && !empty($childProductId)) {
                $inventoryRequest->setItemId($parentProductId)->setVariantId($childProductId);
            } else {
                $inventoryRequest->setItemId($parentProductId);
            }
            $inventoryRequestCollection[] = $inventoryRequest;
        }

        $inventoryRequestArray = new Entity\ArrayOfInventoryRequest();
        $inventoryRequestArray->setInventoryRequest($inventoryRequestCollection);
        $itemStock->setItems($inventoryRequestArray);
        try {
            $response = $request->execute($itemStock);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        if (!empty($response) &&
            !empty($response->getItemsInStoreGetExResult()) &&
            !empty($response->getItemsInStoreGetExResult()->getInventoryResponse())) {
            return $response->getItemsInStoreGetExResult()->getInventoryResponse();
        }

        return null;
    }

    /**
     * Get items stock in store
     *
     * @param $storeId
     * @param $items
     * @return Entity\ArrayOfInventoryResponse|Entity\ItemsInStoreGetResponse|ResponseInterface|null|InventoryResponse[]
     * @throws NoSuchEntityException
     */
    public function getAllItemsStockInSingleStore($storeId, $items)
    {
        if ($this->checkVersion()) {
            return $this->getItemsStockInStoreFromSourcingLocation($storeId, $items);
        }
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
     * Call ItemsInStockGet method to check Items in stock or not
     *
     * @param $simpleProductId
     * @param $parentProductSku
     * @return Entity\ArrayOfInventoryResponse|Entity\ItemsInStockGetResponse|ResponseInterface|null|InventoryResponse[]
     * @throws NoSuchEntityException
     */
    public function getAllStoresItemInStock($simpleProductId, $parentProductSku)
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {

            $simpleProductSku = '';

            if (!empty($simpleProductId)) {
                $simpleProductSku = $this->productRepository->getById($simpleProductId)
                    ->getData(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE);
            }

            if ($this->checkVersion()) {
                $items[] = ['parent' => $parentProductSku, 'child' => $simpleProductSku];
                return $this->getItemsStockInStoreFromSourcingLocation('', $items);
            }

            $response = null;
            // @codingStandardsIgnoreStart
            $request   = new Operation\ItemsInStockGet();
            $itemStock = new Entity\ItemsInStockGet();
            // @codingStandardsIgnoreEnd

            $itemStock->setItemId($parentProductSku)->setVariantId($simpleProductSku);
            try {
                $response = $request->execute($itemStock);
            } catch (Exception $e) {
                $this->_logger->error($e->getMessage());
            }

            return $response ?
                $response->getItemsInStockGetResult() : $response;
        } else {
            return null;
        }
    }

    /**
     * Get given stores information from repl store table
     *
     * @param $storesNavIds
     * @return Collection
     * @throws NoSuchEntityException
     */
    public function getAllStoresFromReplTable($storesNavIds)
    {
        $stores        = $this->storeCollectionFactory->create()
            ->addFieldToFilter('nav_id', ['in' => $storesNavIds])
            ->addFieldToFilter('scope_id', ['eq' => $this->lsr->getCurrentStoreId()]);
        $displayStores = $this->lsr->getStoreConfig(LSR::SC_CART_DISPLAY_STORES);

        if (!$displayStores) {
            $stores->addFieldToFilter('ClickAndCollect', 1);
        }

        return $stores;
    }

    /**
     * Fetch all stores where given item is in stock and get all store data from stores repl table
     *
     * @param $simpleProductId
     * @param $productSku
     * @return Collection
     * @throws NoSuchEntityException
     */
    public function fetchAllStoresItemInStockPlusApplyJoin($simpleProductId, $productSku)
    {
        $storesNavId     = [];
        $response = $this->getAllStoresItemInStock(
            $simpleProductId,
            $productSku
        );

        if ($response !== null) {
            if (!is_array($response)) {
                $response = $response->getInventoryResponse();
            }

            foreach ($response as $each) {
                if ($each->getQtyInventory() > 0) {
                    $storesNavId[] = $each->getStoreId();
                }
            }

        }

        return $this->getAllStoresFromReplTable(
            $storesNavId
        );
    }

    /**
     * Get Items in store
     *
     * @param $storeId
     * @param $variants
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getItemsInStore($storeId, $variants)
    {
        if ($this->checkVersion()) {
            return $this->getItemsStockInStoreFromSourcingLocation($storeId, $variants);
        }

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
                    $sku                        = $inventoryResponse->getItemId() . '-' .
                        $inventoryResponse->getVariantId();
                    $variants[$sku]['Quantity'] = $inventoryResponse->getQtyInventory();
                }
            }
        }
        return $variants;
    }

    /**
     * Validate quantities
     *
     * @param $qty
     * @param Item $item
     * @param null $quote
     * @param bool $isRemoveItem
     * @param bool $throwException
     * @return Item
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function validateQty(
        $qty,
        Item $item,
        $quote = null,
        bool $isRemoveItem = false,
        bool $throwException = false
    ) {
        if ($this->lsr->inventoryLookupBeforeAddToCartEnabled()) {
            if (!$item->getHasError()) {
                $storeId = $this->lsr->getActiveWebStore();
                $uomQty  = $item->getProduct()->getData(LSR::LS_UOM_ATTRIBUTE_QTY);

                if (!empty($uomQty)) {
                    $qty = $qty * $uomQty;
                }
                [$parentProductSku, $childProductSku] = $this->itemHelper->getComparisonValues(
                    $item->getProductId(),
                    $item->getSku()
                );

                $stock = $this->getItemStockInStore(
                    $storeId,
                    $parentProductSku,
                    $childProductSku
                );

                if ($stock) {
                    $itemStock = reset($stock);

                    if ($itemStock->getQtyInventory() <= 0) {
                        if ($isRemoveItem == true) {
                            $this->deleteItemFromQuote($item, $quote);
                        }
                        $item->setHasError(true);
                        $item->setMessage(__(
                            'Product %1 is not available.',
                            $item->getName()
                        ));
                        if ($throwException == true) {
                            throw new LocalizedException(__(
                                'Product %1 is not available.',
                                $item->getName()
                            ));
                        }
                    } elseif ($itemStock->getQtyInventory() < $qty) {
                        if ($isRemoveItem == true) {
                            $this->deleteItemFromQuote($item, $quote);
                        }
                        $item->setHasError(true);
                        $item->setMessage(__(
                            'Max quantity available for item %2 is %1',
                            $itemStock->getQtyInventory(),
                            $item->getName()
                        ));
                        if ($throwException == true) {
                            throw new LocalizedException(__(
                                'Max quantity available for item %2 is %1',
                                $itemStock->getQtyInventory(),
                                $item->getName()
                            ));
                        }
                    }
                }
            }
        }

        return $item;
    }

    /**
     * Update Stock Collection
     *
     * @param $response
     * @param $stockCollection
     * @return mixed
     */
    public function updateStockCollection($response, $stockCollection)
    {
        foreach ($response as $item) {
            $actualQty = ceil($item->getQtyInventory());
            $sku       = $item->getItemId() .
                (($item->getVariantId()) ? '-' . $item->getVariantId() : '');

            foreach ($stockCollection as &$values) {
                if (strpos($values['sku'], $sku) === 0) {
                    if ($actualQty > 0) {
                        $values['status']  = '1';
                        $values['display'] = __('This item is available');

                        if ($values['qty'] > $actualQty) {
                            $values['status']  = '0';
                            $values['display'] = __(
                                'You have selected %1 quantity for this item.
                                 We only have %2 quantity available in stock for this store.
                                 Please update this item quantity in cart.',
                                $values['qty'],
                                $actualQty
                            );
                        }
                    } else {
                        $values['status']  = '0';
                        $values['display'] = __('This item is not available');
                    }
                }
            }
        }

        return $stockCollection;
    }

    /**
     * Delete an item from quote
     *
     * @param $item
     * @param $quote
     */
    public function deleteItemFromQuote($item, $quote)
    {
        $quote->removeItem($item->getItemId())->collectTotals()->save();
    }

    /**
     * Comparing with commerce service version
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function checkVersion()
    {
        if (version_compare($this->lsr->getOmniVersion(), '4.21', '>')) {
            return true;
        }

        return false;
    }
}
