<?php

namespace Ls\OmniGraphQl\Model\Resolver;

use \Ls\Core\Model\LSR;
use Ls\Omni\Block\Product\View\View;
use \Ls\Omni\Client\Ecommerce\Entity\DiscountsGetResponse;
use Ls\Omni\Client\Ecommerce\Entity\Enum\DiscountType;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ProactiveDiscountType;
use \Ls\Omni\Client\Ecommerce\Entity\ProactiveDiscount;
use \Ls\Omni\Client\Ecommerce\Entity\PublishedOffer;
use \Ls\Omni\Client\Ecommerce\Entity\PublishedOffersGetByCardIdResponse;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Omni\Plugin\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\Catalog\Block\Product\Context as ProductContext;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session\Proxy;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\Url\EncoderInterface as UrlEncoderInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Helper\Image;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

/**
 * To get discounts in product view page in graphql
 */
class GetDiscountsOutput extends View implements ResolverInterface
{
    /**
     * @var LSR
     */
    public $lsr;
    /**
     * @var LoyaltyHelper
     */
    private $loyaltyHelper;
    /**
     * @var PageFactory
     */
    private PageFactory $resultPageFactory;
    /**
     * @var HttpContext
     */
    private HttpContext $httpContext;
    /**
     * @var CustomerFactory
     */
    private CustomerFactory $customerFactory;
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var ItemHelper
     */
    private $itemHelper;
    /**
     * @var ProductContext
     */
    private ProductContext $productContext;
    /**
     * @var Image
     */
    private Image $imageHelper;
    /**
     * @var PriceHelper
     */
    private PriceHelper $priceHelper;
    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timeZoneInterface;
    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;
    /**
     * @var Http
     */
    private Http $request;


    /**
     * @param LSR $lsr
     * @param LoyaltyHelper $loyaltyHelper
     * @param PageFactory $resultPageFactory
     * @param HttpContext $httpContext
     * @param CustomerFactory $customerFactory
     * @param StoreManagerInterface $storeManager
     * @param ItemHelper $itemHelper
     * @param Image $imageHelper
     * @param PriceHelper $priceHelper
     * @param TimezoneInterface $timeZoneInterface
     * @param ScopeConfigInterface $scopeConfig
     * @param Proxy $customerSession
     * @param Http $request
     */
    public function __construct(
        LSR $lsr,
        LoyaltyHelper $loyaltyHelper,
        PageFactory $resultPageFactory,
        HttpContext $httpContext,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        ItemHelper $itemHelper,
        Image $imageHelper,
        PriceHelper $priceHelper,
        TimezoneInterface $timeZoneInterface,
        ScopeConfigInterface $scopeConfig,
        Proxy $customerSession,
        Http $request,
    ) {
        $this->lsr               = $lsr;
        $this->loyaltyHelper     = $loyaltyHelper;
        $this->resultPageFactory = $resultPageFactory;
        $this->httpContext       = $httpContext;
        $this->customerFactory   = $customerFactory;
        $this->storeManager      = $storeManager;
        $this->itemHelper        = $itemHelper;
        $this->imageHelper       = $imageHelper;
        $this->priceHelper       = $priceHelper;
        $this->timeZoneInterface = $timeZoneInterface;
        $this->scopeConfig       = $scopeConfig;
        $this->customerSession   = $customerSession;
        $this->request              = $request;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {

        if (empty($args['item_id'])) {
            throw new GraphQlInputException(__('Required parameter "item_id" is missing'));
        }

        $itemId    = '';

        //$storeId = $args['store_id'];
        if (!empty($args['item_id'])) {
            $itemId = $args['item_id'];
        }

        $couponsObj   = $this->getCoupons($itemId);

        $discountsArr = $couponsArr = [];
        $discountsObj = $this->getProactiveDiscounts($itemId);

        if (!empty($discountsObj)) {
            foreach ($discountsObj as $discount) {
                $discountsArr[] = $this->getFormattedDescriptionDiscount($itemId, $discount);
            }
        }
        if (!empty($couponsObj != '')) {
            foreach ($couponsObj as $coupon) {
                if ($coupon->getCode() == DiscountType::COUPON || $coupon->getCode() == DiscountType::PROMOTION) {
                    $couponsArr[] = $this->getFormattedDescriptionCoupon($coupon);
                }

            }
        }

        return ['output' =>
                    [
                        'coupons' =>  $couponsArr,
                        'discounts' => $discountsArr
                    ]
        ];
    }

    /**
     * @param $sku
     * @return array|DiscountsGetResponse|ProactiveDiscount[]|ResponseInterface|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getProactiveDiscounts($sku)
    {
        $itemId   = $sku;
        $webStore = $this->lsr->getActiveWebStore();
        if ($response = $this->loyaltyHelper->getProactiveDiscounts($itemId, $webStore)) {
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
     * Get all coupons
     *
     * @param $sku
     * @return array|bool|PublishedOffer[]|PublishedOffersGetByCardIdResponse|ResponseInterface
     * @throws NoSuchEntityException
     */
    public function getCoupons($sku)
    {
        $itemId = $sku;
        try {
            $storeId = $this->lsr->getActiveWebStore();
            if ($this->httpContext->getValue(Context::CONTEXT_CUSTOMER_ID) ||
                ($this->customerSession->getCustomerId()
                    && str_contains($this->request->getOriginalPathInfo(), 'graphql')
                )
            ) {
                $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
                $email     = ($this->httpContext->getValue(Context::CONTEXT_CUSTOMER_EMAIL)) ?
                    $this->httpContext->getValue(Context::CONTEXT_CUSTOMER_EMAIL) :
                    $this->customerSession->getCustomerData()->getEmail();
                $customer  = $this->customerFactory->create()->setWebsiteId($websiteId)->loadByEmail($email);
                $cardId    = $customer->getData('lsr_cardid');
                if ($response = $this->loyaltyHelper->getPublishedOffers($cardId, $storeId, $itemId)) {
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
     * @throws NoSuchEntityException
     */
    // @codingStandardsIgnoreLine
    public function getFormattedDescriptionDiscount(
        $itemId,
        ProactiveDiscount $discount
    ) {
        $responseArr  = [];
        $description  = [];
        $discountText = '';
        $productData  = [];
        if ($discount->getDescription()) {
//            $description[] = "<span class='discount-description'>" . $discount->getDescription() . '</span>';
            $responseArr['discount_description_title'] = $discount->getDescription();
        }
        if (floatval($discount->getMinimumQuantity()) > 0 && $discount->getType() == ProactiveDiscountType::MULTIBUY) {
            $responseArr['discount_min_qty'] = number_format(
                (float)$discount->getMinimumQuantity(),
                2,
                '.',
                ''
            );
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
            $productsData = [];
            $productName  = '';

            if (!empty($itemIds)) {
                $productsData = $this->itemHelper->getProductsInfoBySku($itemIds);
            }
            foreach ($productsData as $productInfo) {
                if ($this->getMixandMatchProductLimit() == $counter) {
                    break;
                }

                $productPrice = '';
                if (!empty($productInfo)) {
                    $imageUrl = $this->imageHelper->init($productInfo, 'product_base_image')->getUrl();
                    if (!empty($productInfo->getFinalPrice())) {
                        $productPrice = $productInfo->getFinalPrice();
                    }
                    if (!empty($productInfo->getProductUrl())) {
                        if (!empty($productInfo->getName())) {
                            $productName = $productInfo->getName();
                        }
                    }

                    $productData[] = [
                        'product_name' => $productName,
                        'image_url'    => $imageUrl,
                        'product_url'  => $productInfo->getProductUrl(),
                        'sku'          => $productInfo->getSku(),
                        'price'        => $this->priceHelper->currency($productPrice, true, false)
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
                    $responseArr['discount_products_data']    = $productData;
                }
            }
        } else {
            if (!empty($discountText)) {
                $responseArr['discount_description_text'] = $discountText;
            }
        }

        return $responseArr;
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getMixandMatchProductLimit()
    {
        return $this->lsr->getStoreConfig(LSR::LS_DISCOUNT_MIXANDMATCH_LIMIT, $this->lsr->getCurrentStoreId());
    }

    /**
     * @param PublishedOffer $coupon
     * @return array|string
     * @throws NoSuchEntityException
     */
    public function getFormattedDescriptionCoupon(PublishedOffer $coupon)
    {
        $responseArr = [];
        if ($coupon->getDescription()) {
            $responseArr['coupon_description'] = $coupon->getDescription();
        }
        if ($coupon->getDetails()) {
            $responseArr['coupon_details'] = $coupon->getDetails();
        }
        if ($coupon->getCode() != DiscountType::PROMOTION) {
            if ($coupon->getExpirationDate()) {
                $responseArr['coupon_expire_date'] = $this->getFormattedOfferExpiryDate($coupon->getExpirationDate());
            }
            if ($coupon->getOfferId()) {
                $responseArr['offer_id'] = $coupon->getOfferId();
            }
        }

        return $responseArr;
    }

    /**
     * @param $date
     * @return string
     * @throws NoSuchEntityException
     */
    public function getFormattedOfferExpiryDate($date)
    {
        try {
            $offerExpiryDate = $this->timeZoneInterface->date($date)->format($this->scopeConfig->getValue(
                LSR::SC_LOYALTY_EXPIRY_DATE_FORMAT,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $this->lsr->getActiveWebStore()
            ));

            return $offerExpiryDate;
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return null;
    }
}
