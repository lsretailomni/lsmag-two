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
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
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
     * ItemHelper constructor.
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
        Quote $quoteResourceModel
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
        $check             = false;
        $baseUnitOfMeasure = "";
        $discountInfo      = $orderLines = $discountsLines = [];
        $discountText      = __("Save");

        try {
            if ($type == 2) {
                $itemId      = $item->getItemId();
                $variantId   = $item->getVariantId();
                $uom         = $item->getUomId();
                $customPrice = $item->getDiscountAmount();
            } else {
                $baseUnitOfMeasure = $item->getProduct()->getData('uom');
                list($itemId, $variantId, $uom) = $this->getComparisonValues(
                    $item->getProductId(),
                    $item->getSku()
                );
                $customPrice = $item->getCustomPrice();
            }

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
                            $check = true;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        if ($check == true) {
            return [implode($discountInfo), $discountText];
        } else {
            return null;
        }
    }

    /**
     * This function is overriding in hospitality module
     *
     * Compare one_list lines with quote_item items and set correct prices
     *
     * @param $quote
     * @param Order $basketData
     */
    public function setDiscountedPricesForItems($quote, $basketData)
    {
        try {
            $orderLines    = [];
            $quoteItemList = $quote->getAllVisibleItems();

            if (count($quoteItemList) && !empty($basketData)) {
                $orderLines = $basketData->getOrderLines()->getOrderLine();
            }

            foreach ($quoteItemList as $quoteItem) {
                $baseUnitOfMeasure = $quoteItem->getProduct()->getData('uom');
                list($itemId, $variantId, $uom) = $this->getComparisonValues(
                    $quoteItem->getProductId(),
                    $quoteItem->getSku()
                );

                foreach ($orderLines as $index => $line) {
                    if ($this->isValid($line, $itemId, $variantId, $uom, $baseUnitOfMeasure)) {
                        $unitPrice = $line->getAmount() / $line->getQuantity();
                        if ($line->getDiscountAmount() > 0) {
                            $quoteItem->setCustomPrice($unitPrice);
                            $quoteItem->setDiscountAmount($line->getDiscountAmount());
                            $quoteItem->setOriginalCustomPrice($unitPrice);
                        } elseif ($line->getAmount() != $quoteItem->getProduct()->getPrice()) {
                            $quoteItem->setCustomPrice($unitPrice);
                            $quoteItem->setOriginalCustomPrice($unitPrice);
                        } else {
                            $quoteItem->setCustomPrice(null);
                            $quoteItem->setDiscountAmount(null);
                            $quoteItem->setOriginalCustomPrice(null);
                        }
                        $quoteItem->setTaxAmount($line->getTaxAmount())
                            ->setBaseTaxAmount($line->getTaxAmount())
                            ->setPriceInclTax($unitPrice)
                            ->setBasePriceInclTax($unitPrice)
                            ->setRowTotal($line->getNetAmount())
                            ->setBaseRowTotal($line->getNetAmount())
                            ->setRowTotalInclTax($line->getAmount())
                            ->setBaseRowTotalInclTax($line->getAmount());
                        unset($orderLines[$index]);
                        break;
                    }
                }
                $quoteItem->getProduct()->setIsSuperMode(true);
                // @codingStandardsIgnoreLine
                $this->itemResourceModel->save($quoteItem);
            }

            if ($quote->getId()) {
                $cartQuote = $this->cart->getQuote();

                if (isset($basketData)) {
                    $pointDiscount  = $cartQuote->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
                    $giftCardAmount = $cartQuote->getLsGiftCardAmountUsed();
                    $cartQuote->getShippingAddress()->setGrandTotal(
                        $basketData->getTotalAmount() - $giftCardAmount - $pointDiscount
                    );
                }
                $couponCode = $this->checkoutSession->getCouponCode();
                $cartQuote->setCouponCode($couponCode);
                $cartQuote->getShippingAddress()->setCouponCode($couponCode);
                $cartQuote->setTotalsCollectedFlag(false)->collectTotals();
                $this->quoteResourceModel->save($cartQuote);
            }
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * @param $items
     * @return array|ProductSearchResultsInterface
     */
    public function getProductsInfoBySku($items)
    {
        $productData = [];
        try {
            $criteria = $this->searchCriteriaBuilder->addFilter('sku', implode(",", $items), 'in')->create();
            $product  = $this->productRepository->getList($criteria);
            return $product->getItems();
        } catch (Exception $e) {
            $this->_logger->debug($e->getMessage());
        }
        return $productData;
    }

    /**
     * Get comparison values
     *
     * @param $productId
     * @param $sku
     * @return array
     * @throws NoSuchEntityException
     */
    public function getComparisonValues($productId, $sku)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('sku', $sku, 'eq')->create();
        $productList    = $this->productRepository->getList($searchCriteria)->getItems();
        /** @var Product $product */
        $product   = array_pop($productList);
        $itemId    = $this->productRepository->getById($productId, false, null, true)->getSku();
        $variantId = $product->getData(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE);
        $uom       = $product->getData('uom');
        $barCode   = $product->getData('barcode');
        $uomQty    = $product->getData(LSR::LS_UOM_ATTRIBUTE_QTY);

        return [$itemId, $variantId, $uom, $barCode, $uomQty];
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
