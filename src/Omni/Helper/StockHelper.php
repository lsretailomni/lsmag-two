<?php
declare(strict_types=1);

namespace Ls\Omni\Helper;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\CentralEcommerce\Entity\GetInventoryMultipleV2;
use \Ls\Omni\Client\CentralEcommerce\Entity\InventoryBufferIn;
use \Ls\Omni\Client\CentralEcommerce\Entity\RootGetInventoryMultipleIn;
use \Ls\Omni\Client\CentralEcommerce\Entity\RootGetInventoryMultipleOut;
use \Ls\Omni\Client\CentralEcommerce\Operation;
use \Ls\Replication\Model\ResourceModel\ReplStore\Collection;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Item;

/**
 * Stock related operation helper
 */
class StockHelper extends AbstractHelperOmni
{
    /**
     * Getting items stock in store
     *
     * @param $storeId
     * @param $parentProductId
     * @param $childProductId
     * @return null|RootGetInventoryMultipleOut
     */
    public function getItemStockInStore(string $storeId, string $parentProductId, string $childProductId)
    {
        $items[] = ['parent' => $parentProductId, 'child' => $childProductId];

        return $this->getItemsStockInStoreFromSourcingLocation($storeId, $items);
    }

    /**
     * This function is overriding in hospitality module
     *
     * Get stock for all the given items in given store
     *
     * @param array $items
     * @param string $storeId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getGivenItemsStockInGivenStore(array $items, string $storeId = '')
    {
        $stockCollection = $stockItems = [];
        $useManageStockConfiguration = $this->configuration->getManageStock();

        foreach ($items as &$item) {
            $children = [];
            if ($item->getProductType() == Type::TYPE_BUNDLE) {
                $children = $item->getChildren();
            } else {
                $children[] = $item;
            }

            foreach ($children as $child) {
                $itemQty = $item->getQty();
                list($parentProductSku, $childProductSku, , , $uomQty) = $this->itemHelper->getComparisonValues(
                    $child->getSku()
                );

                if (!empty($uomQty)) {
                    $itemQty = $itemQty * $uomQty;
                }

                if ($useManageStockConfiguration) {
                    $product = $this->productRepository->get($child->getSku());
                    try {
                        $stockItem     = $this->stockItemRepository->get($product->getId());
                        $useMangeStock = $stockItem->getUseConfigManageStock();
                    } catch (\Exception $e) {
                        $useMangeStock = false;
                    }

                    if ($useMangeStock) {
                        $stockItems[] = ['parent' => $parentProductSku, 'child' => $childProductSku];
                        $this->mergeStockCollection(
                            $stockCollection,
                            $parentProductSku,
                            $childProductSku,
                            $child->getName(),
                            $itemQty
                        );
                    }
                }
            }
        }

        return [$this->getAllItemsStockInSingleStore($storeId, $stockItems), $stockCollection];
    }

    /**
     * Merge Stock Collection
     *
     * @param $stockCollection
     * @param $parentProductSku
     * @param $childProductSku
     * @param $name
     * @param $qty
     */
    public function mergeStockCollection(&$stockCollection, $parentProductSku, $childProductSku, $name, $qty)
    {
        $found = false;

        foreach ($stockCollection as &$stock) {
            if ($stock['item_id'] == $parentProductSku && $stock['variant_id'] == $childProductSku) {
                $stock['qty'] = $stock['qty'] + $qty;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $stockCollection[] = [
                'item_id' => $parentProductSku,
                'variant_id' => $childProductSku,
                'name' => $name,
                'qty' => $qty
            ];
        }
    }

    /**
     * This function is overriding in hospitality module
     *
     * For sourcing location of inventory
     *
     * @param string $storeId
     * @param array $items
     * @return null|RootGetInventoryMultipleOut
     */
    public function getItemsStockInStoreFromSourcingLocation(string $storeId, array $items)
    {
        // @codingStandardsIgnoreStart
        $operation = $this->createInstance(Operation\GetInventoryMultipleV2::class);
        $itemsCollection = [];
        foreach ($items as $item) {
            $payload = [];
            $itemId = reset($item);
            $variantId = end($item);
            $payload[InventoryBufferIn::NUMBER] = $itemId;
            if (!empty($itemId) && !empty($variantId)) {
                $payload[InventoryBufferIn::VARIANT] = $variantId;
            }

            $item = $operation->createInstance(
                InventoryBufferIn::class,
                ['data' => $payload]
            );
            $itemsCollection[] = $item;
        }

        $getInventoryMultipleInXML = $operation->createInstance(
            RootGetInventoryMultipleIn::class,
            ['data' => [RootGetInventoryMultipleIn::INVENTORY_BUFFER_IN => $itemsCollection]]
        );

        $operation->setOperationInput([
            GetInventoryMultipleV2::STORE_NO => $storeId,
            GetInventoryMultipleV2::SOURCING_LOCATION_AVAILABILITY => 1,
            GetInventoryMultipleV2::GET_INVENTORY_MULTIPLE_IN_XML => $getInventoryMultipleInXML,
        ]);
        $response = $operation->execute();
        // @codingStandardsIgnoreEnd

        return $response && !empty($response->getResponsecode() == "0000") ?
            $response->getGetinventorymultipleoutxml() : null;
    }

    /**
     * Get items stock in store
     *
     * @param string $storeId
     * @param array $items
     * @return RootGetInventoryMultipleOut
     */
    public function getAllItemsStockInSingleStore(string $storeId, array $items)
    {
        return $this->getItemsStockInStoreFromSourcingLocation($storeId, $items);
    }

    /**
     * Call ItemsInStockGet method to check Items in stock or not
     *
     * @param string $simpleProductId
     * @param string $itemId
     * @return RootGetInventoryMultipleOut|null
     * @throws NoSuchEntityException
     * @throws GuzzleException
     */
    public function getAllStoresItemInStock(string $simpleProductId, string $itemId)
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $simpleProductSku = '';

            if (!empty($simpleProductId)) {
                $simpleProductSku = $this->productRepository->getById($simpleProductId)
                    ->getData(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE);
            }

            $items[] = ['parent' => $itemId, 'child' => $simpleProductSku];

            return $this->getItemsStockInStoreFromSourcingLocation('', $items);
        } else {
            return null;
        }
    }

    /**
     * Get given stores information from repl store table
     *
     * @param array $storesNavIds
     * @return Collection
     * @throws NoSuchEntityException
     */
    public function getAllStoresFromReplTable(array $storesNavIds): Collection
    {
        $stores = $this->storeCollectionFactory->create()
            ->addFieldToFilter('nav_id', ['in' => $storesNavIds])
            ->addFieldToFilter(
                'scope_id',
                ['eq' => !$this->lsr->isSSM() ?
                    $this->lsr->getCurrentWebsiteId() :
                    $this->lsr->getAdminStore()->getWebsiteId()]
            );
        $displayStores = $this->lsr->getStoreConfig(LSR::SC_CART_DISPLAY_STORES);

        if (!$displayStores) {
            $stores->addFieldToFilter('ClickAndCollect', 1);
        }

        return $stores;
    }

    /**
     * Fetch all stores where given item is in stock and get all store data from stores repl table
     *
     * @param string $simpleProductId
     * @param string $productSku
     * @return Collection
     * @throws NoSuchEntityException|GuzzleException
     */
    public function fetchAllStoresItemInStockPlusApplyJoin(string $simpleProductId, string $productSku): Collection
    {
        $itemId = $this->itemHelper->getLsCentralItemIdBySku($productSku);
        $storesNavId = [];
        $response = $this->getAllStoresItemInStock(
            $simpleProductId,
            $itemId
        );

        if ($response !== null) {
            foreach ($response->getInventorybufferout() as $each) {
                if ($each->getInventory() > 0) {
                    $storesNavId[] = $each->getStore();
                }
            }
        }

        return $this->getAllStoresFromReplTable(
            $storesNavId
        );
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
        if (!$item->getHasError()) {
            $storeId = $this->lsr->getActiveWebStore();
            $children = [];

            if ($item->getProductType() == Type::TYPE_BUNDLE) {
                $children = $item->getChildren();
            } else {
                $children[] = $item;
            }

            foreach ($children as $child) {
                list($lsrId) = $this->itemHelper->getComparisonValues($child->getSku());

                if (in_array($lsrId, explode(',', $this->lsr->getGiftCardIdentifiers()))) {
                    if ($qty > 1) {
                        $item->setHasError(true);
                        $item->setMessage(__(
                            'Max quantity available for item %2 is %1',
                            1,
                            $item->getName()
                        ));
                        if ($throwException == true) {
                            throw new LocalizedException(__(
                                'Product %1 is not available.',
                                $item->getName()
                            ));
                        }
                    }
                    continue;
                }

                if ($this->lsr->inventoryLookupBeforeAddToCartEnabled()) {
                    $uomQty = $child->getProduct()->getData(LSR::LS_UOM_ATTRIBUTE_QTY);

                    if (!empty($uomQty)) {
                        $qty = $qty * $uomQty;
                    }
                    [$itemId, $variantId] = $this->itemHelper->getComparisonValues(
                        $child->getSku()
                    );

                    $stock = $this->getItemStockInStore(
                        $storeId,
                        $itemId,
                        $variantId
                    );

                    if ($stock && !empty($stock->getInventorybufferout())) {
                        $itemStock = current((array)$stock->getInventorybufferout());

                        if ($itemStock->getInventory() <= 0) {
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
                        } elseif ($itemStock->getInventory() < $qty) {
                            if ($isRemoveItem == true) {
                                $this->deleteItemFromQuote($item, $quote);
                            }
                            $item->setHasError(true);
                            $item->setMessage(__(
                                'Max quantity available for item %2 is %1',
                                $itemStock->getInventory(),
                                $item->getName()
                            ));
                            if ($throwException == true) {
                                throw new LocalizedException(__(
                                    'Max quantity available for item %2 is %1',
                                    $itemStock->getInventory(),
                                    $item->getName()
                                ));
                            }
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
     * @param RootGetInventoryMultipleOut $response
     * @param array $stockCollection
     * @return mixed
     */
    public function updateStockCollection(RootGetInventoryMultipleOut $response, array $stockCollection)
    {
        foreach ($response->getInventorybufferout() as $item) {
            $actualQty = ceil($item->getInventory());

            foreach ($stockCollection as &$values) {
                if ($values['item_id'] == $item->getNumber() && $values['variant_id'] == $item->getVariant()) {
                    if ($actualQty > 0) {
                        $values['status'] = '1';
                        $values['display'] = __('This item is available');

                        if ($values['qty'] > $actualQty) {
                            $values['status'] = '0';
                            $values['display'] = __(
                                'You have selected %1 quantity for this item.
                                 We only have %2 quantity available in stock for this store.
                                 Please update this item quantity in cart.',
                                $values['qty'],
                                $actualQty
                            );
                        }
                    } else {
                        $values['status'] = '0';
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
}
