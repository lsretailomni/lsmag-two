<?php

namespace Ls\Omni\Block\Product\View\Discount;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ProactiveDiscountType;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class View
 * @package Ls\Omni\Block\Product\View
 */
class Proactive extends \Magento\Catalog\Block\Product\View
{
    /** @var \Ls\Core\Model\LSR */
    public $lsr;

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @var \Ls\Omni\Helper\ItemHelper
     */
    public $itemHelper;

    /**
     * @var
     */
    public $httpContext;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    public $customerFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
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
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
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
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
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
        $this->lsr = $lsr;
        $this->loyaltyHelper = $loyaltyHelper;
        $this->itemHelper = $itemHelper;
        $this->httpContext = $httpContext;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->timeZoneInterface = $timeZoneInterface;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param $sku
     * @return array|\Ls\Omni\Client\Ecommerce\Entity\DiscountsGetResponse|\Ls\Omni\Client\Ecommerce\Entity\ProactiveDiscount[]|\Ls\Omni\Client\ResponseInterface|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProactiveDiscounts($sku)
    {
        $itemId = $sku;
        $storeId = $this->lsr->getDefaultWebStore();
        if ($response = $this->loyaltyHelper->getProactiveDiscounts($itemId, $storeId)) {
            if (!is_array($response)) {
                $response = [$response];
            }
            return $response;
        } else {
            return [];
        }
    }

    /**
     * @param $sku
     * @return array|\Ls\Omni\Client\Ecommerce\Entity\PublishedOffer[]|\Ls\Omni\Client\Ecommerce\Entity\PublishedOffersGetResponse|\Ls\Omni\Client\ResponseInterface|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCoupons($sku)
    {
        $itemId = $sku;
        $storeId = $this->lsr->getDefaultWebStore();
        if ($this->httpContext->getValue(\Ls\Omni\Plugin\App\Action\Context::CONTEXT_CUSTOMER_ID)) {
            $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
            $email = $this->httpContext->getValue(\Ls\Omni\Plugin\App\Action\Context::CONTEXT_CUSTOMER_EMAIL);
            $customer = $this->customerFactory->create()->setWebsiteId($websiteId)->loadByEmail($email);
            $cardId = $customer->getData('lsr_cardid');
            if ($response = $this->loyaltyHelper->getPublishedOffers($itemId, $storeId, $cardId)) {
                if (!is_array($response)) {
                    $response = [$response];
                }
                return $response;
            } else {
                return [];
            }
        }
        return [];
    }

    /**
     * @param $itemId
     * @param \Ls\Omni\Client\Ecommerce\Entity\ProactiveDiscount $discount
     * @return array|string
     */
    // @codingStandardsIgnoreLine
    public function getFormattedDescriptionDiscount(
        $itemId,
        \Ls\Omni\Client\Ecommerce\Entity\ProactiveDiscount $discount
    ) {
        $description = [];
        $discountText = "";
        if ($discount->getDescription()) {
            $description[] = "<span class='discount-description'>" . $discount->getDescription() . "</span>";
        }
        if (floatval($discount->getMinimumQuantity()) > 0 && $discount->getType() == ProactiveDiscountType::MULTIBUY) {
            $description[] = "
                <span class='discount-min-qty-label discount-label'>" . __("Minimum Qty :") . "</span>
                <span class='discount-min-qty-value discount-value'>" .
                number_format(
                    (float)$discount->getMinimumQuantity(),
                    2,
                    '.',
                    ''
                ) . "</span>";
        }

        if (floatval($discount->getPercentage()) > 0) {
            $discountPercentage = number_format((float)$discount->getPercentage(), 2, '.', '');
            $discountText = __("Avail %1 Off ", $discountPercentage . "%") . "";
        }
        if ($discount->getItemIds()) {
            $itemIds = $discount->getItemIds()->getString();
            if (!is_array($itemIds)) {
                $itemIds = [$discount->getItemIds()->getString()];
            }
            $itemIds = array_unique($itemIds);
            $itemIds = array_diff($itemIds, [$itemId]);
            $counter = 0;
            $popupLink = "";
            $popupHtml = "";
            $productsData = [];
            $productHtml = "";
            if(!empty($itemIds)){
                $productsData = $this->itemHelper->getProductsInfoBySku($itemIds);
            }
            foreach ($productsData as $productInfo) {
                if ($this->getMixandMatchProductLimit() == $counter) {
                    break;
                }
                $priceHtml = "";
                if ($counter == 0) {
                    $popupLink = "<a style='cursor:pointer' class='ls-click-product-promotion'
                     data-id='" . $discount->getId() . "'>"
                        . __('Click Here to see the items') . "</a>";
                    $popupHtml = "<div class='ls-discounts-popup-model'
                    id='ls-popup-model-" . $discount->getId() . "' style='display:none;'>";
                }
                $productHtml = "";
                if (!empty($productInfo)) {
                    $imageHtml = parent::getImage(
                        $productInfo,
                        'product_small_image'
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
                            $productHtml .= "<div class='item-popup'>";
                            $productHtml .= "<a  href = '" . $productInfo->getProductUrl() . "' class='product-link'
                             target='_blank'>" . $imageHtml .
                                "<div class='title'>" . $productName . "</div>";
                            $productHtml .= "</a>";
                            $productHtml .= $priceHtml."</div>";
                            $productData[] = $productHtml;
                        }
                    }
                }

                $counter++;
            }
            if (!empty($discountText)) {
                $discountText .= __("if Buy with any of these items: " . $popupLink);
            } else {
                $discountText .= $popupLink;
            }
            if ($this->getMixandMatchProductLimit() != 0) {
                $description[] = $discountText;
                $description[] = implode(" ", $productData);
                $description[] = "</div>";
            }
        } else {
            if (!empty($discountText)) {
                $description[] = $discountText . "</span>";
            }
        }
        $description = implode("<br/>", $description);
        return $description;
    }

    /**
     * @param \Ls\Omni\Client\Ecommerce\Entity\PublishedOffer $coupon
     * @return array|string
     */
    public function getFormattedDescriptionCoupon(\Ls\Omni\Client\Ecommerce\Entity\PublishedOffer $coupon)
    {
        $description = [];
        if ($coupon->getDescription()) {
            $description[] = "<span class='coupon-description'>" . $coupon->getDescription() . "</span>";
        }
        if ($coupon->getDetails()) {
            $description[] = "<span class='coupon-details'>" . $coupon->getDetails() . "</span>";
        }
        if ($coupon->getExpirationDate()) {
            $description[] = "
        <span class='coupon-expiration-date-label discount-label'>" . __("Expiry :") . "</span>
        <span class='coupon-expiration-date-value discount-value'>" .
                $this->getFormattedOfferExpiryDate($coupon->getExpirationDate()) . "</span>";
        }
        if ($coupon->getOfferId()) {
            $description[] = "
        <span class='coupon-offer-id-label discount-label'>" . __("Coupon Code :") . "</span>
        <span class='coupon-offer-id-value discount-value'>" . $coupon->getOfferId() . "</span>";
        }
        $description = implode("<br/>", $description);
        return $description;
    }

    /**
     * @param $date
     * @return string
     */
    public function getFormattedOfferExpiryDate($date)
    {
        try {
            $offerExpiryDate = $this->timeZoneInterface->date($date)->format($this->scopeConfig->getValue(
                LSR::SC_LOYALTY_EXPIRY_DATE_FORMAT,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            ));

            return $offerExpiryDate;
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
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

    /**
     * @return string
     */
    public function getMixandMatchProductLimit()
    {
        return $this->lsr->getStoreConfig(LSR::LS_DISCOUNT_MIXANDMATCH_LIMIT);
    }
}
