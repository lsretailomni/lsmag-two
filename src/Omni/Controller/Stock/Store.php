<?php
declare(strict_types=1);

namespace Ls\Omni\Controller\Stock;

use \Ls\Omni\Block\Stores\Stores;
use \Ls\Omni\Helper\AbstractHelperOmni;
use Ls\Omni\Helper\ItemHelper;
use Ls\Omni\Helper\VendorSourcingHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Model\Quote\Item;
use Psr\Log\LoggerInterface;

/**
 * Controller to accept request for stock lookup for each item in the quote
 */
class Store implements HttpPostActionInterface
{
    /**
     * @param JsonFactory $resultJsonFactory
     * @param Http $request
     * @param PageFactory $resultPageFactory
     * @param AbstractHelperOmni $abstractHelperOmni
     * @param VendorSourcingHelper $vendorSourcingHelper
     * @param ItemHelper $itemHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        public JsonFactory $resultJsonFactory,
        public Http $request,
        public PageFactory $resultPageFactory,
        public AbstractHelperOmni $abstractHelperOmni,
        public VendorSourcingHelper $vendorSourcingHelper,
        public ItemHelper $itemHelper,
        public LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->request->isAjax()) {
            return $result;
        }

        $stockHelper = $this->abstractHelperOmni->getStockHelper();
        $dataHelper = $this->abstractHelperOmni->getDataHelper();
        $checkoutSession = $this->abstractHelperOmni->getCheckoutSession();

        $selectedStore = (string)$this->request->getParam('storeid');
        $items = $checkoutSession->getQuote()->getAllVisibleItems();

        [$stockedItems, $vendorSourcedItems] = $this->partitionItems($items);

        $stockCollection = [];
        $lookupFailed = false;

        // Ticket 89360 follow-up: no GetInventoryMultipleV2 call at all for vendor-sourcing-
        // eligible (manage_stock=0) items - only items that are actually stock-managed go through
        // the inventory lookup here.
        if (!empty($stockedItems)) {
            [$response, $partialCollection] = $stockHelper->getGivenItemsStockInGivenStore(
                $stockedItems,
                $selectedStore
            );

            if ($response && !empty($response->getInventorybufferout())) {
                $stockCollection = $stockHelper->updateStockCollection($response, $partialCollection);
            } else {
                $lookupFailed = true;
            }
        }

        if (!$lookupFailed && !empty($vendorSourcedItems)) {
            $stockCollection = array_merge(
                $stockCollection,
                $this->buildVendorSourcedRows($vendorSourcedItems, $selectedStore)
            );
        }

        if ($lookupFailed) {
            return $result->setData([
                'remarks' => __('Oops! Unable to do stock lookup currently.'),
                'stocks' => null,
                'storeHoursHtml' => null
            ]);
        }

        $hours = $dataHelper->getStoreHours($selectedStore);
        $resultPage = $this->resultPageFactory->create();
        $storeHoursHtml = $resultPage->getLayout()->createBlock(Stores::class)
            ->setTemplate('Ls_Omni::stores/hours.phtml')
            ->setData(['hours' => $hours])
            ->toHtml();

        return $result->setData([
            'remarks' => __('Please check other stores or remove the not available item(s) from your '),
            'stocks' => $stockCollection,
            'storeHoursHtml' => $storeHoursHtml
        ]);
    }

    /**
     * Split the cart's quote items into those that need a regular inventory lookup versus those
     * that are vendor-sourcing-eligible and need a sourcing-location lookup instead. Bundle items
     * are kept in the stocked bucket unchanged (a bundle mixing vendor-sourced and ordinary
     * children is not a scenario this feature is designed around elsewhere either) - only
     * non-bundle items are partitioned by isVendorSourced().
     *
     * @param Item[] $items
     * @return array{0: Item[], 1: Item[]}
     */
    private function partitionItems(array $items): array
    {
        $stockedItems = [];
        $vendorSourcedItems = [];

        foreach ($items as $item) {
            if ($item->getProductType() === \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                $stockedItems[] = $item;
                continue;
            }

            [$parent, $child] = $this->resolveParentAndChildProducts($item);

            if ($parent && $this->vendorSourcingHelper->isVendorSourced($parent, $child)) {
                $vendorSourcedItems[] = $item;
            } else {
                $stockedItems[] = $item;
            }
        }

        return [$stockedItems, $vendorSourcedItems];
    }

    /**
     * For each vendor-sourcing-eligible item, look up its sourcing options directly (no inventory
     * call at all) and build a stock-collection-shaped row: available if the selected store is
     * among its collect-type sourcing stores, not available otherwise.
     *
     * @param Item[] $vendorSourcedItems
     * @param string $selectedStore
     * @return array
     */
    private function buildVendorSourcedRows(array $vendorSourcedItems, string $selectedStore): array
    {
        $rows = [];

        foreach ($vendorSourcedItems as $item) {
            [$parent, $child] = $this->resolveParentAndChildProducts($item);

            if (!$parent) {
                continue;
            }

            [$itemId, $variantId] = $this->resolveComparisonIds($item->getSku());

            try {
                $options = $this->vendorSourcingHelper->getOptions(
                    $parent->getSku(),
                    $child ? (int)$child->getId() : null
                );
            } catch (\Throwable $e) {
                $this->logger->error(
                    'Vendor sourcing store check options lookup failed: ' . $e->getMessage()
                );
                $options = [];
            }

            $collectCodes = array_column(
                array_filter($options, static fn (array $o): bool => $o['type'] === VendorSourcingHelper::TYPE_COLLECT),
                'store_code'
            );

            $available = in_array($selectedStore, $collectCodes, true);

            // Distinguish "genuinely not sourceable from any vendor" (no collect-type stores at
            // all) from "sourceable, just not at this particular store" (collect-type stores
            // exist, just not the selected one) - the latter is a different, less alarming
            // situation for the customer than the former, same distinction
            // CheckoutFallbackService::wrongStoreBlockedVerdict() already makes for the
            // equivalent case in the main checkout validation flow.
            if ($available) {
                $display = __('This item is available at the selected store and can be sourced from a vendor.');
            } elseif (!empty($collectCodes)) {
                $display = __(
                    'This item cannot be sourced from a vendor at the selected store, but is available '
                    . 'at another store. Please choose a different store.'
                );
            } else {
                $display = __('This item is not available at the selected store and cannot be sourced from a vendor.');
            }

            $rows[] = [
                'item_id' => $itemId,
                'variant_id' => $variantId,
                'name' => $item->getName(),
                'qty' => $item->getQty(),
                'status' => $available ? '1' : '0',
                'display' => $display,
                'quote_item' => $item,
            ];
        }

        return $rows;
    }

    /**
     * Resolve (item_id, variant_id) for a SKU via a local repository lookup
     * (ItemHelper::getComparisonValues() - a DB query, not a Central API call), matching the same
     * identity space an inventory-checked row would carry, without performing any inventory call.
     *
     * Injected directly (not reached via $this->abstractHelperOmni->getStockHelper()->itemHelper)
     * because AbstractHelperOmni's own stockHelper dependency is wired as a \Proxy - Magento's
     * generated Proxy classes only forward METHOD calls, not direct property access, so reaching
     * through a Proxy's public property throws "must not be accessed before initialization".
     *
     * @param string $sku
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveComparisonIds(string $sku): array
    {
        $comparisonValues = $this->itemHelper->getComparisonValues($sku);

        return $comparisonValues ? [$comparisonValues[0], $comparisonValues[1]] : [null, null];
    }

    /**
     * @param Item $item
     * @return array{0: ?Product, 1: ?Product}
     */
    private function resolveParentAndChildProducts(Item $item): array
    {
        $parent = $item->getProduct();
        $child = null;

        if ($item->getOptionByCode('simple_product')) {
            $child = $item->getOptionByCode('simple_product')->getProduct();
        }

        return [$parent, $child];
    }
}
