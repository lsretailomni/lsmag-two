<?php
declare(strict_types=1);

namespace Ls\Omni\Controller\Stock;

use \Ls\Omni\Block\Stores\Stores;
use \Ls\Omni\Helper\AbstractHelperOmni;
use Ls\Omni\Helper\VendorSourcingHelper;
use Ls\Omni\Model\VendorSourcing\ZeroStockEverywhereResolver;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
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
     * @param ZeroStockEverywhereResolver $zeroStockResolver
     * @param LoggerInterface $logger
     */
    public function __construct(
        public JsonFactory $resultJsonFactory,
        public Http $request,
        public PageFactory $resultPageFactory,
        public AbstractHelperOmni $abstractHelperOmni,
        public VendorSourcingHelper $vendorSourcingHelper,
        public ZeroStockEverywhereResolver $zeroStockResolver,
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
        $stockHelper = $this->abstractHelperOmni->getStockHelper();
        $dataHelper = $this->abstractHelperOmni->getDataHelper();
        $checkoutSession = $this->abstractHelperOmni->getCheckoutSession();

        if ($this->request->isAjax()) {
            $selectedStore = $this->request->getParam('storeid');
            $items = $checkoutSession->getQuote()->getAllVisibleItems();
            $notAvailableNotice = __('Please check other stores or remove the not available item(s) from your ');
            list($response, $stockCollection) = $stockHelper->getGivenItemsStockInGivenStore(
                $items,
                $selectedStore
            );

            if ($response && !empty($response->getInventorybufferout())) {
                $stockCollection = $stockHelper->updateStockCollection($response, $stockCollection);
                $stockCollection = $this->applyVendorSourcingStoreCheck(
                    $stockCollection,
                    $items,
                    (string)$selectedStore,
                    $stockHelper
                );
                $hours = $dataHelper->getStoreHours($selectedStore);
                $resultPage = $this->resultPageFactory->create();
                $storeHoursHtml = $resultPage->getLayout()->createBlock(Stores::class)
                    ->setTemplate('Ls_Omni::stores/hours.phtml')
                    ->setData(['hours' => $hours])
                    ->toHtml();
                $result = $result->setData(
                    [
                        'remarks' => $notAvailableNotice,
                        'stocks' => $stockCollection,
                        'storeHoursHtml' => $storeHoursHtml
                    ]
                );
            } else {
                $notAvailableNotice = __('Oops! Unable to do stock lookup currently.');
                $result = $result->setData(
                    ['remarks' => $notAvailableNotice, 'stocks' => null, 'storeHoursHtml' => null]
                );
            }
        }

        return $result;
    }

    /**
     * FR8 (simplified, single-store application, solution plan §7.3): for every item flagged
     * isVendorSourced() that is zero-stock-everywhere, confirm the selected store is among its
     * collect-type sourcing stores; if not, override that item's row to "not available" so this
     * secondary, single-store ajax recheck agrees with {@see \Ls\Omni\Model\Checkout\DataProvider}'s
     * candidate list (§7.2).
     *
     * @param array $stockCollection
     * @param array $items
     * @param string $selectedStore
     * @param \Ls\Omni\Helper\StockHelper $stockHelper
     * @return array
     * @throws NoSuchEntityException
     */
    private function applyVendorSourcingStoreCheck(
        array $stockCollection,
        array $items,
        string $selectedStore,
        $stockHelper
    ): array {
        $flaggedRows = [];

        foreach ($stockCollection as $row) {
            [$parent, $child] = $this->resolveParentAndChildProducts($row);

            if ($parent && $this->vendorSourcingHelper->isVendorSourced($parent, $child)) {
                $flaggedRows[] = $row;
            }
        }

        if (empty($flaggedRows)) {
            return $stockCollection;
        }

        try {
            [$allStoreResponse] = $stockHelper->getGivenItemsStockIgnoringManageStock($items);
        } catch (\Throwable $e) {
            $this->logger->error(
                'Vendor sourcing store check stock lookup failed: ' . $e->getMessage()
            );

            // Fail closed: the all-store stock check could not be performed, leave every row
            // untouched rather than crash the ajax endpoint (same as the empty($flaggedRows)
            // early-return path above).
            return $stockCollection;
        }

        $stockMap = $this->zeroStockResolver->resolve($allStoreResponse, $flaggedRows);

        foreach ($stockCollection as &$row) {
            $key = $row['item_id'] . '|' . $row['variant_id'];

            if (!array_key_exists($key, $stockMap) || $stockMap[$key]) {
                continue; // not a flagged row, or stock exists somewhere - leave as-is
            }

            [$parent, $child] = $this->resolveParentAndChildProducts($row);

            if (!$parent) {
                continue;
            }

            try {
                $options = $this->vendorSourcingHelper->getOptions(
                    $parent->getSku(),
                    $child ? (int)$child->getId() : null
                );
            } catch (\Throwable $e) {
                $this->logger->error(
                    'Vendor sourcing store check options lookup failed: ' . $e->getMessage()
                );

                continue; // leave this row untouched rather than crash the ajax endpoint
            }

            $collectCodes = array_column(
                array_filter($options, static fn (array $o): bool => $o['type'] === VendorSourcingHelper::TYPE_COLLECT),
                'store_code'
            );

            if (!in_array($selectedStore, $collectCodes, true)) {
                $row['status'] = '0';
                $row['display'] = __('This item is not available at the selected store and cannot be sourced from a vendor.');
            }
        }
        unset($row);

        return $stockCollection;
    }

    /**
     * @param array $row
     * @return array{0: ?\Magento\Catalog\Model\Product, 1: ?\Magento\Catalog\Model\Product}
     */
    private function resolveParentAndChildProducts(array $row): array
    {
        $quoteItem = $row['quote_item'] ?? null;

        if (!$quoteItem) {
            return [null, null];
        }

        $parent = $quoteItem->getProduct();
        $child = null;

        if ($quoteItem->getOptionByCode('simple_product')) {
            $child = $quoteItem->getOptionByCode('simple_product')->getProduct();
        }

        return [$parent, $child];
    }
}
