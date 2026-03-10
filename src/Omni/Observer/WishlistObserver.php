<?php

namespace Ls\Omni\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\OneListItem;
use \Ls\Omni\Client\Ecommerce\Entity\OneListItemModify;
use \Ls\Omni\Client\Ecommerce\Operation\OneListItemModify as OneListItemModifyOperation;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Wishlist\Model\Wishlist;

/**
 * This observer is responsible for wishlist sync
 */
class WishlistObserver implements ObserverInterface
{
    /** @var BasketHelper */
    private $basketHelper;

    /** @var CustomerSession $customerSession */
    private $customerSession;

    /**
     * @var LSR
     */
    private $lsr;

    /**
     * @var Wishlist
     */
    private $wishlist;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param BasketHelper $basketHelper
     * @param CustomerSession $customerSession
     * @param LSR $LSR
     * @param Wishlist $wishlist
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        BasketHelper $basketHelper,
        CustomerSession $customerSession,
        LSR $LSR,
        Wishlist $wishlist,
        ProductRepositoryInterface $productRepository
    ) {
        $this->basketHelper    = $basketHelper;
        $this->customerSession = $customerSession;
        $this->lsr             = $LSR;
        $this->wishlist        = $wishlist;
        $this->productRepository = $productRepository;
    }

    /**
     * Entry point for the observer
     *
     * @param Observer $observer
     * @return $this
     * @throws NoSuchEntityException|InvalidEnumException
     */
    public function execute(Observer $observer)
    {
        if ($this->lsr->isLSR(
            $this->lsr->getCurrentStoreId(),
            false,
            $this->lsr->getOrderIntegrationOnFrontend()
        )) {
            $session  = $this->customerSession;
            $data     = empty($session->getBeforeWishlistUrl())
                ? $observer->getRequest()->getParams() : null;

            $productId  = isset($data['product']) ? (int)$data['product'] : null;
            $qty        = $data['qty'] ?? 1;
            $customerId = $this->customerSession->getCustomer()->getId();
            $wishlist   = $this->wishlist->loadByCustomerId($customerId)->getItemCollection();
            $oneList    = $this->basketHelper->fetchCurrentCustomerWishlist();
            $oneListItems = $this->basketHelper->getWishListFromCustomerSession()
                ? $this->basketHelper->getWishListFromCustomerSession()->getItems()
                : [];

            if (!$oneList->getId()) {
                $oneList = $this->basketHelper->addProductToExistingWishlist($oneList, $wishlist);
                $this->basketHelper->updateWishlistAtOmni($oneList);
            } elseif ($productId) {
                $oneList = $this->handleSingleProductAdd($productId, $qty, $oneList, $oneListItems);
            } elseif (is_array($qty)) {
                $oneList = $this->handleQtyUpdate($qty, $wishlist, $oneList, $oneListItems);
            } else {
                $oneList = $this->handleRemovedItems($wishlist, $oneList, $oneListItems);
            }
        }

        return $this;
    }

    /**
     * Handle adding a single product to the wishlist
     *
     * @param int $productId
     * @param float $qty
     * @param mixed $oneList
     * @param array $oneListItems
     * @return mixed
     * @throws NoSuchEntityException
     */
    private function handleSingleProductAdd($productId, $qty, $oneList, $oneListItems)
    {
        try {
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            return $oneList;
        }

        [$itemId, $variantId, $uom, $barCode] = $this->getComparisonValuesForProduct($product);
        $found    = $this->findInOneListItems($oneListItems, $itemId, $variantId);
        $listItem = $this->buildOneListItem($itemId, $variantId, $uom, $barCode, $qty);

        if ($found) {
            $listItem->setLineNumber($found->getLineNumber());
        }

        return $this->executeOneListItemModify($listItem, $oneList, false) ?? $oneList;
    }

    /**
     * Handle updating quantities for multiple wishlist items
     *
     * @param array $qty
     * @param mixed $wishlist
     * @param mixed $oneList
     * @param array $oneListItems
     * @return mixed
     * @throws NoSuchEntityException
     */
    private function handleQtyUpdate($qty, $wishlist, $oneList, $oneListItems)
    {
        foreach ($qty as $key => $value) {
            $product = null;
            foreach ($wishlist->getItems() as $wishListItem) {
                if ($wishListItem->getId() == $key) {
                    $product = $wishListItem->getProduct();
                    break;
                }
            }

            if (!$product) {
                continue;
            }

            [$itemId, $variantId, $uom, $barCode] = $this->getComparisonValuesForProduct($product);
            $found    = $this->findInOneListItems($oneListItems, $itemId, $variantId);
            $listItem = $this->buildOneListItem($itemId, $variantId, $uom, $barCode, $value);

            if ($found) {
                $listItem->setLineNumber($found->getLineNumber());
            }

            $oneList = $this->executeOneListItemModify($listItem, $oneList, false) ?? $oneList;
        }

        return $oneList;
    }

    /**
     * Handle removal of items no longer in the wishlist
     *
     * @param mixed $wishlist
     * @param mixed $oneList
     * @param array $oneListItems
     * @return mixed
     */
    private function handleRemovedItems($wishlist, $oneList, $oneListItems)
    {
        $finalWishlistItems = $wishlist->getItems();

        foreach ($oneListItems as $oneListItem) {
            $currentItemId    = $oneListItem->getItemId();
            $currentVariantId = $oneListItem->getVariantId();
            $found            = false;

            foreach ($finalWishlistItems as $finalWishlistItem) {
                $finalItemId    = $finalWishlistItem->getProduct()->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE);
                $finalVariantId = $finalWishlistItem->getProduct()->getData(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE);

                if ($currentItemId == $finalItemId && $currentVariantId == $finalVariantId) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $oneList = $this->executeOneListItemModify($oneListItem, $oneList, true) ?? $oneList;
            }
        }

        return $oneList;
    }

    /**
     * Execute OneListItemModify operation and update session
     *
     * @param OneListItem $listItem
     * @param mixed $oneList
     * @param bool $remove
     * @return \Ls\Omni\Client\Ecommerce\Entity\OneList|null
     */
    private function executeOneListItemModify($listItem, $oneList, bool $remove)
    {
        // @codingStandardsIgnoreLine
        $operation = new OneListItemModifyOperation();
        // @codingStandardsIgnoreLine
        $request = (new OneListItemModify())
            ->setItem($listItem)
            ->setOneListId($oneList->getId())
            ->setRemove($remove)
            ->setCalculate(true)
            ->setCardId($this->getCardId());

        if ($oneList = $operation->execute($request)->getOneListItemModifyResult()) {
            $this->basketHelper->setWishListInCustomerSession($oneList);
            return $oneList;
        }

        return null;
    }

    /**
     * Build a OneListItem object
     *
     * @param string $itemId
     * @param string|null $variantId
     * @param string|null $uom
     * @param string|null $barCode
     * @param float $qty
     * @return OneListItem
     */
    private function buildOneListItem($itemId, $variantId, $uom, $barCode, $qty)
    {
        // @codingStandardsIgnoreLine
        return (new OneListItem())
            ->setQuantity($qty)
            ->setItemId($itemId)
            ->setId('')
            ->setBarcodeId($barCode)
            ->setVariantId($variantId)
            ->setUnitOfMeasureId($uom);
    }

    /**
     * Get card ID from customer session
     *
     * @return string
     */
    private function getCardId()
    {
        return $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID) ?? '';
    }

    /**
     * Get comparison values from product SKU
     *
     * @param mixed $product
     * @return array
     * @throws NoSuchEntityException
     */
    private function getComparisonValuesForProduct($product)
    {
        return $this->basketHelper->itemHelper->getComparisonValues($product->getSku());
    }

    /**
     * Find an item in the one list items by itemId and variantId
     *
     * @param array $oneListItems
     * @param string $itemId
     * @param string|null $variantId
     * @return mixed|false
     */
    private function findInOneListItems($oneListItems, $itemId, $variantId)
    {
        foreach ($oneListItems as $oneListItem) {
            if ($oneListItem->getItemId() == $itemId && $oneListItem->getVariantId() == $variantId) {
                return $oneListItem;
            }
        }

        return false;
    }
}
