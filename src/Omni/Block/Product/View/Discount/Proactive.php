<?php
namespace Ls\Omni\Block\Product\View\Discount;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\LoyaltyHelper;
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
        $this->httpContext = $httpContext;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->timeZoneInterface = $timeZoneInterface;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return array|\Ls\Omni\Client\Ecommerce\Entity\DiscountsGetResponse|\Ls\Omni\Client\Ecommerce\Entity\ProactiveDiscount[]|\Ls\Omni\Client\ResponseInterface|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProactiveDiscounts()
    {
        $currentProduct = $this->getProduct();
        $itemId = $currentProduct->getSku();
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
     * @return array|\Ls\Omni\Client\Ecommerce\Entity\ArrayOfPublishedOffer|\Ls\Omni\Client\Ecommerce\Entity\PublishedOffersGetResponse|\Ls\Omni\Client\ResponseInterface|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCoupons()
    {
        $currentProduct = $this->getProduct();
        $itemId = $currentProduct->getSku();
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
     * @param \Ls\Omni\Client\Ecommerce\Entity\ProactiveDiscount $discount
     * @return array|string
     */
    public function getFormattedDescriptionDiscount(\Ls\Omni\Client\Ecommerce\Entity\ProactiveDiscount $discount)
    {
        $currentProduct = $this->getProduct();
        $itemId = $currentProduct->getSku();
        $description = [];
        if ($discount->getDescription()) {
            $description[] = "<span class='discount-description'>" . $discount->getDescription() . "</span>";
        }
        if (floatval($discount->getMinimumQuantity()) > 0) {
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
            $description[] = "
                <span class='discount-percentage-discount-label discount-label'>" . __("Percentage Discount :") . "</span> 
                <span class='discount-percentage-discount-value discount-value'>" .
                number_format((float)$discount->getPercentage(), 2, '.', '') . "%</span>";
        }
        if ($discount->getItemIds()) {
            $itemIds = $discount->getItemIds()->getString();
            if (!is_array($itemIds)) {
                $itemIds = [$discount->getItemIds()->getString()];
            }
            $itemIds = array_unique($itemIds);
            $itemIds = array_diff($itemIds, [$itemId]);
            foreach ($itemIds as &$sku) {
                $url = $this->getProductBySku($sku);
                if (!empty($url)) {
                    $sku = "<a href = '".$url."' target='_blank'>".$sku.'</a>';
                }
            }
            $description[] = "<span class='discount-other-items-label discount-label'>" . __("Other Items :") .
                "</span><span class='discount-other-items-value discount-value'>" . implode(", ", $itemIds) . "</span>";
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
     * @param $sku
     * @return string
     */
    public function getProductBySku($sku)
    {
        $url = "";
        try {
            $product = $this->productRepository->get($sku);
            $url = $product->getProductUrl();
        } catch (\Exception $e) {
            $this->_logger->debug($e->getMessage());
        }

        return $url;
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
}
