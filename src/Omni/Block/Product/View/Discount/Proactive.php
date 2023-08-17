<?php

namespace Ls\Omni\Block\Product\View\Discount;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\DiscountsGetResponse;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DiscountType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ProactiveDiscountType;
use \Ls\Omni\Client\Ecommerce\Entity\ProactiveDiscount;
use \Ls\Omni\Client\Ecommerce\Entity\PublishedOffer;
use \Ls\Omni\Client\ResponseInterface;
use Ls\Omni\Helper\ContactHelper;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\View;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Proactive extends Template
{
    /** @var LSR */
    public $lsr;

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @var ItemHelper
     */
    public $itemHelper;

    /**
     * @var CustomerFactory
     */
    public $customerFactory;

    /** @var StoreManagerInterface */
    public $storeManager;

    /**
     * @var TimezoneInterface
     */
    public $timeZoneInterface;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var UrlInterface
     */
    public $urlBuilder;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var Data
     */
    public $catalogHelper;

    /**
     * @var null
     */
    public $product = null;

    /**
     * @var View
     */
    public $productBlock;

    /**
     * @var ContactHelper
     */
    public $contactHelper;

    /**
     * @param Template\Context $context
     * @param LSR $lsr
     * @param LoyaltyHelper $loyaltyHelper
     * @param ItemHelper $itemHelper
     * @param ContactHelper $contactHelper
     * @param CustomerFactory $customerFactory
     * @param StoreManagerInterface $storeManager
     * @param TimezoneInterface $timeZoneInterface
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $urlBuilder
     * @param LoggerInterface $logger
     * @param Data $catalogHelper
     * @param View $productBlock
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        LSR $lsr,
        LoyaltyHelper $loyaltyHelper,
        ItemHelper $itemHelper,
        ContactHelper $contactHelper,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        TimezoneInterface $timeZoneInterface,
        ScopeConfigInterface $scopeConfig,
        UrlInterface $urlBuilder,
        LoggerInterface $logger,
        Data $catalogHelper,
        View $productBlock,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->lsr               = $lsr;
        $this->loyaltyHelper     = $loyaltyHelper;
        $this->itemHelper        = $itemHelper;
        $this->contactHelper     = $contactHelper;
        $this->customerFactory   = $customerFactory;
        $this->storeManager      = $storeManager;
        $this->timeZoneInterface = $timeZoneInterface;
        $this->scopeConfig       = $scopeConfig;
        $this->urlBuilder        = $urlBuilder;
        $this->logger            = $logger;
        $this->catalogHelper     = $catalogHelper;
        $this->productBlock      = $productBlock;
    }

    /**
     * Get proactive discounts
     *
     * @param string $itemId
     * @return array|bool|DiscountsGetResponse|ProactiveDiscount[]|ResponseInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getProactiveDiscounts($itemId)
    {
        $webStore = $this->lsr->getActiveWebStore();
        if ($response = $this->loyaltyHelper->getProactiveDiscounts($itemId, $webStore)) {
            if (!is_array($response)) {
                $response = [$response];
            }
            $tempArray = [];
            foreach ($response as $key => $responseData) {
                $uniqueKey = $responseData->getId();
                if (!in_array($uniqueKey, $tempArray, true)) {
                    $tempArray[] = $uniqueKey;
                    continue;
                }
                unset($response[$key]);
            }

            return $response;
        }

        return [];
    }

    /**
     * Get all coupons
     *
     * @param $itemId
     * @return array
     */
    public function getCoupons($itemId)
    {
        try {
            $storeId = $this->lsr->getActiveWebStore();
            if (!empty($this->contactHelper->getCardIdFromCustomerSession())) {
                $cardId    = $this->contactHelper->getCardIdFromCustomerSession();
                $response = [];

                foreach ($itemId as $id) {
                    $publishedOffers = $this->loyaltyHelper->getPublishedOffers($cardId, $storeId, $id);

                    foreach ($publishedOffers as $publishedOffer) {
                        $response[$publishedOffer->getOfferId()] = $publishedOffer;
                    }
                }

                return $response;
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return [];
    }

    /**
     * Get formatted description for discount
     *
     * @param $itemId
     * @param ProactiveDiscount $discount
     * @return string
     * @throws NoSuchEntityException
     */
    // @codingStandardsIgnoreLine
    public function getFormattedDescriptionDiscount(
        $itemId,
        ProactiveDiscount $discount
    ) {
        $description  = [];
        $discountText = '';

        if ($discount->getDescription()) {
            $description[] = "<span class='discount-description'>" . $discount->getDescription() . '</span>';
        }

        if (floatval($discount->getMinimumQuantity()) > 0 && $discount->getType() == ProactiveDiscountType::MULTIBUY) {
            $description[] = "
                <span class='discount-min-qty-label discount-label'>" . __('Minimum Qty :') . "</span>
                <span class='discount-min-qty-value discount-value'>" .
                number_format(
                    (float)$discount->getMinimumQuantity(),
                    2,
                    '.',
                    ''
                ) . '</span>';
        }

        if (floatval($discount->getPercentage()) > 0) {
            $discountPercentage = number_format((float)$discount->getPercentage(), 2, '.', '');
            $discountText       = __('Avail %1 Off ', $discountPercentage . '%') . '';
        }
        if ($discount->getItemIds()) {
            $itemIds = $discount->getItemIds()->getString();

            if (!is_array($itemIds)) {
                $itemIds = [$discount->getItemIds()->getString()];
            }
            $itemIds      = array_unique($itemIds);

            if (!is_array($itemId)) {
                $itemIds      = array_diff($itemIds, [$itemId]);
            }

            $counter      = 0;
            $popupLink    = '';
            $popupHtml    = '';
            $productsData = [];
            $productHtml  = '';
            if (!empty($itemIds)) {
                $productsData = $this->itemHelper->getProductsInfoByItemIds($itemIds);
            }
            foreach ($productsData as $productInfo) {
                if ($this->getMixandMatchProductLimit() == $counter) {
                    break;
                }
                $priceHtml = '';
                if ($counter == 0) {
                    $popupLink = "<a style='cursor:pointer' class='ls-click-product-promotion'
                     data-id='" . $discount->getId() . "'>"
                        . __('Click Here to see the items') . "</a>";
                    $popupHtml = "<div class='ls-discounts-popup-model'
                    id='ls-popup-model-" . $discount->getId() . "' style='display:none;'>";
                }
                $productHtml = '';
                if (!empty($productInfo)) {
                    $imageHtml = $this->productBlock->getImage(
                        $productInfo,
                        'product_base_image'
                    )
                        ->toHtml();
                    if (!empty($productInfo->getFinalPrice())) {
                        $priceHtml = $this->productBlock->getProductPrice($productInfo);
                    }
                    if (!empty($productInfo->getProductUrl())) {
                        if (!empty($productInfo->getName())) {
                            $productName = $productInfo->getName();
                            if ($counter == 0) {
                                $productHtml = $popupHtml;
                            }
                            $productHtml   .= "<div class='item-popup'>";
                            $productHtml   .= "<a  href = '" . $productInfo->getProductUrl() . "' class='product-link'
                             target='_blank'>" . $imageHtml .
                                "<div class='title'>" . $productName . '</div>';
                            $productHtml   .= '</a>';
                            $productHtml   .= $priceHtml . '</div>';
                            $productData[] = $productHtml;
                        }
                    }
                }
                $counter++;
            }
            if (!empty($discountText)) {
                $discountText .= __('if Buy with any of these items: ') . $popupLink;
            } else {
                $discountText .= $popupLink;
            }
            if ($this->getMixandMatchProductLimit() != 0) {
                $description[] = $discountText;
                if (!empty($productsData)) {
                    $description[] = implode(' ', $productData);
                    $description[] = '</div>';
                }
            }
        } else {
            if (!empty($discountText)) {
                $description[] = $discountText . "</span>";
            }
        }

        return implode('<br/>', $description);
    }

    /**
     * Get mix and match product limit
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getMixandMatchProductLimit()
    {
        return $this->lsr->getStoreConfig(LSR::LS_DISCOUNT_MIXANDMATCH_LIMIT, $this->lsr->getCurrentStoreId());
    }

    /**
     * Get formatted description for coupon
     *
     * @param PublishedOffer $coupon
     * @return string
     */
    public function getFormattedDescriptionCoupon(PublishedOffer $coupon)
    {
        $description = [];
        if ($coupon->getDescription()) {
            $description[] = "<span class='coupon-description'>" . $coupon->getDescription() . '</span>';
        }
        if ($coupon->getDetails()) {
            $description[] = "<span class='coupon-details'>" . $coupon->getDetails() . '</span>';
        }
        if ($coupon->getCode() != DiscountType::PROMOTION) {
            if ($coupon->getExpirationDate()) {
                $description[] = "
        <span class='coupon-expiration-date-label discount-label'>" . __('Expiry :') . "</span>
        <span class='coupon-expiration-date-value discount-value'>" .
                    $this->getFormattedOfferExpiryDate($coupon->getExpirationDate()) . '</span>';
            }
            if ($coupon->getOfferId()) {
                $description[] = "
        <span class='coupon-offer-id-label discount-label'>" . __('Coupon Code :') . "</span>
        <span class='coupon-offer-id-value discount-value'>" . $coupon->getOfferId() . '</span>';
            }
        }

        return implode('<br/>', $description);
    }

    /**
     * Get formatted expiry date
     *
     * @param $date
     * @return string
     */
    public function getFormattedOfferExpiryDate($date)
    {
        try {
            return $this->timeZoneInterface->date($date)->format($this->scopeConfig->getValue(
                LSR::SC_LOYALTY_EXPIRY_DATE_FORMAT,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $this->lsr->getActiveWebStore()
            ));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return null;
    }

    /**
     * Get ajax url
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->urlBuilder->getUrl('omni/ajax/ProactiveDiscountsAndCoupons', []);
    }

    /**
     * Get current product sku
     *
     * @return string|null
     */
    public function getProductSku()
    {
        $currentProduct = $this->getProduct();
        if (empty($currentProduct) || !$currentProduct->getId()) {
            return null;
        }
        return $currentProduct->getSku();
    }

    /**
     * Check if discount block is enabled in the config
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function isDiscountEnable()
    {
        return $this->lsr->getStoreConfig(
            LSR::LS_DISCOUNT_SHOW_ON_PRODUCT,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * Get current product
     *
     * @return Product|null
     */
    public function getProduct()
    {
        if ($this->product === null) {
            $this->product = $this->catalogHelper->getProduct();
        }

        return $this->product;
    }

    /**
     * Check if commerce service is responding
     *
     * @return bool|null
     * @throws NoSuchEntityException
     */
    public function isValid()
    {
        return $this->lsr->isLSR($this->lsr->getCurrentStoreId());
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
        return $this->itemHelper->getLsCentralItemIdBySku($sku);
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
        return $this->itemHelper->getProductGivenSku($sku);
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
        return $this->itemHelper->getLinkedProductsItemIds($bundleProduct);
    }
}
