<?php
declare(strict_types=1);

namespace Ls\Omni\Helper;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\CentralEcommerce\Entity;
use \Ls\Omni\Client\CentralEcommerce\Entity\GetDirectMarketingInfoResult as GetDirectMarketingInfoResponse;
use \Ls\Omni\Client\CentralEcommerce\Entity\GetMemberContactInfo_GetMemberContactInfo;
use \Ls\Omni\Client\CentralEcommerce\Entity\PublishedOfferLine;
use \Ls\Omni\Client\CentralEcommerce\Operation\GetImage_GetImage;
use \Ls\Omni\Client\CentralEcommerce\Operation\GetDirectMarketingInfo;
use \Ls\Omni\Client\CentralEcommerce\Operation\GetDiscount_GetDiscount;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Model\Cache\Type;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Currency;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class LoyaltyHelper for handling loyalty points
 */
class LoyaltyHelper extends AbstractHelperOmni
{
    /**
     * To get all customer offers
     *
     * @return \Ls\Omni\Client\Ecommerce\Entity\ArrayOfPublishedOffer|\Ls\Omni\Client\Ecommerce\Entity\PublishedOffersGetByCardIdResponse|ResponseInterface|null
     */
    public function getOffers()
    {
        $response = null;
        $customer = $this->customerSession->getCustomer();
        $cardId   = $customer->getData('lsr_cardid');
        // @codingStandardsIgnoreLine

        $operation = $this->createInstance(GetDirectMarketingInfo::class);
        $operation->setOperationInput([
            Entity\GetDirectMarketingInfo::CARD_ID => $cardId
        ]);
        // @codingStandardsIgnoreLine

        try {
            $response = $operation->execute();
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $response && $response->getResponsecode() == '0000' ? $response->getLoadmemberdirmarkinfoxml() : null;
    }

    /**
     * Get image by id
     *
     * @param mixed $image_id
     * @param mixed $image_size
     * @return array|bool
     * @throws NoSuchEntityException
     */
    public function getImageById($image_id = null, $image_size = null)
    {
        if ($image_id == null) {
            return [];
        }

        $cacheId = $this->getImageCacheId($image_id);

        $response = $this->cacheHelper->getCachedContent($cacheId);

        if ($response) {
            $this->_logger->debug("Found image from cache " . $cacheId);
            return $response;
        }
        // @codingStandardsIgnoreStart
        $operation = $this->createInstance(GetImage_GetImage::class);
        $operation->setOperationInput([
            'imageNo' => $image_id
        ]);
        // @codingStandardsIgnoreEnd
        try {
            $response = $operation->execute($operation);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        if (!empty($response) && $response->getResponseCode() == '0000') {
            $this->cacheHelper->persistContentInCache(
                $cacheId,
                [
                    "image"        => $response->getRecords()[0]->getTenantMedia()->getContent(),
                    "format"       => $response->getRecords()[0]->getTenantMedia()->getMimeType(),
                    "width"        => $response->getRecords()[0]->getTenantMedia()->getWidth(),
                    "height"       => $response->getRecords()[0]->getTenantMedia()->getHeight(),
                    "description"  => $response->getRecords()[0]->getTenantMedia()->getDescription(),
                    "location"     => "",
                    "locationType" => ""
                ],
                [Type::CACHE_TAG],
                604800
            );
            return [
                "image"        => $response->getRecords()[0]->getTenantMedia()->getContent(),
                "format"       => $response->getRecords()[0]->getTenantMedia()->getMimeType(),
                "width"        => $response->getRecords()[0]->getTenantMedia()->getWidth(),
                "height"       => $response->getRecords()[0]->getTenantMedia()->getHeight(),
                "description"  => $response->getRecords()[0]->getTenantMedia()->getDescription(),
                "location"     => "",
                "locationType" => ""
            ];
        }
        return [];
    }

    /**
     * Get image cache id
     *
     * @param string $imageId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getImageCacheId($imageId)
    {
        $storeId = $this->lsr->getCurrentStoreId();
        $cacheId = LSR::IMAGE_CACHE . $imageId;

        if (!$this->imageCacheIndependenOfStoreId()) {
            $cacheId .= "_" . $storeId;
        }

        return $cacheId;
    }

    /**
     * Get loyalty points available to customer
     *
     * @return float
     * @throws GuzzleException
     * @throws NoSuchEntityException
     */
    public function getLoyaltyPointsAvailableToCustomer()
    {
        $cardId = $this->contactHelper->getCardIdFromCustomerSession();
        if (!$cardId) { //fetch card id from customer object if session value not available
            $customerId = $this->customerSession->getCustomerId();
            $customer = $this->customerFactory->create()->load($customerId);
            $cardId = $customer->getLsrCardid();
        }
        $points = $this->basketHelper->getMemberPointsFromCheckoutSession();

        if ($cardId && $points == null && $this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            if ($response = $this->contactHelper->getGivenMemberCard($cardId)) {
                $points = $response->getTotalremainingpoints() ?? 0.0;
            }

            $this->basketHelper->setMemberPointsInCheckoutSession($points);
        }

        return $points ?? 0.0;
    }

    /**
     * To get contact by card id
     *
     * @return GetMemberContactInfo_GetMemberContactInfo
     */
    public function getMemberInfo()
    {
        $customer = $this->customerSession->getCustomer();
        $cardId = $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID);
        // if not set in session then get it from customer database.
        if (!$cardId) {
            $cardId = $customer->getData('lsr_cardid');
        }

        return $this->contactHelper->getCentralCustomerByCardId($cardId);
    }

    /**
     * Fetch all member schemes
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getSchemes()
    {
        return $this->dataHelper->fetchGivenTableData('LSC Member Scheme');
    }

    /**
     * Get next scheme
     *
     * @param string $currentClubCode
     * @param string $currentSequence
     * @return mixed|null
     * @throws NoSuchEntityException
     */
    public function getNextScheme(string $currentClubCode, string $currentSequence)
    {
        $schemes = $this->loyaltyHelper->getSchemes();
        $requiredScheme = null;

        foreach ($schemes as $scheme) {
            $clubCode = $scheme['Club Code'];
            $updateSequence = $scheme['Update Sequence'];
            if ($currentClubCode == $clubCode &&
                $updateSequence > $currentSequence
            ) {
                $requiredScheme = $scheme;
                break;
            }
        }

        return $requiredScheme;
    }

    /**
     * To get point balance expiry sum
     *
     * @return false|float|int
     * @throws NoSuchEntityException
     */
    public function getPointBalanceExpirySum()
    {
        $totalEarnedPoints = 0;
        $totalRedemption = 0;
        $totalExpiryPoints = 0;
        $result = $this->getCardGetPointEntries();
        $expiryInterval = $this->lsr->getStoreConfig(
            LSR::SC_LOYALTY_POINTS_EXPIRY_NOTIFICATION_INTERVAL,
            $this->lsr->getCurrentStoreId()
        );

        if ($result) {
            $startDateTs = Carbon::now();
            $endDateTs = Carbon::now()->addDays((int)$expiryInterval);

            foreach ($result as $res) {
                $entryType = $res['Entry Type'];
                $expirationDate = Carbon::parse($res['Expiration Date']);
                if ($entryType == "0" && $expirationDate->between($startDateTs, $endDateTs, true)) {
                    $totalEarnedPoints += $res['Points'];
                } elseif ($entryType == "1" && $expirationDate->between($startDateTs, $endDateTs, true)) {
                    $totalRedemption += $res['Points'];
                }
            }

            //Convert to negative redemption points to positive for ease of calculation
            $totalRedemption = abs($totalRedemption);
            if ($totalEarnedPoints >= $totalRedemption) {
                $totalExpiryPoints = $totalEarnedPoints - $totalRedemption;
            }
        }

        return $totalExpiryPoints;
    }

    /**
     * To fetch card point entry details
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getCardGetPointEntries()
    {
        $customer = $this->customerSession->getCustomer();
        $cardId = $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID);
        // if not set in session then get it from customer database.
        if (!$cardId) {
            $cardId = $customer->getData('lsr_cardid');
        }

        return $this->dataHelper->fetchGivenTableData(
            'LSC Member Point Entry',
            '',
            [
                [
                    'filterName' => 'Card No.',
                    'filterValue' => $cardId
                ]
            ]
        );
    }

    /**
     * To get member points
     *
     * @return int|null
     * @throws NoSuchEntityException|GuzzleException
     */
    public function getMemberPoints()
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $points = $this->checkoutSession->getMemberPoints();
            if (isset($points)) {
                return $points;
            }
            if ($this->customerSession->isLoggedIn()) {
                $memberProfile = $this->getMemberInfo();
                if ($memberProfile != null) {
                    $points = $memberProfile->getAccount()->getPointBalance();
                    $this->checkoutSession->setMemberPoints($points);
                    return $points;
                }
            }
        }
        return 0;
    }

    /**
     * Convert Point Rate into Values
     *
     * @param $storeId
     * @param $currencyCode
     * @return float|int|string|null
     * @throws GuzzleException
     * @throws NoSuchEntityException
     */
    public function getPointRate($storeId = null, $currencyCode = null)
    {
        if (!$storeId) {
            $storeId = $this->lsr->getCurrentStoreId();
        }

        if (!$currencyCode) {
            $currencyCode = $this->lsr->getStoreCurrencyCode();
        }

        $rate = 0;
        if ($this->lsr->isLSR($storeId) && $this->isEnabledLoyaltyPoints()) {
            $cacheId  = LSR::POINTRATE . $currencyCode . $storeId;
            $response = $this->cacheHelper->getCachedContent($cacheId);

            if ($response !== false) {
                return $this->formatValue($response);
            }

            $rate = $this->fetchGetPointsRate($currencyCode);
            if (!empty($rate)) {
                $this->cacheHelper->persistContentInCache(
                    $cacheId,
                    $rate,
                    [Type::CACHE_TAG],
                    86400
                );

                return $rate;
            }
        }

        return $rate;
    }

    /**
     * Fetch point rate from central
     *
     * @param $currencyCode
     * @return float|int
     * @throws NoSuchEntityException
     */
    public function fetchGetPointsRate($currencyCode)
    {
        $rate = 0;
        $response = $this->dataHelper->fetchGivenTableData(
            'Currency Exchange Rate',
            '',
            [
                [
                    'filterName' => 'Currency Code',
                    'filterValue' => $currencyCode
                ]
            ]
        );

        if (!empty($response['LSC POS Exchange Rate Amount']) &&
            !empty($response['LSC POS Rel. Exch. Rate Amount'])
        ) {
            $rate = ((1 / $response['LSC POS Exchange Rate Amount']) * $response['LSC POS Rel. Exch. Rate Amount']);
        } else {
            if (!empty($response['Exchange Rate Amount']) &&
                !empty($response['Relational Exch. Rate Amount'])
            ) {
                $rate = ((1 / $response['Exchange Rate Amount']) * $response['Relational Exch. Rate Amount']);
            }
        }

        if ($rate) {
            $rate = 1 / $rate;
        }

        return $rate;
    }

    /**
     * To get image size
     *
     * @param mixed $size
     * @return \Ls\Omni\Client\Ecommerce\Entity\ImageSize
     */
    public function getImageSize($size = null)
    {
        // @codingStandardsIgnoreLine
        $imagesize = new \Ls\Omni\Client\Ecommerce\Entity\ImageSize();
        $imagesize->setHeight($size['height'])
            ->setWidth($size['width']);
        return $imagesize;
    }

    /**
     * Get media path to store
     *
     * @return string
     */
    public function getMediaPathtoStore()
    {
        return $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
    }

    /**
     * Check the discount is not crossing the grand total amount
     *
     * @param float $grandTotal
     * @param int $loyaltyPoints
     * @return bool
     * @throws NoSuchEntityException|GuzzleException
     */
    public function isPointsLimitValid($grandTotal, $loyaltyPoints)
    {
        $pointRate      = $this->getPointRate();
        $requiredAmount = $pointRate * $loyaltyPoints;

        return $requiredAmount <= $grandTotal;
    }

    /**
     * Check user have enough points or not
     *
     * @param int $loyaltyPoints
     * @return bool
     * @throws NoSuchEntityException|GuzzleException
     */
    public function isPointsAreValid($loyaltyPoints)
    {
        $points = $this->getLoyaltyPointsAvailableToCustomer();

        return $points >= $loyaltyPoints;
    }

    /**
     * Fetch discounts
     *
     * @param string $itemId
     * @param string $webStore
     * @return bool|GetDiscount_GetDiscount|null
     * @throws LocalizedException
     * @throws NoSuchEntityException|GuzzleException
     */
    public function getProactiveDiscounts(string $itemId, string $webStore)
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $storeId = $this->lsr->getCurrentStoreId();
            $customerGroupId = $this->customerSession->getCustomerGroupId();
            $customerGroup = $this->groupRepository->getById($customerGroupId)->getCode();
            $cacheItemId = $itemId;
            $cacheId = LSR::PROACTIVE_DISCOUNTS . $cacheItemId . "_" . $customerGroupId . "_" . $storeId;
            $response = $this->cacheHelper->getCachedContent($cacheId);
            if ($response) {
                $this->_logger->debug("Found proactive discounts from cache " . $cacheId);
                return $response;
            }

            // @codingStandardsIgnoreStart
            $operation = $this->createInstance(GetDiscount_GetDiscount::class);
            $operation->setOperationInput([
                'items' => $itemId,
                'storeNo' => $webStore,
                'schemeCode' => $customerGroup !== 'NOT LOGGED IN' ? $customerGroup : ''
            ]);
            // @codingStandardsIgnoreEnd
            try {
                $response = $operation->execute();
            } catch (Exception $e) {
                $this->_logger->error($e->getMessage());
            }

            if ($response && $response->getResponsecode() == '0000' &&
                !empty(current($response->getRecords())->getData())
            ) {
                $this->cacheHelper->persistContentInCache(
                    $cacheId,
                    current($response->getRecords()),
                    [Type::CACHE_TAG],
                    7200
                );

                return current($response->getRecords());
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * Get published offers for given card_id, store_id, item_id
     *
     * @param string $cardId
     * @param string $storeId
     * @param ?string $itemId
     * @return bool|GetDirectMarketingInfoResponse|null
     * @throws GuzzleException
     * @throws NoSuchEntityException
     */
    public function getPublishedOffers(string $cardId, string $storeId, ?string $itemId = null)
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $cacheId = LSR::COUPONS;
            $cacheId = $itemId ? $cacheId . $itemId . '_' : $cacheId;
            $cacheId .= $cardId . "_" . $storeId;
            $response = $this->cacheHelper->getCachedContent($cacheId);

            if ($response) {
                $this->_logger->debug("Found coupons from cache " . $cacheId);

                return $response;
            }
            // @codingStandardsIgnoreStart
            $operation = $this->createInstance(GetDirectMarketingInfo::class);
            $operation->setOperationInput([
                Entity\GetDirectMarketingInfo::CARD_ID => $cardId,
                Entity\GetDirectMarketingInfo::ITEM_NO => $itemId,
                Entity\GetDirectMarketingInfo::STORE_NO => $storeId
            ]);
            try {
                $response = $operation->execute();
            } catch (Exception $e) {
                $this->_logger->error($e->getMessage());
            }
            // @codingStandardsIgnoreEnd
            if ($response && $response->getResponsecode() == '0000') {
                $this->cacheHelper->persistContentInCache(
                    $cacheId,
                    $response->getLoadmemberdirmarkinfoxml(),
                    [Type::CACHE_TAG],
                    7200
                );

                return $response->getLoadmemberdirmarkinfoxml();
            }

            return null;
        } else {
            return null;
        }
    }

    /**
     * Get all coupons
     *
     * @param array $itemId
     * @return array
     * @throws GuzzleException
     */
    public function getAllCouponsGivenItems(array $itemId): array
    {
        try {
            $storeId = $this->lsr->getActiveWebStore();
            $cardId = $this->contactHelper->getCardIdFromCustomerSession() ?? '';
            $coupons = [];

            foreach ($itemId as $id) {
                $rootGetDirectMarketingInfo = $this->loyaltyHelper->getPublishedOffers($cardId, $storeId, $id);

                if ($rootGetDirectMarketingInfo) {
                    $results = $this->contactHelper->flattenModel($rootGetDirectMarketingInfo);
                    $this->registry->unregister('lsr-c-po');
                    $this->registry->register('lsr-c-po', $results);
                    $publishedOffers = $rootGetDirectMarketingInfo->getPublishedoffer();

                    foreach ($publishedOffers ?? [] as $publishedOffer) {
                        if ($publishedOffer->getDiscounttype() == "9") {
                            $coupons[$publishedOffer->getNo()] = $publishedOffer;
                        }
                    }
                }
            }

            return $coupons;
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return [];
    }

    /**
     * Get available coupons for logged in customers
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException|GuzzleException
     *
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     */
    public function getAvailableCouponsForLoggedInCustomers()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('I am here getAvailableCouponsForLoggedInCustomers 1');
        $flag = $this->lsr->isLSR(
            $this->lsr->getCurrentStoreId(),
            false,
            $this->lsr->getBasketIntegrationOnFrontend()
        );
        
        //$flag =  $this->lsr->getBasketIntegrationOnFrontend();
        $logger->info("Flag ".(string)$flag);
        if ($this->lsr->isLSR(
            $this->lsr->getCurrentStoreId(),
            false,
            $this->lsr->getBasketIntegrationOnFrontend()
        )) {
            
            $logger->info('I am here getAvailableCouponsForLoggedInCustomers 2');
            $storeId = $this->lsr->getActiveWebStore();
            $cardId = $this->contactHelper->getCardIdFromCustomerSession();
            $logger->info("CardId: ".$cardId);
            if (!$cardId) { //fetch card id from customer object if session value not available
                $customerId = $this->customerSession->getCustomerId();
                $customer = $this->customerFactory->create()->load($customerId);
                $cardId = $customer->getLsrCardid();
            }
            $rootGetDirectMarketingInfo = $this->getPublishedOffers($cardId, $storeId);
            $requiredOffers = [];
            if ($rootGetDirectMarketingInfo) {
                $publishedOffers = $rootGetDirectMarketingInfo->getPublishedoffer();

                foreach ($publishedOffers as $publishedOffer) {
                    if ($publishedOffer->getDiscounttype() == "9") {
                        $requiredOffers[$publishedOffer->getNo()] = $publishedOffer;
                    }
                }
            }
            $itemsInCart = $this->checkoutSession->getQuote()->getAllVisibleItems();
            $coupons = $itemIdentifiers = [];
            foreach ($itemsInCart as $item) {
                if ($item->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                    $children = $item->getChildren();
                } else {
                    $children[] = $item;
                }

                foreach ($children as $child) {
                    list($itemId, $variantId, $uom, , , $baseUom) = $this->itemHelper->getComparisonValues(
                        $child->getSku(),
                        $child->getProductId()
                    );
                    $itemIdentifiers[] = [
                        'itemId' => $itemId,
                        'variantId' => $variantId,
                        'uom' => $uom,
                        'baseUom' => $baseUom
                    ];
                }
            }

            if (!empty($requiredOffers)) {
                foreach ($requiredOffers as $each) {
                    $offerNo = $each->getNo();
                    $getPublishedOfferLineArray = $rootGetDirectMarketingInfo->getPublishedofferline();

                    foreach ($getPublishedOfferLineArray as $publishedOfferLine) {
                        if ($publishedOfferLine->getPublishedofferno() == $offerNo &&
                            $this->itemExistsInCart($publishedOfferLine, $itemIdentifiers)
                            && !array_key_exists($offerNo, $coupons)
                        ) {
                            $coupons[$offerNo] = $each;
                        }
                    }
                }
                return $coupons;
            }
        }
        return [];
    }

    /**
     * Item exists in cart
     *
     * @param PublishedOfferLine $publishedOfferLine
     * @param array $itemIdentifiers
     * @return bool
     */
    public function itemExistsInCart(PublishedOfferLine $publishedOfferLine, array $itemIdentifiers): bool
    {
        $flag = false;

        foreach ($itemIdentifiers as $identifier) {
            if ($publishedOfferLine->getDiscountlineid() == $identifier['itemId'] &&
                (
                    $publishedOfferLine->getVariantcode() == $identifier['variantId'] ||
                    $publishedOfferLine->getVariantcode() == ''
                ) &&
                (
                    $publishedOfferLine->getUnitofmeasure() == $identifier['uom'] ||
                    ($publishedOfferLine->getUnitofmeasure() == '' && $identifier['uom'] == $identifier['baseUom'])
                )
            ) {
                $flag = true;
                break;
            }
        }

        return $flag;
    }

    /**
     * To check if loyalty points enabled
     *
     * @param string $area
     * @return string
     * @throws NoSuchEntityException|GuzzleException
     */
    public function isLoyaltyPointsEnabled($area)
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            if ($area == "cart") {
                return $this->lsr->getStoreConfig(
                    LSR::LS_ENABLE_LOYALTYPOINTS_ELEMENTS,
                    $this->lsr->getCurrentStoreId()
                ) && $this->lsr->getStoreConfig(
                    LSR::LS_LOYALTYPOINTS_SHOW_ON_CART,
                    $this->lsr->getCurrentStoreId()
                );
            }
            return $this->lsr->getStoreConfig(
                LSR::LS_ENABLE_LOYALTYPOINTS_ELEMENTS,
                $this->lsr->getCurrentStoreId()
            ) && $this->lsr->getStoreConfig(
                LSR::LS_LOYALTYPOINTS_SHOW_ON_CHECKOUT,
                $this->lsr->getCurrentStoreId()
            );
        } else {
            return false;
        }
    }

    /**
     * To check if loyalty elements enabled
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function isEnabledLoyaltyElements()
    {
        return $this->lsr->getStoreConfig(
            LSR::SC_LOYALTY_ENABLE_LOYALTY_ELEMENTS,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * To check if loyalty points enabled
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function isEnabledLoyaltyPoints()
    {
        return $this->lsr->getStoreConfig(
            LSR::LS_ENABLE_LOYALTYPOINTS_ELEMENTS,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * To check if enabled to show loyalty offers
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function isEnabledShowLoyaltyOffers()
    {
        return $this->lsr->getStoreConfig(
            LSR::SC_LOYALTY_SHOW_LOYALTY_OFFERS,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * To check if enabled to show point offers
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function isEnabledShowPointOffers()
    {
        return $this->lsr->getStoreConfig(
            LSR::SC_LOYALTY_SHOW_POINT_OFFERS,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * To check if enabled to show member offers
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function isEnabledShowMemberOffers()
    {
        return $this->lsr->getStoreConfig(
            LSR::SC_LOYALTY_SHOW_MEMBER_OFFERS,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * To check if enabled to show general offers
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function isEnabledShowGeneralOffers()
    {
        return $this->lsr->getStoreConfig(
            LSR::SC_LOYALTY_SHOW_GENERAL_OFFERS,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * To check if enabled to show coupon offers
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function isEnabledShowCouponOffers()
    {
        return $this->lsr->getStoreConfig(
            LSR::SC_LOYALTY_SHOW_COUPON_OFFERS,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * Image cache is independent of store
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function imageCacheIndependenOfStoreId()
    {
        return $this->lsr->getStoreConfig(
            LSR::IMAGE_CACHE_INDEPENDENT_OF_STORE_ID,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * Get Ls points discount
     *
     * @param $pointsSpent
     * @return float|int
     * @throws NoSuchEntityException
     */
    public function getLsPointsDiscount($pointsSpent)
    {
        $loyaltyPointsRate = $this->getPointRate(null, 'LOY');

        return $pointsSpent * (1 / $loyaltyPointsRate);
    }

    /**
     * Format value to two decimal places
     *
     * @param float $value
     * @return string
     */
    public function formatValue($value)
    {
        if ($value !== null && $value !== '') {
            $formattedValue = $this->currencyHelper->format(
                $value,
                ['display' => Currency::NO_SYMBOL],
                false
            );
            return str_replace(',', '.', $formattedValue);
        }

        return $value;
    }
}
