<?php
declare(strict_types=1);

namespace Ls\Omni\Helper;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Order;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Useful helper functions for item
 *
 */
class ItemHelper extends AbstractHelperOmni
{
    /**
     * This function is overriding in hospitality module
     *
     * Compare orderLines with discountLines and get discounted prices on cart page or order detail page
     *
     * @param object $item
     * @param Order|SalesEntry $orderData
     * @param int $type
     * @param int $graphQlRequest
     * @return array|null
     */
    public function getOrderDiscountLinesForItem($item, $orderData, $type = 1, $graphQlRequest = 0)
    {
        $discountText = __("Save");
        $discountInfo = [];
        try {
            if ($type == 2) {
                $itemId            = $item->getNumber();
                $variantId         = $item->getVariantCode();
                $uom               = $item->getUnitOfMeasure();
                $baseUnitOfMeasure = "";
                $customPrice = $item->getDiscountAmount();
                $this->getDiscountInfo(
                    $item,
                    $orderData,
                    $customPrice,
                    $itemId,
                    $variantId,
                    $uom,
                    $baseUnitOfMeasure,
                    $discountInfo,
                    $graphQlRequest
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
                        $child,
                        $orderData,
                        $customPrice,
                        $itemId,
                        $variantId,
                        $uom,
                        $baseUnitOfMeasure,
                        $discountInfo,
                        $graphQlRequest
                    );
                }
            }
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        if (!empty($discountInfo)) {
            if (!$graphQlRequest) {
                return [implode($discountInfo), $discountText];
            }
            return [$discountInfo, $discountText];
        } else {
            return null;
        }
    }

    /**
     * Get discount related info in the basket coming from Central
     *
     * @param $quoteItem
     * @param $orderData
     * @param $customPrice
     * @param $itemId
     * @param $variantId
     * @param $uom
     * @param $baseUnitOfMeasure
     * @param $discountInfo
     * @param int $graphQlRequest
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getDiscountInfo(
        $quoteItem,
        $orderData,
        $customPrice,
        $itemId,
        $variantId,
        $uom,
        $baseUnitOfMeasure,
        &$discountInfo,
        $graphQlRequest = 0
    ) {
        $orderLines = $discountsLines = [];
        if ($orderData instanceof \Ls\Omni\Client\Ecommerce\Entity\GetSelectedSalesDoc_GetSelectedSalesDoc) {
            $orderLines     = !is_array($orderData->getLscMemberSalesDocLine()) ?
                [$orderData->getLscMemberSalesDocLine()] : $orderData->getLscMemberSalesDocLine() ;
            $discountsLines = !is_array($orderData->getLscMemberSalesDocDiscLine()) ?
                [$orderData->getLscMemberSalesDocDiscLine()] : $orderData->getLscMemberSalesDocDiscLine();
        } elseif ($orderData instanceof Order) {
            $orderLines     = $orderData->getOrderLines();
            $discountsLines = $orderData->getOrderDiscountLines()->getOrderDiscountLine();
        }

        foreach ($orderLines as $line) {
            if ($this->isValid($quoteItem, $line, $itemId, $variantId, $uom, $baseUnitOfMeasure)) {
                if ($customPrice > 0 && $customPrice != null) {
                    foreach ($discountsLines as $orderDiscountLine) {
                        if ($line->getLineNo() == $orderDiscountLine->getDocumentLineNo()) {
                            $offerDescription = !empty($orderDiscountLine->getDescription()) ?
                                $orderDiscountLine->getDescription() :
                                $orderDiscountLine->getOfferNo();

                            if (empty($offerDescription)) {
                                $offerDescription = __('Discounted');
                            }

                            $offerDescription .= '<br />';
                            if (!in_array($offerDescription, $discountInfo)) {
                                if (!$graphQlRequest) {
                                    $discountInfo[] = $offerDescription;
                                } else {
                                    $discountInfo[] = [
                                        'description' => $offerDescription,
                                        'value'       => $orderDiscountLine->getDiscountAmount()
                                    ];
                                }

                            }
                        }
                    }
                }
            }
        }

        return $discountInfo;
    }

    /**
     * Setting prices coming from Central into quote and quote_item
     *
     * @param $quote
     * @param $basketData
     * @param int $type
     * @throws GuzzleException
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
     * @throws NoSuchEntityException|LocalizedException
     */
    public function compareQuoteItemsWithOrderLinesAndSetRelatedAmounts(&$quote, $basketData, $type = 1)
    {
        $orderLines = [];
        $quoteItemList = $quote->getAllVisibleItems();

        if (count($quoteItemList) && !empty($basketData)) {
            $orderLines = $basketData->getMobiletransactionline();
        }

        foreach ($quoteItemList as $quoteItem) {
            $bundleProduct = $customPrice = $taxAmount = $rowTotal = $rowTotalIncTax = $priceInclTax = 0;
            $children = [];

            if ($quoteItem->getProductType() == Type::TYPE_BUNDLE) {
                $children = $quoteItem->getChildren();
                $bundleProduct = 1;
            } else {
                $children[] = $quoteItem;
            }

            foreach ($children as $child) {
                foreach ($orderLines as $index => $line) {
                    if (is_numeric($line->getId()) ?
                        $child->getItemId() == $line->getId() :
                        $this->isSameItem($child, $line)
                    ) {
                        $unitPrice = ($line->getNetamount() + $line->getTaxamount()) / $line->getQuantity();
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
                    $this->_logger->critical("Error saving SKU:-" . $child->getSku() . " - " . $e->getMessage());
                }

                $customPrice += $child->getCustomPrice() * $child->getQty();
                $priceInclTax += $child->getPriceInclTax() * $child->getQty();
                $taxAmount += $child->getTaxAmount();
                $rowTotal += $child->getRowTotal();
                $rowTotalIncTax += $child->getRowTotalInclTax();
            }

            if ($bundleProduct == 1) {
                $quoteItem->setCustomPrice($customPrice);
                $quoteItem->setRowTotal($rowTotal);
                $quoteItem->setRowTotalInclTax($rowTotalIncTax);
                $quoteItem->setTaxAmount($taxAmount);
                $quoteItem->setPriceInclTax($priceInclTax);
                $quoteItem->getProduct()->setIsSuperMode(true);
                try {
                    // @codingStandardsIgnoreLine
                    $this->itemResourceModel->save($quoteItem);
                } catch (LocalizedException $e) {
                    $this->_logger->critical(
                        "Error saving Quote Item:-" . $quoteItem->getSku() . " - " . $e->getMessage()
                    );
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
     * @throws NoSuchEntityException|GuzzleException
     */
    public function setGrandTotalGivenQuote(&$quote, $basketData, $type)
    {
        if ($quote->getId()) {
            if (isset($basketData)) {
                $mobileTransaction = current($basketData->getMobiletransaction());
                $pointDiscount = $quote->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
                $giftCardAmount = $quote->getLsGiftCardAmountUsed();
                $quote->getShippingAddress()
                    ->setGrandTotal(
                        $mobileTransaction->getGrossamount() - $giftCardAmount - $pointDiscount
                    );
            }
            $couponCode = $quote->getCouponCode();
            $quote->getShippingAddress()->setCouponCode($couponCode);

            if ($basketData) {
                if (method_exists($mobileTransaction, 'getIssuedpoints')) {
                    $quote->setLsPointsEarn($mobileTransaction->getIssuedpoints());
                }

                $quote->setLsDiscountAmount($mobileTransaction->getLinediscount());
            }

            if ($type == 2) {
                $this->checkoutSession->setData('stopCalcRowTotal', 1);
            } else {
                $this->checkoutSession->unsetData('stopCalcRowTotal');
            }
            $quote->setTotalsCollectedFlag(false)->collectTotals();
            $quote->setIsActive(1);
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
     * @throws NoSuchEntityException|LocalizedException
     */
    public function setRelatedAmountsAgainstGivenQuoteItem($line, &$quoteItem, $unitPrice, $type = 1)
    {
        $customPrice = $amount = $taxAmount = $netAmount = $lsDiscountAmount = null;
        $itemQty = $quoteItem->getQty();

        if ($quoteItem->getParentItem() &&
            $quoteItem->getParentItem()->getProductType() == Type::TYPE_BUNDLE
        ) {
            $itemQty = $quoteItem->getParentItem()->getQty() * $quoteItem->getQty();
        }
        $qtyEqual = $line->getQuantity() == $itemQty;

        if ($line->getDiscountamount() > 0) {
            $customPrice = $unitPrice;
            $lsDiscountAmount = $line->getDiscountamount();
        } elseif (($line->getNetamount() + $line->getTaxamount()) != $quoteItem->getProduct()->getPrice()) {
            $customPrice = $unitPrice;
        }

        if ($line->getTaxamount() > 0) {
            $taxAmount = $qtyEqual ? $line->getTaxamount() :
                ($line->getTaxamount() / $line->getQuantity()) * $itemQty;
        }

        if ($line->getNetamount() > 0) {
            $netAmount = $qtyEqual ? $line->getNetamount() :
                ($line->getNetamount() / $line->getQuantity()) * $itemQty;
        }

        if (($line->getNetamount() + $line->getTaxamount()) > 0) {
            $amount = $qtyEqual ? ($line->getNetamount() + $line->getTaxamount()) :
                (($line->getNetamount() + $line->getTaxamount()) / $line->getQuantity()) * $itemQty;
        }

        $rowTotal       = $line->getNetamount();
        $rowTotalIncTax = $line->getPrice() * $line->getQuantity();

        $quoteItem
            ->setCustomPrice($customPrice)
            ->setOriginalCustomPrice($customPrice)
            ->setTaxAmount($taxAmount)
            ->setBaseTaxAmount($this->convertToBaseCurrency($taxAmount))
            ->setPriceInclTax($unitPrice)
            ->setBasePriceInclTax($this->convertToBaseCurrency($unitPrice))
            ->setLsDiscountAmount($lsDiscountAmount)
            ->setRowTotal($type == 1 ? $netAmount : $rowTotal)
            ->setBaseRowTotal(
                $type == 1 ? $this->convertToBaseCurrency($netAmount) :
                    $this->convertToBaseCurrency($rowTotal)
            )
            ->setRowTotalInclTax($type == 1 ? $amount : $rowTotalIncTax)
            ->setBaseRowTotalInclTax(
                $type == 1 ? $this->convertToBaseCurrency($amount) :
                    $this->convertToBaseCurrency($rowTotalIncTax)
            );
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
            $criteria = $this->searchCriteriaBuilder
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
            $product = $this->productRepository->getList($criteria);
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
        $productData = null;
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

        return !empty($productData) ? current($productData) : null;
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
        if (!empty($productList)) {
            /** @var Product $product */
            $product   = array_pop($productList);
            $itemId    = $product->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE);
            $variantId = ($product->getData(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE))?:"";
            $uom       = $product->getData('uom');
            $barCode   = $product->getData('barcode');
            $uomQty    = $product->getData(LSR::LS_UOM_ATTRIBUTE_QTY);
            $baseUom   = null;

            if ($parentId != '') {
                $parentProduct = $this->productRepository->getById($parentId);
                $baseUom = $parentProduct->getData('uom');
            }

            return [$itemId, $variantId, $uom, $barCode, $uomQty, $baseUom];

        }

        return null;
    }

    /**
     * Get item attributes based on given quote item
     *
     * @param $quoteItem
     * @return array
     */
    public function getItemAttributesGivenQuoteItem($quoteItem)
    {
        if ($quoteItem->getProductType() != Type::DEFAULT_TYPE && $quoteItem->getProductType() != LSR::TYPE_GIFT_CARD) {
            $quoteItem = current($quoteItem->getChildren());
        }

        $itemId = $quoteItem->getProduct()->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE);
        $variantId = $quoteItem->getProduct()->getData(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE);
        $uom = $quoteItem->getProduct()->getData('uom');
        $barCode = $quoteItem->getProduct()->getData('barcode');
        $uomQty = $quoteItem->getProduct()->getData(LSR::LS_UOM_ATTRIBUTE_QTY);

        return [$itemId, $variantId, $uom, $barCode, $uomQty];
    }

    /**
     * Get Ls Central Item Id by sku
     *
     * @param string $sku
     * @return string
     * @throws NoSuchEntityException
     */
    public function getLsCentralItemIdBySku(string $sku): string
    {
        $product = $this->productRepository->get($sku);
        $itemId = $product->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE);

        return $itemId ?: $sku;
    }

    /**
     * Get product given sku
     *
     * @param string $sku
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    public function getProductGivenSku(string $sku): ProductInterface
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
     * @param $quoteItem
     * @param $line
     * @param $itemId
     * @param $variantId
     * @param $uom
     * @param $baseUnitOfMeasure
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isValid($quoteItem, $line, $itemId, $variantId, $uom, $baseUnitOfMeasure)
    {
        $giftCardIdentifier = $this->lsr->getGiftCardIdentifiers();

        return in_array($itemId, explode(',', $giftCardIdentifier)) ?
            $line->getNumber() == $quoteItem->getProduct()->getLsrItemId() :
            (($itemId == $line->getNumber() && $variantId == $line->getVariantCode()) &&
                ($uom == $line->getUnitOfMeasure() || (empty($line->getUnitOfMeasure()) &&
                        $uom == $baseUnitOfMeasure)));
    }

    /**
     * Validate to see if quoteItem = OneListItem
     *
     * @param $quoteItem
     * @param $line
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isSameItem($quoteItem, $line)
    {
        $baseUnitOfMeasure = $quoteItem->getProduct()->getData('uom');
        list($itemId, $variantId, $uom) = $this->getItemAttributesGivenQuoteItem(
            $quoteItem
        );

        return $this->isValid($quoteItem, $line, $itemId, $variantId, $uom, $baseUnitOfMeasure);
    }

    /**
     * Convert to current store currency
     *
     * @param $price
     * @param string $currentCurrencyCode
     * @param string $baseCurrencyCode
     * @return float|int
     * @throws NoSuchEntityException|LocalizedException
     */
    public function convertToCurrentStoreCurrency($price, $currentCurrencyCode = null, $baseCurrencyCode = null)
    {
        if (!$currentCurrencyCode) {
            $currentCurrencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        }

        if (!$baseCurrencyCode) {
            $baseCurrencyCode = $this->storeManager->getStore()->getBaseCurrency()->getCode();
        }

        $rate = $this->currencyFactory->create()->load($baseCurrencyCode)->getAnyRate($currentCurrencyCode);

        return $price * $rate;
    }

    /**
     * Convert to base currency
     *
     * @param $price
     * @param string $currentCurrencyCode
     * @param string $baseCurrencyCode
     * @return float|int
     * @throws NoSuchEntityException|LocalizedException
     */
    public function convertToBaseCurrency($price, $currentCurrencyCode = null, $baseCurrencyCode = null)
    {
        if (!$currentCurrencyCode) {
            $currentCurrencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        }

        if (!$baseCurrencyCode) {
            $baseCurrencyCode = $this->storeManager->getStore()->getBaseCurrency()->getCode();
        }

        $rate = $this->currencyFactory->create()->load($currentCurrencyCode)->getAnyRate($baseCurrencyCode);

        return $price * $rate;
    }
}
