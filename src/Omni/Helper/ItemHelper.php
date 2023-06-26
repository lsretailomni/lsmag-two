<?php

namespace Ls\Omni\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\ItemGetByIdResponse;
use \Ls\Omni\Client\Ecommerce\Entity\Order;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Client\Ecommerce\Entity\VariantRegistration;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Replication\Model\ReplBarcodeRepository;
use Magento\Bundle\Api\ProductLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Quote\Model\ResourceModel\Quote\Item;

/**
 * Useful helper functions for item
 *
 */
class ItemHelper extends AbstractHelper
{
    /** @var SearchCriteriaBuilder */
    public $searchCriteriaBuilder;

    /** @var ReplBarcodeRepository */
    public $barcodeRepository;

    /** @var ProductRepository */
    public $productRepository;

    /** @var CartRepositoryInterface * */
    public $quoteRepository;

    /**
     * @var Proxy
     */
    public $checkoutSession;

    /** @var Cart $cart */
    public $cart;

    /**
     * @var Item
     */
    public $itemResourceModel;

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @var Quote
     */
    public $quoteResourceModel;

    /**
     * @var QuoteFactory
     */
    public $quoteFactory;

    /**
     * @var ProductLinkManagementInterface
     */
    public $productLinkManagement;

    /**
     * @param Context $context
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ReplBarcodeRepository $barcodeRepository
     * @param ProductRepository $productRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param Proxy $checkoutSession
     * @param Item $itemResourceModel
     * @param LoyaltyHelper $loyaltyHelper
     * @param Cart $cart
     * @param Quote $quoteResourceModel
     * @param QuoteFactory $quoteFactory
     * @param ProductLinkManagementInterface $productLinkManagement
     */
    public function __construct(
        Context $context,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ReplBarcodeRepository $barcodeRepository,
        ProductRepository $productRepository,
        CartRepositoryInterface $quoteRepository,
        Proxy $checkoutSession,
        Item $itemResourceModel,
        LoyaltyHelper $loyaltyHelper,
        Cart $cart,
        Quote $quoteResourceModel,
        QuoteFactory $quoteFactory,
        ProductLinkManagementInterface $productLinkManagement
    ) {
        parent::__construct($context);
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->barcodeRepository     = $barcodeRepository;
        $this->productRepository     = $productRepository;
        $this->quoteRepository       = $quoteRepository;
        $this->checkoutSession       = $checkoutSession;
        $this->itemResourceModel     = $itemResourceModel;
        $this->loyaltyHelper         = $loyaltyHelper;
        $this->cart                  = $cart;
        $this->quoteResourceModel    = $quoteResourceModel;
        $this->quoteFactory          = $quoteFactory;
        $this->productLinkManagement = $productLinkManagement;
    }

    /**
     * @param $id
     * @param bool $lite
     * @return bool|Entity\LoyItem
     */
    public function get($id, $lite = false)
    {
        $result = false;
        // @codingStandardsIgnoreStart
        $entity = new Entity\ItemGetById();
        $entity->setItemId($id);
        $request = new Operation\ItemGetById();
        // @codingStandardsIgnoreEnd

        /** @var ItemGetByIdResponse $response */
        $response = $request->execute($entity);

        if ($response && !($response->getItemGetByIdResult() == null)) {
            $item   = $response->getItemGetByIdResult();
            $result = $item;
        }

        return $lite && $result
            ? $this->lite($result)
            : $result;
    }

    /**
     * @param Entity\LoyItem $item
     * @return Entity\LoyItem
     */
    public function lite(Entity\LoyItem $item)
    {
        // @codingStandardsIgnoreStart
        return (new Entity\LoyItem())
            ->setId($item->getId())
            ->setPrice($item->getPrice())
            ->setAllowedToSell($item->getAllowedToSell());
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param Entity\LoyItem $item
     * @return Entity\UnitOfMeasure|Entity\UnitOfMeasure[]|null
     */
    public function uom(Entity\LoyItem $item)
    {
        // @codingStandardsIgnoreLine
        $uom        = new Entity\UnitOfMeasure();
        $salesUomId = $item->getSalesUomId();

        $uoms = $item->getUnitOfMeasures()->getUnitOfMeasure();

        if (is_array($uoms)) {
            /** @var Entity\UnitOfMeasure $row */
            foreach ($uoms as $row) {
                if ($row->getId() == $salesUomId) {
                    $uom = $row;
                    break;
                }
            }
        } else {
            $uom = $uoms;
        }
        /** @var Entity\UnitOfMeasure $response */
        // @codingStandardsIgnoreLine
        $response = new Entity\UnitOfMeasure();
        $response->setId($uom->getId())
            ->setDecimals($uom->getDecimals())
            ->setDescription($uom->getDescription())
            ->setItemId($uom->getItemId())
            ->setPrice($uom->getPrice())
            ->setQtyPerUom($uom->getQtyPerUom())
            ->setShortDescription($uom->getShortDescription());

        return $response;
    }

    /**
     * @param Entity\LoyItem $item
     * @param null $variant_id
     * @return VariantRegistration|null
     */
    public function getItemVariant(Entity\LoyItem $item, $variant_id = null)
    {
        $variant = null;
        if (($variant_id == null)) {
            return $variant;
        }
        $variants = $item->getVariantsRegistration()->getVariantRegistration();
        if (!is_array($variants)) {
            $variants = [$item->getVariantsRegistration()->getVariantRegistration()];
        }
        /** @var VariantRegistration $row */
        foreach ($variants as $row) {
            if ($row->getId() == $variant_id) {
                $variant = $row;
                break;
            }
        }

        /**  Omni is not accepting the return object so trying to work this out in different way */

        /** @var VariantRegistration $response */
        // @codingStandardsIgnoreLine
        $response = new VariantRegistration();

        $response->setItemId($variant->getItemId())
            ->setId($variant->getId())
            ->setDimension1($variant->getDimension1())
            ->setDimension2($variant->getDimension2())
            ->setDimension3($variant->getDimension3())
            ->setDimension4($variant->getDimension4())
            ->setDimension5($variant->getDimension5())
            ->setDimension6($variant->getDimension6())
            ->setFrameworkCode($variant->getFrameworkCode())
            ->setImages($variant->getImages());

        return $response;
    }

    /**
     * This function is overriding in hospitality module
     *
     * Compare orderLines with discountLines and get discounted prices on cart page or order detail page
     *
     * @param $item
     * @param Order|SalesEntry $orderData
     * @param int $type
     * @return array|null
     */
    public function getOrderDiscountLinesForItem($item, $orderData, $type = 1)
    {
        $discountText      = __("Save");
        $discountInfo = [];
        try {
            if ($type == 2) {
                $itemId      = $item->getItemId();
                $variantId   = $item->getVariantId();
                $uom         = $item->getUomId();
                $baseUnitOfMeasure = "";
                $customPrice = $item->getDiscountAmount();
                $this->getDiscountInfo(
                    $orderData,
                    $customPrice,
                    $itemId,
                    $variantId,
                    $uom,
                    $baseUnitOfMeasure,
                    $discountInfo
                );
            } else {
                $customPrice = $item->getCustomPrice();
                $children = [];

                if ($item->getProductType() == Type::TYPE_BUNDLE) {
                    $children = $item->getChildren();
                } else {
                    $children[] = $item;
                }

                foreach ($children as $child) {
                    $baseUnitOfMeasure = $child->getProduct()->getData('uom');
                    list($itemId, $variantId, $uom) = $this->getComparisonValues(
                        $child->getSku()
                    );
                    $this->getDiscountInfo(
                        $orderData,
                        $customPrice,
                        $itemId,
                        $variantId,
                        $uom,
                        $baseUnitOfMeasure,
                        $discountInfo
                    );
                }
            }
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        if (!empty($discountInfo)) {
            return [implode($discountInfo), $discountText];
        } else {
            return null;
        }
    }

    /**
     * Get discount related info in the basket coming from Central
     *
     * @param $orderData
     * @param $customPrice
     * @param $itemId
     * @param $variantId
     * @param $uom
     * @param $baseUnitOfMeasure
     * @param $discountInfo
     * @return mixed
     */
    public function getDiscountInfo(
        $orderData,
        $customPrice,
        $itemId,
        $variantId,
        $uom,
        $baseUnitOfMeasure,
        &$discountInfo
    ) {
        $orderLines = $discountsLines = [];
        if ($orderData instanceof SalesEntry) {
            $orderLines     = $orderData->getLines();
            $discountsLines = $orderData->getDiscountLines();
        } elseif ($orderData instanceof Order) {
            $orderLines     = $orderData->getOrderLines();
            $discountsLines = $orderData->getOrderDiscountLines()->getOrderDiscountLine();
        }

        foreach ($orderLines as $line) {
            if ($this->isValid($line, $itemId, $variantId, $uom, $baseUnitOfMeasure)) {
                if ($customPrice > 0 && $customPrice != null) {
                    foreach ($discountsLines as $orderDiscountLine) {
                        if ($line->getLineNumber() == $orderDiscountLine->getLineNumber()) {
                            if (!in_array($orderDiscountLine->getDescription() . '<br />', $discountInfo)) {
                                $discountInfo[] = $orderDiscountLine->getDescription() . '<br />';
                            }
                        }
                    }
                }
            }
        }

        return $discountInfo;
    }

    /**
     *
     * Setting prices coming from Central
     *
     * @param $quote
     * @param $basketData
     * @param int $type
     */
    public function setDiscountedPricesForItems($quote, $basketData, $type = 1)
    {
        try {
            $this->compareQuoteItemsWithOrderLinesAndSetRelatedAmounts($quote, $basketData, $type);
            $this->setGrandTotalGivenQuote($quote, $basketData, $type);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * This function is overriding in hospitality module
     *
     * Compare one_list lines with quote_item items and set correct prices
     *
     * @param $quote
     * @param $basketData
     * @param int $type
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     */
    public function compareQuoteItemsWithOrderLinesAndSetRelatedAmounts(&$quote, $basketData, $type = 1)
    {
        $quoteItemList = $quote->getAllVisibleItems();

        foreach ($quoteItemList as $quoteItem) {
            $bundleProduct = $customPrice = $discountAmount = $taxAmount = $rowTotal = $rowTotalIncTax = $priceInclTax = 0;
            $children = [];
            $orderLines = $basketData->getOrderLines()->getOrderLine();

            if ($quoteItem->getProductType() == Type::TYPE_BUNDLE) {
                $children = $quoteItem->getChildren();
                $bundleProduct = 1;
            } else {
                $children[] = $quoteItem;
            }

            foreach ($children as $child) {
                $baseUnitOfMeasure = $child->getProduct()->getData('uom');
                list($itemId, $variantId, $uom) = $this->getComparisonValues(
                    $child->getSku()
                );

                foreach ($orderLines as $index => $line) {
                    if ($this->isValid($line, $itemId, $variantId, $uom, $baseUnitOfMeasure)) {
                        $unitPrice = $line->getAmount() / $line->getQuantity();
                        $this->setRelatedAmountsAgainstGivenQuoteItem($line, $child, $unitPrice, $type);
                        unset($orderLines[$index]);
                        break;
                    }
                }
                $child->getProduct()->setIsSuperMode(true);
                try {
                    // @codingStandardsIgnoreLine
                    $this->itemResourceModel->save($child);
                } catch (LocalizedException $e) {
                    $this->_logger->critical("Error saving SKU:-".$child->getSku(). " - ".$e->getMessage());
                }

                $customPrice += $child->getCustomPrice();
                $priceInclTax += $child->getPriceInclTax();
                $taxAmount += $child->getTaxAmount();
                $rowTotal += $child->getRowTotal();
                $rowTotalIncTax += $child->getRowTotalInclTax();
                $discountAmount += $child->getDiscountAmount();
            }

            if ($bundleProduct == 1) {
                $quoteItem->setCustomPrice($customPrice);
                $quoteItem->setDiscountAmount($discountAmount);
                $quoteItem->setRowTotal($rowTotal);
                $quoteItem->setRowTotalInclTax($rowTotalIncTax);
                $quoteItem->setTaxAmount($taxAmount);
                $quoteItem->setPriceInclTax($priceInclTax);
                $quoteItem->getProduct()->setIsSuperMode(true);
                try {
                    // @codingStandardsIgnoreLine
                    $this->itemResourceModel->save($quoteItem);
                } catch (LocalizedException $e) {
                    $this->_logger->critical("Error saving Quote Item:-".$quoteItem->getSku(). " - ".$e->getMessage());
                }
            }
        }
    }

    /**
     * Setting grand total in quote_address
     *
     * @param $quote
     * @param $basketData
     * @param $type
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     */
    public function setGrandTotalGivenQuote(&$quote, $basketData, $type)
    {
        if ($quote->getId()) {
            if (isset($basketData)) {
                $pointDiscount  = $quote->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
                $giftCardAmount = $quote->getLsGiftCardAmountUsed();
                $quote->getShippingAddress()->setGrandTotal(
                    $basketData->getTotalAmount() - $giftCardAmount - $pointDiscount
                );
            }
            $couponCode = $quote->getCouponCode();
            $quote->getShippingAddress()->setCouponCode($couponCode);

            if ($basketData && method_exists($basketData, 'getPointsRewarded')) {
                $quote->setLsPointsEarn($basketData->getPointsRewarded());
            }

            if ($type == 2) {
                $this->checkoutSession->setData('stopCalcRowTotal', 1);
            } else {
                $this->checkoutSession->unsetData('stopCalcRowTotal');
            }
            $quote->setTotalsCollectedFlag(false)->collectTotals();
            $this->quoteResourceModel->save($quote);
        }
    }

    /**
     * Setting related amounts in each quote_item
     *
     * @param $line
     * @param $quoteItem
     * @param $unitPrice
     * @param int $type
     */
    public function setRelatedAmountsAgainstGivenQuoteItem($line, &$quoteItem, $unitPrice, $type = 1)
    {
        $customPrice = $discountAmount = $amount = $taxAmount = $netAmount = null;
        $itemQty = $quoteItem->getQty();

        if ($quoteItem->getParentItem() &&
            $quoteItem->getParentItem()->getProductType() == Type::TYPE_BUNDLE
        ) {
            $itemQty = $quoteItem->getParentItem()->getQty();
        }
        $qtyEqual = $line->getQuantity() == $itemQty;

        if ($line->getDiscountAmount() > 0) {
            $discountAmount = $qtyEqual ? $line->getDiscountAmount() :
                ($line->getDiscountAmount() / $line->getQuantity()) * $itemQty;
            $customPrice = $unitPrice;
        } elseif ($line->getAmount() != $quoteItem->getProduct()->getPrice()) {
            $customPrice = $unitPrice;
        }

        if ($line->getTaxAmount() > 0) {
            $taxAmount = $qtyEqual ? $line->getTaxAmount() :
                ($line->getTaxAmount() / $line->getQuantity()) * $itemQty;
        }

        if ($line->getNetAmount() > 0) {
            $netAmount = $qtyEqual ? $line->getNetAmount() :
                ($line->getNetAmount() / $line->getQuantity()) * $itemQty;
        }

        if ($line->getAmount() > 0) {
            $amount = $qtyEqual ? $line->getAmount() :
                ($line->getAmount() / $line->getQuantity()) * $itemQty;
        }

        $rowTotal = $line->getPrice() * $line->getQuantity();

        $quoteItem->setCustomPrice($customPrice)
            ->setDiscountAmount($discountAmount)
            ->setOriginalCustomPrice($customPrice)
            ->setTaxAmount($taxAmount)
            ->setBaseTaxAmount($taxAmount)
            ->setPriceInclTax($unitPrice)
            ->setBasePriceInclTax($unitPrice)
            ->setRowTotal($type == 1 ? $netAmount : $rowTotal)
            ->setBaseRowTotal($type == 1 ? $netAmount : $rowTotal)
            ->setRowTotalInclTax($type == 1 ? $amount : $rowTotal)
            ->setBaseRowTotalInclTax($type == 1 ? $amount : $rowTotal);
    }

    /**
     * Get products by Item Ids
     *
     * @param array $itemIds
     * @return array|ProductInterface[]
     */
    public function getProductsInfoByItemIds($itemIds)
    {
        $productData = [];
        try {
            $criteria    = $this->searchCriteriaBuilder
                ->addFilter(
                    LSR::LS_ITEM_ID_ATTRIBUTE_CODE,
                    implode(",", $itemIds),
                    'in'
                )->addFilter(
                    LSR::LS_VARIANT_ID_ATTRIBUTE_CODE,
                    true,
                    'null'
                )
                ->create();
            $product     = $this->productRepository->getList($criteria);
            $productData = $product->getItems();
        } catch (Exception $e) {
            $this->_logger->debug($e->getMessage());
        }

        return $productData;
    }

    /**
     * Get product by itemId and variantId
     *
     * @param string $itemId
     * @param string $variantId
     * @return false|mixed
     */
    public function getProductByIdentificationAttributes($itemId, $variantId = '')
    {
        $searchCriteria = clone $this->searchCriteriaBuilder;
        $productData    = null;
        try {
            $searchCriteria->addFilter(LSR::LS_ITEM_ID_ATTRIBUTE_CODE, $itemId);

            if ($variantId != '') {
                $searchCriteria->addFilter(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE, $variantId);
            } else {
                $searchCriteria->addFilter(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE, true, 'null');
            }

            $productData = $this->productRepository->getList($searchCriteria->create())->getItems();
        } catch (Exception $e) {
            $this->_logger->debug($e->getMessage());
        }

        return current($productData);
    }

    /**
     * Get relevant attributes values of item for sending to Central or for comparison purposes
     *
     * @param string $sku
     * @param string $parentId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getComparisonValues($sku, $parentId = '')
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('sku', $sku, 'eq')->create();
        $productList    = $this->productRepository->getList($searchCriteria)->getItems();
        /** @var Product $product */
        $product   = array_pop($productList);
        $itemId    = $product->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE);
        $variantId = $product->getData(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE);
        $uom       = $product->getData('uom');
        $barCode   = $product->getData('barcode');
        $uomQty    = $product->getData(LSR::LS_UOM_ATTRIBUTE_QTY);
        $baseUom   = null;

        if ($parentId != '') {
            $parentProduct = $this->productRepository->getById($parentId);
            $baseUom       = $parentProduct->getData('uom');
        }

        return [$itemId, $variantId, $uom, $barCode, $uomQty, $baseUom];
    }

    /**
     * Get Ls Central Item Id by sku
     *
     * @param string $sku
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getLsCentralItemIdBySku($sku)
    {
        $product = $this->productRepository->get($sku);
        $itemId  = $product->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE);

        return $itemId ?: $sku;
    }

    /**
     * Get product given sku
     *
     * @param $sku
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    public function getProductGivenSku($sku)
    {
        return $this->productRepository->get($sku);
    }

    /**
     * Get bundle product linked item_ids
     *
     * @param $bundleProduct
     * @return array
     * @throws NoSuchEntityException
     * @throws InputException
     */
    public function getLinkedProductsItemIds($bundleProduct)
    {
        $items = $this->productLinkManagement->getChildren($bundleProduct->getSku());
        $itemIds = [];

        foreach ($items as $item) {
            $itemIds[] = $this->getLsCentralItemIdBySku($item->getSku());
        }

        return $itemIds;
    }

    /**
     * Test if same quote_item as given line
     *
     * @param $line
     * @param $itemId
     * @param $variantId
     * @param $uom
     * @param $baseUnitOfMeasure
     * @return bool
     */
    public function isValid($line, $itemId, $variantId, $uom, $baseUnitOfMeasure)
    {
        return (($itemId == $line->getItemId() && $variantId == $line->getVariantId()) &&
            ($uom == $line->getUomId() || (empty($line->getUomId()) && $uom == $baseUnitOfMeasure)));
    }
}
