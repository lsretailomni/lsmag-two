<?php

namespace Ls\Omni\Block\Product\View\Discount;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\DiscountsGetResponse;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DiscountType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ProactiveDiscountType;
use \Ls\Omni\Client\Ecommerce\Entity\ProactiveDiscount;
use \Ls\Omni\Client\Ecommerce\Entity\PublishedOffer;
use \Ls\Omni\Client\Ecommerce\Entity\PublishedOffersGetByCardIdResponse;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Omni\Plugin\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\View;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session\Proxy;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class View
 * @package Ls\Omni\Block\Product\View
 */
class Proactive extends View
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
     * @var
     */
    public $httpContext;

    /**
     * @var CustomerFactory
     */
    public $customerFactory;

    /** @var StoreManagerInterface */
    public $storeManager;

    /**
     * @var Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public $timeZoneInterface;

    /**
     * @var Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * Proactive constructor.
     * @param LSR $lsr
     * @param LoyaltyHelper $loyaltyHelper
     * @param ItemHelper $itemHelper
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param EncoderInterface $jsonEncoder
     * @param StringUtils $string
     * @param Product $productHelper
     * @param ConfigInterface $productTypeConfig
     * @param FormatInterface $localeFormat
     * @param Proxy $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param CustomerFactory $customerFactory
     * @param StoreManagerInterface $storeManager
     * @param TimezoneInterface $timeZoneInterface
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        LSR $lsr,
        LoyaltyHelper $loyaltyHelper,
        ItemHelper $itemHelper,
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        EncoderInterface $jsonEncoder,
        StringUtils $string,
        Product $productHelper,
        ConfigInterface $productTypeConfig,
        FormatInterface $localeFormat,
        Proxy $customerSession,
        ProductRepositoryInterface $productRepository,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\App\Http\Context $httpContext,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        TimezoneInterface $timeZoneInterface,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $urlEncoder,
            $jsonEncoder,
            $string,
            $productHelper,
            $productTypeConfig,
            $localeFormat,
            $customerSession,
            $productRepository,
            $priceCurrency,
            $data
        );
        $this->lsr               = $lsr;
        $this->loyaltyHelper     = $loyaltyHelper;
        $this->itemHelper        = $itemHelper;
        $this->httpContext       = $httpContext;
        $this->customerFactory   = $customerFactory;
        $this->storeManager      = $storeManager;
        $this->timeZoneInterface = $timeZoneInterface;
        $this->scopeConfig       = $scopeConfig;
    }

    /**
     * @param $sku
     * @return array|DiscountsGetResponse|ProactiveDiscount[]|ResponseInterface|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getProactiveDiscounts($sku)
    {
        $itemId  = $sku;
        $storeId = $this->lsr->getDefaultWebStore();
        if ($response = $this->loyaltyHelper->getProactiveDiscounts($itemId, $storeId)) {
            if (!is_array($response)) {
                $response = [$response];
            }
            $tempArray = [];
            foreach ($response as $key => $responseData) {
                $uniqueKey = $responseData->getPercentage() . '|' . $responseData->getItemId() . '|'
                    . $responseData->getPopUpLine1() . '|' . $responseData->getType();
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
     * @param $sku
     * @return array|PublishedOffer[]|PublishedOffersGetByCardIdResponse|ResponseInterface|null
     */
    public function getCoupons($sku)
    {
        $itemId = $sku;
        try {
            $storeId = $this->lsr->getDefaultWebStore();
            if ($this->httpContext->getValue(Context::CONTEXT_CUSTOMER_ID)) {
                $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
                $email     = $this->httpContext->getValue(Context::CONTEXT_CUSTOMER_EMAIL);
                $customer  = $this->customerFactory->create()->setWebsiteId($websiteId)->loadByEmail($email);
                $cardId    = $customer->getData('lsr_cardid');
                if ($response = $this->loyaltyHelper->getPublishedOffers($itemId, $storeId, $cardId)) {
                    return $response;
                }
                return [];
            }
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return [];
    }

    /**
     * @param $itemId
     * @param ProactiveDiscount $discount
     * @return array|string
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
            $itemIds      = array_diff($itemIds, [$itemId]);
            $counter      = 0;
            $popupLink    = '';
            $popupHtml    = '';
            $productsData = [];
            $productHtml  = '';
            if (!empty($itemIds)) {
                $productsData = $this->itemHelper->getProductsInfoBySku($itemIds);
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
                    $imageHtml = parent::getImage(
                        $productInfo,
                        'product_base_image'
                    )
                        ->toHtml();
                    if (!empty($productInfo->getFinalPrice())) {
                        $priceHtml = parent::getProductPrice($productInfo);
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
        $description = implode('<br/>', $description);
        return $description;
    }

    /**
     * @return string
     */
    public function getMixandMatchProductLimit()
    {
        return $this->lsr->getStoreConfig(LSR::LS_DISCOUNT_MIXANDMATCH_LIMIT);
    }

    /**
     * @param PublishedOffer $coupon
     * @return array|string
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
        $description = implode('<br/>', $description);
        return $description;
    }

    /**
     * @param $date
     * @return string
     */
    public function getFormattedOfferExpiryDate($date)
    {
        $offerExpiryDate = null;
        try {
            $offerExpiryDate = $this->timeZoneInterface->date($date)->format($this->scopeConfig->getValue(
                LSR::SC_LOYALTY_EXPIRY_DATE_FORMAT,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            ));

            return $offerExpiryDate;
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return null;
    }

    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('omni/ajax/ProactiveDiscountsAndCoupons');
    }

    /**
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
     * @return string
     */
    public function isDiscountEnable()
    {
        return $this->lsr->getStoreConfig(LSR::LS_DISCOUNT_SHOW_ON_PRODUCT);
    }
}
