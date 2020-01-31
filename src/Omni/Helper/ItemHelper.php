<?php

namespace Ls\Omni\Helper;

use Exception;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\ItemGetByIdResponse;
use \Ls\Omni\Client\Ecommerce\Entity\Order;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Client\Ecommerce\Entity\VariantRegistration;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Replication\Model\ReplBarcodeRepository;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Quote\Model\ResourceModel\Quote\Item;

/**
 * Class ItemHelper
 * @package Ls\Omni\Helper
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
        return (new Entity\LoyItem)
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
     * @param $item
     * @param Order|SalesEntry $orderData
     * @return array|null
     */
    // @codingStandardsIgnoreLine
    public function getOrderDiscountLinesForItem($item, $orderData, $type = 1)
    {
        try {
            $discountInfo = [];
            $customPrice  = 0;
            if ($type == 2) {
                $itemSku = $item->getItemId();
                $itemSku = explode("-", $itemSku);
                if (count($itemSku) < 2) {
                    $itemSku[1] = $item->getVariantId();
                }
                $customPrice = $item->getDiscountAmount();
            } else {
                $itemSku = $item->getSku();
                $itemSku = explode("-", $itemSku);
                if (count($itemSku) < 2) {
                    $itemSku[1] = '';
                }
                $customPrice = $item->getCustomPrice();
            }
            $check        = false;
            $basketData   = [];
            $discountText = __("Save");
            if ($orderData instanceof SalesEntry) {
                $basketData     = $orderData->getLines();
                $discountsLines = $orderData->getDiscountLines();
            } elseif ($orderData instanceof Order) {
                $basketData     = $orderData->getOrderLines();
                $discountsLines = $orderData->getOrderDiscountLines()->getOrderDiscountLine();
            }
            foreach ($basketData as $basket) {
                if ($basket->getItemId() == $itemSku[0] && $basket->getVariantId() == $itemSku[1]) {
                    if ($customPrice > 0 && $customPrice != null) {
                        // @codingStandardsIgnoreLine
                        foreach ($discountsLines as $orderDiscountLine) {
                            if ($basket->getLineNumber() == $orderDiscountLine->getLineNumber()) {
                                if (!in_array($orderDiscountLine->getDescription() . '<br />', $discountInfo)) {
                                    $discountInfo[] = $orderDiscountLine->getDescription() . '<br />';
                                }
                            }
                            $check = true;
                        }
                    }
                }
            }
            if ($check == true) {
                return [implode($discountInfo), $discountText];
            } else {
                return null;
            }
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * @param $quote
     * @param Order $basketData
     */
    public function setDiscountedPricesForItems($quote, $basketData)
    {
        try {
            $itemlist = $this->cart->getQuote()->getAllVisibleItems();
            foreach ($itemlist as $item) {
                $orderLines     = $basketData->getOrderLines()->getOrderLine();
                $oldItemVariant = [];
                $itemSku        = explode("-", $item->getSku());
                // @codingStandardsIgnoreLine
                if (count($itemSku) < 2) {
                    $itemSku[1] = null;
                }
                if (is_array($orderLines)) {
                    foreach ($orderLines as $line) {
                        if ($itemSku[0] == $line->getItemId() && $itemSku[1] == $line->getVariantId()) {
                            if (!empty($oldItemVariant[$line->getItemId()][$line->getVariantId()]['Amount'])) {
                                // @codingStandardsIgnoreLine
                                $item->setCustomPrice($oldItemVariant[$line->getItemId()][$line->getVariantId()]['Amount'] + $line->getAmount());
                                $item->setDiscountAmount(
                                // @codingStandardsIgnoreLine
                                    $oldItemVariant[$line->getItemId()][$line->getVariantId()]['Discount'] + $line->getDiscountAmount()
                                );
                                $item->setOriginalCustomPrice($line->getPrice());
                            } else {
                                if ($line->getDiscountAmount() > 0) {
                                    $item->setCustomPrice($line->getAmount());
                                    $item->setDiscountAmount($line->getDiscountAmount());
                                    $item->setOriginalCustomPrice($line->getPrice());
                                } elseif ($line->getAmount() != $item->getProduct()->getPrice()) {
                                    $item->setCustomPrice($line->getAmount());
                                    $item->setOriginalCustomPrice($line->getPrice());
                                } else {
                                    $item->setCustomPrice(null);
                                    $item->setDiscountAmount(null);
                                    $item->setOriginalCustomPrice(null);
                                }
                            }
                        }
                        // @codingStandardsIgnoreStart
                        if (!empty($oldItemVariant[$line->getItemId()][$line->getVariantId()]['Amount'])) {
                            $oldItemVariant[$line->getItemId()][$line->getVariantId()]['Amount']    =
                                $oldItemVariant[$line->getItemId()][$line->getVariantId()]['Amount'] + $line->getAmount();
                            $oldItemVariant[$line->getItemId()][$line->getVariantId()] ['Discount'] =
                                $oldItemVariant[$line->getItemId()][$line->getVariantId()]['Discount'] + $line->getDiscountAmount();
                        } else {

                            $oldItemVariant[$line->getItemId()][$line->getVariantId()]['Amount']   = $line->getAmount();
                            $oldItemVariant[$line->getItemId()][$line->getVariantId()]['Discount'] = $line->getDiscountAmount();
                        }
                        // @codingStandardsIgnoreEnd
                    }
                } else {
                    if ($orderLines->getDiscountAmount() > 0) {
                        $item->setCustomPrice($orderLines->getAmount());
                        $item->setDiscountAmount($orderLines->getDiscountAmount());
                        $item->setOriginalCustomPrice($orderLines->getPrice());
                    } else {
                        $item->setCustomPrice(null);
                        $item->setDiscountAmount(null);
                        $item->setOriginalCustomPrice(null);
                    }
                }
                $item->getProduct()->setIsSuperMode(true);
                $item->calcRowTotal();
                // @codingStandardsIgnoreLine
                $this->itemResourceModel->save($item);
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
                $cartQuote->collectTotals();
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
}
