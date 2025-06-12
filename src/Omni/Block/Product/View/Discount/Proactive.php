<?php
declare(strict_types=1);

namespace Ls\Omni\Block\Product\View\Discount;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\LSCPeriodicDiscount;
use \Ls\Omni\Client\Ecommerce\Entity\PublishedOffer;
use \Ls\Omni\Client\Ecommerce\Operation\GetDiscount_GetDiscount;
use \Ls\Omni\Helper\ContactHelper;
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
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Proactive extends Template
{
    /**
     * @var null
     */
    public $product = null;

    /**
     * @param Context $context
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
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        public Template\Context $context,
        public LSR $lsr,
        public LoyaltyHelper $loyaltyHelper,
        public ItemHelper $itemHelper,
        public ContactHelper $contactHelper,
        public CustomerFactory $customerFactory,
        public StoreManagerInterface $storeManager,
        public TimezoneInterface $timeZoneInterface,
        public ScopeConfigInterface $scopeConfig,
        public UrlInterface $urlBuilder,
        public LoggerInterface $logger,
        public Data $catalogHelper,
        public View $productBlock,
        public Registry $registry,
        public array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Return discounts recommendation only if we are doing basket calculation on frontend
     *
     * @return string
     */
    public function toHtml()
    {
        if (!$this->lsr->getBasketIntegrationOnFrontend()) {
            return '';
        }
        return parent::toHtml();
    }

    /**
     * Get proactive discounts
     *
     * @param string $itemId
     * @return GetDiscount_GetDiscount|array
     * @throws LocalizedException
     * @throws NoSuchEntityException|GuzzleException
     */
    public function getProactiveDiscounts(string $itemId)
    {
        $webStore = $this->lsr->getActiveWebStore();

        if ($response = $this->loyaltyHelper->getProactiveDiscounts($itemId, $webStore)) {
            return $response;
        }

        return [];
    }

    /**
     * Get all coupons
     *
     * @param array $itemId
     * @return array
     * @throws GuzzleException
     */
    public function getCoupons(array $itemId): array
    {
        return $this->loyaltyHelper->getAllCouponsGivenItems($itemId);
    }

    /**
     * Segregate different types of discounts
     *
     * @param \Ls\Omni\Client\Ecommerce\Entity\GetDiscount_GetDiscount $discounts
     * @return array
     */
    public function getRelevantDiscountOffers(
        \Ls\Omni\Client\Ecommerce\Entity\GetDiscount_GetDiscount $discounts
    ): array {
        $discountOffers = $mixAndMatchOffers = $multibuyOffers = [];
        $periodicDiscounts = $discounts->getLscPeriodicDiscount();
        $periodicDiscounts = is_array($periodicDiscounts) ? $periodicDiscounts : [$periodicDiscounts];

        foreach ($periodicDiscounts as $periodicDiscount) {
            if ($periodicDiscount->getOfferType() == "2") {
                $multibuyOffers[] = $periodicDiscount;
            }

            if ($periodicDiscount->getOfferType() == "3") {
                $mixAndMatchOffers[] = $periodicDiscount;
            }

            if ($periodicDiscount->getOfferType() == "4") {
                $discountOffers[] = $periodicDiscount;
            }
        }

        return [$discountOffers, $mixAndMatchOffers, $multibuyOffers];
    }

    /**
     * Get formatted description for discount offer
     *
     * @param LSCPeriodicDiscount $discount
     * @return string
     */
    public function getFormattedDescriptionForDiscountOffer(LSCPeriodicDiscount $discount): string
    {
        $description = [];
        $additionalDetails = $this->getAdditionalInformation($discount);

        if ($discount->getDescription()) {
            $description[] = "<span class='discount-description'>" . $discount->getDescription() . '</span>';
        }

        if (floatval($discount->getDiscountValue()) > 0) {
            $discountPercentage = number_format((float)$discount->getDiscountValue(), 2, '.', '');
            $discountText = __('Avail %1 Off ', $discountPercentage . '%') . '';
        }

        if (!empty($discountText)) {
            $description[] = "<span>" . $discountText . "</span>";
        }

        if (!empty($additionalDetails)) {
            $description[] = "<span>" . $additionalDetails . "</span>";
        }

        return implode('<br/>', $description);
    }

    /**
     * Get additional information for the offer
     *
     * @param LSCPeriodicDiscount $discount
     * @return Phrase|string
     */
    public function getAdditionalInformation(LSCPeriodicDiscount $discount)
    {
        $publishedOffer = $this->registry->registry('lsr-c-po');
        $publishedOffer = $this->contactHelper->restoreModel($publishedOffer);
        $additionalDetails = '';

        if (!empty($publishedOffer->getPublishedoffer())) {
            foreach ($publishedOffer->getPublishedoffer() as $offer) {
                if ($offer->getDiscountno() == $discount->getNo() &&
                    !empty($offer->getData('MemberAttribute')) &&
                    !empty($offer->getData('MemberAttributeValue'))
                ) {
                    $additionalDetails = __(
                        'Applicable only if %1 is %2',
                        $offer->getData('MemberAttribute'),
                        $offer->getData('MemberAttributeValue')
                    );

                    break;
                }
            }
        }

        return $additionalDetails;
    }

    /**
     * Get formatted description for multi buy offer
     *
     * @param LSCPeriodicDiscount $multibuyOffer
     * @param \Ls\Omni\Client\Ecommerce\Entity\GetDiscount_GetDiscount $discounts
     * @return string
     */
    public function getFormattedDescriptionForMultibuyOffer(
        LSCPeriodicDiscount $multibuyOffer,
        \Ls\Omni\Client\Ecommerce\Entity\GetDiscount_GetDiscount $discounts
    ): string {
        $additionalDetails = $this->getAdditionalInformation($multibuyOffer);
        $description = [];

        if ($multibuyOffer->getDescription()) {
            $description[] = "<span class='discount-description'>" . $multibuyOffer->getDescription() . '</span>';
        }

        foreach ($discounts->getLscWiDiscounts() as $discount) {
            if ($discount->getOfferNo() !== $multibuyOffer->getNo()) {
                continue;
            }
            $discountText = '';
            if (floatval($discount->getMinimumQuantity()) > 0) {
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

            if (floatval($discount->getDiscount()) > 0) {
                $discountPercentage = number_format((float)$discount->getDiscount(), 2, '.', '');
                $discountText = __('Avail %1 Off ', $discountPercentage . '%') . '';
            }

            if (!empty($discountText)) {
                $description[] = "<span>" . $discountText . "</span>";
            }
        }

        if (!empty($additionalDetails)) {
            $description[] = "<span>" . $additionalDetails . "</span>";
        }

        return implode('<br/>', $description);
    }

    /**
     * Get formatted description for mix & match offer
     *
     * @param LSCPeriodicDiscount $mixAndMatchOffer
     * @param \Ls\Omni\Client\Ecommerce\Entity\GetDiscount_GetDiscount $discounts
     * @param string $itemId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getFormattedDescriptionForMixAndMatchOffer(
        LSCPeriodicDiscount $mixAndMatchOffer,
        \Ls\Omni\Client\Ecommerce\Entity\GetDiscount_GetDiscount $discounts,
        string $itemId
    ): string {
        $additionalDetails = $this->getAdditionalInformation($mixAndMatchOffer);
        $description = $items = [];
        $counter = 0;
        $popupLink = $popupHtml = $discountText = '';
        foreach ($discounts->getLscWiMixMatchOfferExt() as $discount) {
            if ($discount->getOfferNo() == $mixAndMatchOffer->getNo() &&
                $discount->getCustomerDiscGroup() == '--EXT--' &&
                $discount->getItemNo() != $itemId
            ) {
                $items[] = $discount;
            }
        }
        if ($mixAndMatchOffer->getDescription()) {
            $description[] = "<span class='discount-description'>" . $mixAndMatchOffer->getDescription() . '</span>';
        }

        if (floatval($mixAndMatchOffer->getDiscountValue()) > 0) {
            $discountPercentage = number_format((float)$mixAndMatchOffer->getDiscountValue(), 2, '.', '');
            $discountText = __('Avail %1 Off ', $discountPercentage . '%') . '';
        }

        foreach ($items as $item) {
            $productInfo = $this->itemHelper->getProductByIdentificationAttributes($item->getItemNo());
            if ($this->getMixandMatchProductLimit() == $counter) {
                break;
            }
            $priceHtml = '';
            if ($counter == 0) {
                $popupLink = "<a style='cursor:pointer' class='ls-click-product-promotion'
                 data-id='" . $mixAndMatchOffer->getOfferNo() . "'>"
                    . __('Click Here to see the items') . "</a>";
                $popupHtml = "<div class='ls-discounts-popup-model'
                id='ls-popup-model-" . $mixAndMatchOffer->getOfferNo() . "' style='display:none;'>";
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
                        $productHtml .= "<div class='item-popup'>";
                        $productHtml .= "<a  href = '" . $productInfo->getProductUrl() . "' class='product-link'
                         target='_blank'>" . $imageHtml .
                            "<div class='title'>" . $productName . '</div>';
                        $productHtml .= '</a>';
                        $productHtml .= $priceHtml . '</div>';
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
            if (!empty($productData)) {
                $description[] = implode(' ', $productData);
                $description[] = '</div>';
            }
        }

        if (!empty($additionalDetails)) {
            $description[] = "<span>" . $additionalDetails . "</span>";
        }

        return implode('<br/>', $description);
    }

    /**
     * Get mix and match product limit
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getMixandMatchProductLimit(): string
    {
        return $this->lsr->getStoreConfig(LSR::LS_DISCOUNT_MIXANDMATCH_LIMIT, $this->lsr->getCurrentStoreId());
    }

    /**
     * Get formatted description for coupon
     *
     * @param PublishedOffer $coupon
     * @return string
     */
    public function getFormattedDescriptionForCoupon(PublishedOffer $coupon): string
    {
        $description = [];
        if ($coupon->getDescription()) {
            $description[] = "<span class='coupon-description'>" . $coupon->getDescription() . '</span>';
        }
        if ($coupon->getSecondarytext()) {
            $description[] = "<span class='coupon-details'>" . $coupon->getSecondarytext() . '</span>';
        }
        if ($coupon->getDiscounttype() == "9") {
            if ($coupon->getEndingdate()) {
                $description[] = "
        <span class='coupon-expiration-date-label discount-label'>" . __('Expiry :') . "</span>
        <span class='coupon-expiration-date-value discount-value'>" .
                    $this->getFormattedOfferExpiryDate($coupon->getEndingdate()) . '</span>';
            }
            if ($coupon->getDiscountno()) {
                $description[] = "
        <span class='coupon-offer-id-label discount-label'>" . __('Coupon Code :') . "</span>
        <span class='coupon-offer-id-value discount-value'>" . $coupon->getDiscountno() . '</span>';
            }
        }

        return implode('<br/>', $description);
    }

    /**
     * Get formatted expiry date
     *
     * @param string $date
     * @return string|null
     */
    public function getFormattedOfferExpiryDate(string $date): ?string
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
    public function getAjaxUrl(): string
    {
        return $this->urlBuilder->getUrl('omni/ajax/ProactiveDiscountsAndCoupons', []);
    }

    /**
     * Get current product sku
     *
     * @return string|null
     */
    public function getProductSku(): ?string
    {
        $currentProduct = $this->getProduct();
        if (empty($currentProduct) || !$currentProduct->getId()) {
            return null;
        }
        return $currentProduct->getSku();
    }

    /**
     * Get current product
     *
     * @return Product|null
     */
    public function getProduct(): ?Product
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
     * @throws NoSuchEntityException|GuzzleException
     */
    public function isValid(): ?bool
    {
        return $this->lsr->isLSR($this->lsr->getCurrentStoreId());
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
        return $this->itemHelper->getLsCentralItemIdBySku($sku);
    }

    /**
     * Get product given sku
     *
     * @param string $sku
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    public function getProductGivenSku(string $sku)
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
