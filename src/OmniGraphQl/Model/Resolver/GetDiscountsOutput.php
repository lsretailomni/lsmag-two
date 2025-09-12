<?php
declare(strict_types=1);

namespace Ls\OmniGraphQl\Model\Resolver;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Block\Product\View\Discount\Proactive;
use \Ls\Omni\Client\CentralEcommerce\Entity\GetDiscount_GetDiscount;
use \Ls\Omni\Client\CentralEcommerce\Entity\LSCPeriodicDiscount;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\OmniGraphQl\Helper\DataHelper;
use Magento\Catalog\Helper\Image;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Psr\Log\LoggerInterface;

/**
 * To get discounts in product view page in graphql
 */
class GetDiscountsOutput implements ResolverInterface
{
    /**
     * @param LSR $lsr
     * @param LoyaltyHelper $loyaltyHelper
     * @param PageFactory $resultPageFactory
     * @param CustomerFactory $customerFactory
     * @param StoreManagerInterface $storeManager
     * @param ItemHelper $itemHelper
     * @param Image $imageHelper
     * @param PriceHelper $priceHelper
     * @param ContactHelper $contactHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param TimezoneInterface $timeZoneInterface
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $customerSession
     * @param Http $request
     * @param Emulation $appEmulation
     * @param DataHelper $dataHelper
     * @param LoggerInterface $logger
     * @param Proactive $proactiveDiscountBlock
     * @param Registry $registry
     */
    public function __construct(
        public LSR $lsr,
        public LoyaltyHelper $loyaltyHelper,
        public PageFactory $resultPageFactory,
        public CustomerFactory $customerFactory,
        public StoreManagerInterface $storeManager,
        public ItemHelper $itemHelper,
        public Image $imageHelper,
        public PriceHelper $priceHelper,
        public ContactHelper $contactHelper,
        public PriceCurrencyInterface $priceCurrency,
        public TimezoneInterface $timeZoneInterface,
        public ScopeConfigInterface $scopeConfig,
        public Session $customerSession,
        public Http $request,
        public Emulation $appEmulation,
        public DataHelper $dataHelper,
        public LoggerInterface $logger,
        public Proactive $proactiveDiscountBlock,
        public Registry $registry,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($args['item_id'])) {
            throw new GraphQlInputException(__('Required parameter "item_id" is missing'));
        }

        if (!empty($context->getUserId())) {
            $websiteId = (int)$context->getExtensionAttributes()->getStore()->getWebsiteId();
            $userId = $context->getUserId();
            $this->dataHelper->setCustomerValuesInSession($userId, $websiteId);
        }

        $itemId = $args['item_id'];
        $couponsObj = $this->loyaltyHelper->getAllCouponsGivenItems([$itemId]);
        $discountsArr = $couponsArr = [];
        list(
            $discountOffers,
            $mixAndMatchDiscounts,
            $multibuyOffers
            ) = $this->getProactiveDiscounts($itemId);

        if (!empty($discountOffers)) {
            foreach ($discountOffers as $discountOffer) {
                $discountsArr[] = $this->getFormattedDescriptionForDiscountOffer($discountOffer['offer']);
            }
        }

        if (!empty($mixAndMatchDiscounts)) {
            foreach ($mixAndMatchDiscounts as $mixAndMatchDiscount) {
                $discountsArr[] = $this->getFormattedDescriptionForMixAndMatchOffer(
                    $mixAndMatchDiscount['offer'],
                    $mixAndMatchDiscount['discounts'],
                    $mixAndMatchDiscount['item']
                );
            }
        }

        if (!empty($multibuyOffers)) {
            foreach ($multibuyOffers as $multibuyOffer) {
                $discountsArr[] = $this->getFormattedDescriptionForMultibuyOffer(
                    $multibuyOffer['offer'],
                    $multibuyOffer['discounts']
                );
            }
        }

        if (!empty($couponsObj != '')) {
            foreach ($couponsObj as $coupon) {
                $couponsArr[] = $this->dataHelper->getFormattedDescriptionCoupon($coupon);
            }
        }

        return [
            'output' => [
                'coupons' => $couponsArr,
                'discounts' => $discountsArr
            ]
        ];
    }

    /**
     * Get proactive discounts
     *
     * @param string $itemId
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException|GuzzleException
     */
    public function getProactiveDiscounts($itemId)
    {
        $webStore = $this->lsr->getActiveWebStore();
        $discounts = $this->loyaltyHelper->getProactiveDiscounts($itemId, $webStore);
        $discountOffers = $mixAndMatchDiscounts = $multibuyOffers = [];
        if (!empty($discounts)) {
            list(
                $discountOffer,
                $mixAndMatchDiscount,
                $multibuyOffer
                ) = $this->proactiveDiscountBlock->getRelevantDiscountOffers($discounts);

            if (!empty($discountOffer)) {
                foreach ($discountOffer as $doNo => $do) {
                    if (!isset($discountOffers[$doNo])) {
                        $discountOffers[$doNo]['offer'] = $do;
                        $discountOffers[$doNo]['discounts'] = $discounts;
                        $discountOffers[$doNo]['item'] = $itemId;
                    }
                }
            }

            if (!empty($mixAndMatchDiscount)) {
                foreach ($mixAndMatchDiscount as $mmNo => $mm) {
                    if (!isset($mixAndMatchDiscounts[$mmNo])) {
                        $mixAndMatchDiscounts[$mmNo]['offer'] = $mm;
                        $mixAndMatchDiscounts[$mmNo]['discounts'] = $discounts;
                        $mixAndMatchDiscounts[$mmNo]['item'] = $itemId;
                    }
                }
            }

            if (!empty($multibuyOffer)) {
                foreach ($multibuyOffer as $mbNo => $mb) {
                    if (!isset($multibuyOffers[$mbNo])) {
                        $multibuyOffers[$mbNo]['offer'] = $mb;
                        $multibuyOffers[$mbNo]['discounts'] = $discounts;
                        $multibuyOffers[$mbNo]['item'] = $itemId;
                    }
                }
            }

            return [$discountOffers, $mixAndMatchDiscounts, $multibuyOffers];
        }

        return [$discountOffers, $mixAndMatchDiscounts, $multibuyOffers];
    }

    /**
     * Get formatted description for discount offer
     *
     * @param LSCPeriodicDiscount $discount
     * @return array
     */
    public function getFormattedDescriptionForDiscountOffer(LSCPeriodicDiscount $discount): array
    {
        $description = [];
        $additionalDetails = $this->getAdditionalInformation($discount);

        if ($discount->getDescription()) {
            $description['discount_description_title'] = $discount->getDescription();
        }

        if (floatval($discount->getDiscountValue()) > 0) {
            $discountPercentage = number_format((float)$discount->getDiscountValue(), 2, '.', '');
            $discountText = __('Avail %1 Off ', $discountPercentage . '%') . '';
        }

        if (!empty($discountText)) {
            $description['discount_description_text'] = $discountText;
        }

        if (!empty($additionalDetails)) {
            $description['discount_description_text'] .= $additionalDetails;
        }

        return $description;
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
        $publishedOffer = $publishedOffer ? $this->contactHelper->restoreModel($publishedOffer) : '';
        $additionalDetails = '';

        if ($publishedOffer && !empty($publishedOffer->getPublishedoffer())) {
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
     * Get formatted description for mix & match offer
     *
     * @param LSCPeriodicDiscount $mixAndMatchOffer
     * @param GetDiscount_GetDiscount $discounts
     * @param string $itemId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getFormattedDescriptionForMixAndMatchOffer(
        LSCPeriodicDiscount $mixAndMatchOffer,
        GetDiscount_GetDiscount $discounts,
        string $itemId
    ): array {
        $responseArr  = [];
        $discountText = '';
        $productData  = [];
        if ($mixAndMatchOffer->getDescription()) {
            $responseArr['discount_description_title'] = $mixAndMatchOffer->getDescription();
        }
        $additionalDetails = $this->getAdditionalInformation($mixAndMatchOffer);
        $itemIds = [];
        $counter = 0;
        foreach ($discounts->getLscWiMixMatchOfferExt() as $discount) {
            if ($discount->getOfferNo() == $mixAndMatchOffer->getNo() &&
                $discount->getCustomerDiscGroup() == '--EXT--' &&
                $discount->getItemNo() != $itemId
            ) {
                $itemIds[] = $discount->getItemNo();
            }
        }

        if (floatval($mixAndMatchOffer->getDiscountValue()) > 0) {
            $discountPercentage = number_format((float)$mixAndMatchOffer->getDiscountValue(), 2, '.', '');
            $discountText = __('Avail %1 Off ', $discountPercentage . '%') . '';
        }

        $currency     = $this->priceCurrency->getCurrency($this->lsr->getCurrentStoreId())->getCurrencyCode();
        if (!empty($itemIds)) {
            $productsData = $this->itemHelper->getProductsInfoByItemIds($itemIds);
        }
        foreach ($productsData as $productInfo) {
            $productName  = '';

            if ($this->getMixandMatchProductLimit() == $counter) {
                break;
            }

            $productPrice = '';
            if (!empty($productInfo)) {
                $imageUrl = $productInfo->getImage();
                if (!empty($productInfo->getFinalPrice())) {
                    $productPrice = $productInfo->getFinalPrice();
                }
                if (!empty($productInfo->getUrlKey())) {
                    if (!empty($productInfo->getName())) {
                        $productName = $productInfo->getName();
                    }
                }

                $productData[] = [
                    'product_name' => $productName,
                    'image_url'    => $imageUrl,
                    'product_url'  => $productInfo->getUrlKey(),
                    'sku'          => $productInfo->getSku(),
                    'price'        => [
                        'currency' => $currency,
                        'value'    => $productInfo->getPrice(),
                    ],
                    'final_price'  => [
                        'currency' => $currency,
                        'value'    => $productPrice,
                    ]
                ];
            }
            $counter++;
        }
        if (!empty($discountText)) {
            $discountText .= __('if Buy with any of these items: ');
        }

        $responseArr['discount_description_text'] = $discountText;

        if ($this->getMixandMatchProductLimit() != 0) {
            if (!empty($productsData)) {
                $responseArr['discount_products_data'] = $productData;
            }
        }

        if (!empty($additionalDetails)) {
            $responseArr['discount_description_text'] .= $additionalDetails;
        }

        return $responseArr;
    }

    /**
     * Get formatted description for multi buy offer
     *
     * @param LSCPeriodicDiscount $multibuyOffer
     * @param GetDiscount_GetDiscount $discounts
     * @return array
     */
    public function getFormattedDescriptionForMultibuyOffer(
        LSCPeriodicDiscount $multibuyOffer,
        GetDiscount_GetDiscount $discounts
    ): array {
        $responseArr  = [];
        $additionalDetails = $this->getAdditionalInformation($multibuyOffer);

        if ($multibuyOffer->getDescription()) {
            $responseArr['discount_description_title'] = $multibuyOffer->getDescription();
        }

        foreach ($discounts->getLscWiDiscounts() as $discount) {
            if ($discount->getOfferNo() !== $multibuyOffer->getNo()) {
                continue;
            }
            $discountText = '';
            if (floatval($discount->getMinimumQuantity()) > 0) {
                $responseArr['discount_min_qty'] = number_format(
                    (float)$discount->getMinimumQuantity(),
                    2,
                    '.',
                    ''
                );
            }

            if (floatval($discount->getDiscount()) > 0) {
                $discountPercentage = number_format((float)$discount->getDiscount(), 2, '.', '');
                $discountText       = __('Avail %1 Off ', $discountPercentage . '%') . '';
            }

            $responseArr['discount_description_text'] = $discountText;
            break;
        }

        if (!empty($additionalDetails)) {
            $responseArr['discount_description_text'] .= $additionalDetails;
        }

        return $responseArr;
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
}
