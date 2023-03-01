<?php

namespace Ls\Omni\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\StoreHourCalendarType;
use \Ls\Omni\Client\Ecommerce\Entity\StoreHours;
use \Ls\Omni\Client\Ecommerce\Operation\Ping;
use \Ls\Omni\Client\Ecommerce\Operation\StoreGetById;
use \Ls\Omni\Client\Ecommerce\Operation\StoresGetAll;
use \Ls\Omni\Model\Cache\Type;
use \Ls\Omni\Service\Service as OmniService;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use \Ls\Replication\Api\ReplStoreRepositoryInterface;
use \Ls\Replication\Api\ReplStoreTenderTypeRepositoryInterface;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\Order\Creditmemo;
use function PHPUnit\Framework\throwException;

/**
 * Helper class that is used on multiple areas
 */
class Data extends AbstractHelper
{
    /** @var ReplStoreRepositoryInterface */
    public $storeRepository;

    /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
    public $searchCriteriaBuilder;

    /**
     * @var SessionManagerInterface
     */
    public $session;

    /**
     * @var Proxy
     */
    public $checkoutSession;

    /** @var ManagerInterface */
    public $messageManager;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    public $priceHelper;

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @var CartRepositoryInterface
     */
    public $cartRepository;

    /**
     * @var CacheHelper
     */
    public $cacheHelper;

    /**
     * @var DateTime
     */
    public $date;

    /**
     * @var WriterInterface
     */
    public $configWriter;

    /**
     * @var DirectoryList
     */
    public $directoryList;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var ReplStoreTenderTypeRepositoryInterface
     */
    public $replStoreTenderTypeRepository;
    /**
     * @var GetCartForUser
     */
    private GetCartForUser $getCartForUser;
    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId;
    /**
     * @var StockHelper
     */
    private StockHelper $stockHelper;

    /**
     * Data constructor.
     * @param Context $context
     * @param ReplStoreRepositoryInterface $storeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SessionManagerInterface $session
     * @param Proxy $checkoutSession
     * @param ManagerInterface $messageManager
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param LoyaltyHelper $loyaltyHelper
     * @param CartRepositoryInterface $cartRepository
     * @param CacheHelper $cacheHelper
     * @param LSR $lsr
     * @param DateTime $date
     * @param WriterInterface $configWriter
     * @param DirectoryList $directoryList
     * @param StockHelper $stockHelper
     * @param GetCartForUser $getCartForUser
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param ReplStoreTenderTypeRepositoryInterface $storeTenderTypeRepository
     */
    public function __construct(
        Context $context,
        ReplStoreRepositoryInterface $storeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SessionManagerInterface $session,
        Proxy $checkoutSession,
        ManagerInterface $messageManager,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        LoyaltyHelper $loyaltyHelper,
        CartRepositoryInterface $cartRepository,
        CacheHelper $cacheHelper,
        LSR $lsr,
        DateTime $date,
        WriterInterface $configWriter,
        DirectoryList $directoryList,
        StockHelper $stockHelper,
        GetCartForUser $getCartForUser,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        ReplStoreTenderTypeRepositoryInterface $storeTenderTypeRepository
    ) {
        $this->storeRepository               = $storeRepository;
        $this->searchCriteriaBuilder         = $searchCriteriaBuilder;
        $this->session                       = $session;
        $this->checkoutSession               = $checkoutSession;
        $this->messageManager                = $messageManager;
        $this->priceHelper                   = $priceHelper;
        $this->cartRepository                = $cartRepository;
        $this->loyaltyHelper                 = $loyaltyHelper;
        $this->cacheHelper                   = $cacheHelper;
        $this->lsr                           = $lsr;
        $this->date                          = $date;
        $this->configWriter                  = $configWriter;
        $this->directoryList                 = $directoryList;
        $this->maskedQuoteIdToQuoteId        = $maskedQuoteIdToQuoteId;
        $this->getCartForUser                = $getCartForUser;
        $this->stockHelper                   = $stockHelper;
        $this->replStoreTenderTypeRepository = $storeTenderTypeRepository;
        parent::__construct($context);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getStoreNameById($storeId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('nav_id', $storeId, 'eq')->create();
        $stores         = $this->storeRepository->getList($searchCriteria)->getItems();
        foreach ($stores as $store) {
            return $store->getData('Name');
        }
        return "Sorry! No store found with ID : " . $storeId;
    }

    /**
     * Get Store hours
     *
     * @param string $storeId
     * @return array
     */
    public function getStoreHours($storeId)
    {
        $storeHours = null;
        try {
            $cacheId        = LSR::STORE_HOURS . $storeId;
            $cachedResponse = $this->cacheHelper->getCachedContent($cacheId);

            if ($cachedResponse) {
                $storeResults = $cachedResponse;
            } else {
                // @codingStandardsIgnoreLine
                $request = new StoreGetById();
                $request->getOperationInput()->setStoreId($storeId);
                $response     = $request->execute();
                $storeResults = [];

                if (!empty($response)) {
                    $storeResults = $response->getResult()->getStoreHours()->getStoreHours();
                    $this->cacheHelper->persistContentInCache(
                        $cacheId,
                        $storeResults,
                        [Type::CACHE_TAG],
                        86400
                    );
                }
            }
            $storeHours = [];
            $today      = $this->date->gmtDate("Y-m-d");

            for ($i = 0; $i < 7; $i++) {
                $current          = date("Y-m-d", strtotime($today) + ($i * 86400));
                $currentDayOfWeek = date('w', strtotime($current));

                foreach ($storeResults as $key => $r) {
                    if ((empty($r->getCalendarType()) ||
                            $r->getCalendarType() == StoreHourCalendarType::OPENING_HOURS ||
                            $r->getCalendarType() == StoreHourCalendarType::ALL) &&
                        $r->getDayOfWeek() == $currentDayOfWeek &&
                        $this->checkDateValidity($current, $r)) {
                        $storeHours[$currentDayOfWeek][] = [
                            'type'  => $r->getType(),
                            'day'   => $r->getNameOfDay(),
                            'open'  => $r->getOpenFrom() ?? '0001-01-01T00:00:00Z',
                            'close' => $r->getOpenTo() ?? '0001-01-01T00:00:00Z'
                        ];

                        unset($storeResults[$key]);
                    }
                }
            }
            $storeHours = $this->sortStoreTimeEntries($storeHours);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $storeHours;
    }

    /**
     * Check date validity
     *
     * @param mixed $current
     * @param StoreHours $storeHoursObj
     * @return bool
     */
    public function checkDateValidity($current, $storeHoursObj)
    {
        $currentTimeStamp = strtotime($current);

        if ($storeHoursObj->getStartDate() && $storeHoursObj->getEndDate()) {
            $storeHoursObjStartDateTimeStamp = strtotime($storeHoursObj->getStartDate());
            $storeHoursObjEndDateTimeStamp   = strtotime($storeHoursObj->getEndDate());
            return $currentTimeStamp >= $storeHoursObjStartDateTimeStamp &&
                $currentTimeStamp <= $storeHoursObjEndDateTimeStamp;
        } else {
            if ($storeHoursObj->getStartDate() && !$storeHoursObj->getEndDate()) {
                $storeHoursObjStartDateTimeStamp = strtotime($storeHoursObj->getStartDate());
                return $currentTimeStamp >= $storeHoursObjStartDateTimeStamp;
            } else {
                $storeHoursObjEndDateTimeStamp = strtotime($storeHoursObj->getEndDate());
                if (!$storeHoursObj->getStartDate() && $storeHoursObj->getEndDate()) {
                    return $currentTimeStamp <= $storeHoursObjEndDateTimeStamp;
                }
            }
        }

        return false;
    }

    /**
     * Compare by Time stamp
     *
     * @param array $timeOne
     * @param array $timeTwo
     * @return int
     */
    public function compareByTimeStamp($timeOne, $timeTwo)
    {
        $timeOneOpenTimeStamp = strtotime($timeOne['open']);
        $timeTwoOpenTimeStamp = strtotime($timeTwo['open']);

        if ($timeOneOpenTimeStamp < $timeTwoOpenTimeStamp) {
            return -1;
        } elseif ($timeOneOpenTimeStamp > $timeTwoOpenTimeStamp) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Sort store hours in ascending order
     *
     * @param array $storeHours
     * @return array
     */
    public function sortStoreTimeEntries($storeHours)
    {
        foreach ($storeHours as &$storeHour) {
            usort($storeHour, [self::class, 'compareByTimeStamp']);
        }

        return $storeHours;
    }

    /**
     * @param $value
     */
    public function setValue($value)
    {
        $this->session->start();
        $this->session->setMessage($value);
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        $this->session->start();
        return $this->session->getMessage();
    }

    /**
     * @return mixed
     */
    public function unSetValue()
    {
        $this->session->start();
        return $this->session->unsMessage();
    }

    /**
     * Validating total on gift card or loyalty points
     * @param $giftCardAmount
     * @param $loyaltyPoints
     * @param $basketData
     * @return float|int
     */
    public function getOrderBalance($giftCardAmount, $loyaltyPoints, $basketData)
    {
        $loyaltyAmount = 0;
        try {
            if ($loyaltyPoints > 0) {
                $loyaltyAmount = $this->loyaltyHelper->getPointRate() * $loyaltyPoints;
            }
            $quote = $this->cartRepository->get($this->checkoutSession->getQuoteId());
            if (!empty($basketData)) {
                $totalAmount = $basketData->getTotalAmount() + $quote->getShippingAddress()->getShippingAmount();
                return $totalAmount - $giftCardAmount - $loyaltyAmount;
            } else {
                $totalAmount = $quote->getGrandTotal();
                return $totalAmount - $giftCardAmount - $loyaltyAmount;
            }
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $loyaltyAmount;
    }

    /**
     * For checking order total against loyalty points,gift card amount and coupon discount amount
     * @param $giftCardNo
     * @param $giftCardAmount
     * @param $loyaltyPoints
     * @param $basketData
     * @param bool $showMessage
     * @return \Magento\Framework\Phrase|string
     */
    public function orderBalanceCheck($giftCardNo, $giftCardAmount, $loyaltyPoints, $basketData, $showMessage = true)
    {
        try {
            $message       = '';
            $loyaltyAmount = 0;
            if (!empty($basketData) && is_object($basketData)) {
                if ($loyaltyPoints > 0) {
                    $loyaltyAmount = $this->loyaltyHelper->getPointRate() * $loyaltyPoints;
                }
                $quote          = $this->cartRepository->get($this->checkoutSession->getQuoteId());
                $shippingAmount = $quote->getShippingAddress()->getShippingAmount();
                $discountAmount = $basketData->getTotalDiscount();
                $totalAmount    = $basketData->getTotalAmount() + $discountAmount + $shippingAmount;

                $combinedTotalLoyalGiftCard    = $giftCardAmount + $loyaltyAmount;
                $combinedDiscountPaymentAmount = $discountAmount + $combinedTotalLoyalGiftCard;
                if ($loyaltyAmount > $totalAmount) {
                    $message = __('The loyalty points "%1" are exceeding order total amount.', $loyaltyPoints);
                } elseif ($giftCardAmount > $totalAmount) {
                    $message = __(
                        'The amount "%1" of gift card code %2 is not valid.',
                        $this->priceHelper->currency($giftCardAmount, true, false),
                        $giftCardNo
                    );
                } elseif ($combinedTotalLoyalGiftCard > $totalAmount) {
                    $message = __(
                        'The gift card amount "%1" or loyalty points "%2" are not valid.',
                        $this->priceHelper->currency(
                            $giftCardAmount,
                            true,
                            false
                        ),
                        $loyaltyPoints
                    );
                } elseif ($combinedDiscountPaymentAmount > $totalAmount) {
                    $message = __('Coupon discount is exceeding total amount.');
                }
            }
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        if ($showMessage == true && $message) {
            $this->messageManager->addErrorMessage($message);
        }
        return $message;
    }

    /**
     * @param $pingResponseText
     * @param string $websiteId
     * @return array
     */
    public function parsePingResponseAndSaveToConfigData($pingResponseText, $websiteId = null)
    {
        $bothVersion = [];
        try {
            $results = explode('LS:', $pingResponseText);

            if (!empty($results)) {
                // for Omni 4.16 or higher
                $versions = explode('LS Commerce Service:', $results[1]);
                if (!empty($versions) && count($versions) < 2) {
                    // for Omni lower then 4.16
                    $versions = explode('OMNI:', $results[1]);
                }

                if (!empty($versions)) {
                    $serviceVersion                 = trim($versions[1]);
                    $bothVersion['service_version'] = $serviceVersion;
                    if (!empty($websiteId)) {
                        $this->updateConfigValueWebsite($serviceVersion, LSR::SC_SERVICE_VERSION, $websiteId);
                    } else {
                        $this->updateConfigValueDefault($serviceVersion, LSR::SC_SERVICE_VERSION);
                    }
                    $lsCentralVersion                  = trim($versions[0]);
                    $bothVersion['ls_central_version'] = $lsCentralVersion;
                    if (!empty($websiteId)) {
                        $this->updateConfigValueWebsite(
                            $lsCentralVersion,
                            LSR::SC_SERVICE_LS_CENTRAL_VERSION,
                            $websiteId
                        );
                    } else {
                        $this->updateConfigValueDefault($lsCentralVersion, LSR::SC_SERVICE_LS_CENTRAL_VERSION);
                    }
                }
            }
        } catch (Exception $e) {
            $this->_logger->critical($e);
        }

        return $bothVersion;
    }

    /**
     * @return mixed
     */
    public function getExtensionVersion()
    {
        try {
            $content          = null;
            $path             = $this->directoryList->getRoot();
            $modulePathVendor = $path . "/" . LSR::EXTENSION_COMPOSER_PATH_VENDOR;
            $modulePathApp    = $path . "/" . LSR::EXTENSION_COMPOSER_PATH_APP;
            if ($modulePathVendor) {
                try {
                    $content = file_get_contents($modulePathVendor);
                } catch (Exception $e) {
                    $this->_logger->debug($e->getMessage());
                }
                if ($content) {
                    $jsonContent = json_decode($content, true);

                    if (!empty($jsonContent['version'])) {
                        return $jsonContent['version'];
                    }
                }
                if (empty($content)) {
                    try {
                        $content = file_get_contents($modulePathApp);
                    } catch (Exception $e) {
                        $this->_logger->debug($e->getMessage());
                    }
                    if ($content) {
                        $jsonContent = json_decode($content, true);

                        if (!empty($jsonContent['version'])) {
                            return $jsonContent['version'];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $this->_logger->debug($e->getMessage());
        }
    }

    /**
     * @param $baseUrl
     * @param $lsKey
     * @return string
     */
    public function omniPing($baseUrl, $lsKey)
    {
        //@codingStandardsIgnoreStart
        $service_type = new ServiceType(StoresGetAll::SERVICE_TYPE);
        $url          = OmniService::getUrl($service_type, $baseUrl);
        $client       = new OmniClient($url, $service_type);
        $ping         = new Ping();
        //@codingStandardsIgnoreEnd
        $ping->setClient($client);
        $ping->setToken($lsKey);
        $client->setClassmap($ping->getClassMap());
        $result = $ping->execute();
        return $result->getResult();
    }

    /**
     * Update the config value
     * @param $value
     * @param $path
     */
    public function updateConfigValueDefault($value, $path)
    {
        $this->configWriter->save(
            $path,
            $value,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
    }

    /**
     * @param $value
     * @param $path
     * @param $websiteId
     */
    public function updateConfigValueWebsite($value, $path, $websiteId)
    {
        $this->configWriter->save(
            $path,
            $value,
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );
    }

    /**
     * @param $area
     * @return string
     * @throws NoSuchEntityException
     */
    public function isCouponsEnabled($area)
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            if ($area == "cart") {
                return ($this->lsr->getStoreConfig(
                        LSR::LS_ENABLE_COUPON_ELEMENTS,
                        $this->lsr->getCurrentStoreId()
                    ) && $this->lsr->getStoreConfig(
                        LSR::LS_COUPONS_SHOW_ON_CART,
                        $this->lsr->getCurrentStoreId()
                    )
                );
            }
            return ($this->lsr->getStoreConfig(
                    LSR::LS_ENABLE_COUPON_ELEMENTS,
                    $this->lsr->getCurrentStoreId()
                ) && $this->lsr->getStoreConfig(
                    LSR::LS_COUPONS_SHOW_ON_CHECKOUT,
                    $this->lsr->getCurrentStoreId()
                )
            );
        } else {
            return false;
        }
    }

    /**
     * Function to calculate invoice and credit memo total for gift card and loyalty points
     * @param $invoiceCreditMemo
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function calculateInvoiceCreditMemoTotal($invoiceCreditMemo)
    {
        $pointsSpent    = $invoiceCreditMemo->getOrder()->getLsPointsSpent();
        $giftCardAmount = $invoiceCreditMemo->getOrder()->getLsGiftCardAmountUsed();
        if ($pointsSpent > 0 || $giftCardAmount > 0) {
            $totalItemsQuantities = $totalItemsInvoice = 0;
            $pointsEarn           = $invoiceCreditMemo->getOrder()->getLsPointsEarn();
            $invoiceCreditMemo->setLsPointsEarn($pointsEarn);

            /** @var $item \Magento\Sales\Model\Order\Invoice\Item */
            foreach ($invoiceCreditMemo->getOrder()->getAllVisibleItems() as $item) {
                $totalItemsQuantities = $totalItemsQuantities + $item->getQtyOrdered();
            }

            foreach ($invoiceCreditMemo->getAllItems() as $item) {
                $orderItem = $item->getOrderItem();
                if ($orderItem->getData('product_type') == 'simple') {
                    if ($invoiceCreditMemo instanceof Creditmemo) {
                        $totalItemsInvoice += $item->getQty() - $orderItem->getQtyRefunded();
                    } else {
                        $totalItemsInvoice += $item->getQty() - $orderItem->getQtyInvoiced();
                    }
                }
            }

            $pointRate         = ($this->loyaltyHelper->getPointRate()) ? $this->loyaltyHelper->getPointRate() : 0;
            $totalPointsAmount = $pointsSpent * $pointRate;
            $totalPointsAmount = ($totalPointsAmount / $totalItemsQuantities) * $totalItemsInvoice;
            $pointsSpent       = ($pointsSpent / $totalItemsQuantities) * $totalItemsInvoice;

            $giftCardAmount = ($giftCardAmount / $totalItemsQuantities) * $totalItemsInvoice;

            $invoiceCreditMemo->setLsPointsSpent($pointsSpent);
            $invoiceCreditMemo->setLsGiftCardAmountUsed($giftCardAmount);

            $giftCardNo = $invoiceCreditMemo->getOrder()->getLsGiftCardNo();
            $invoiceCreditMemo->setLsGiftCardNo($giftCardNo);

            $grandTotalAmount     = $invoiceCreditMemo->getGrandTotal() - $totalPointsAmount - $giftCardAmount;
            $baseGrandTotalAmount = $invoiceCreditMemo->getBaseGrandTotal() - $totalPointsAmount - $giftCardAmount;
            $invoiceCreditMemo->setGrandTotal($grandTotalAmount);
            $invoiceCreditMemo->setBaseGrandTotal($baseGrandTotalAmount);
        }

        return $invoiceCreditMemo;
    }

    /**
     * Get Tender type id mapping
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getTenderTypesPaymentMapping()
    {
        $storeTenderTypes     = [];
        $scopeId              = $this->lsr->getCurrentStoreId();
        $storeTenderTypeArray = $this->getTenderTypes($scopeId);
        if (empty($storeTenderTypeArray)) {
            $storeTenderTypeArray = $this->getTenderTypesDirectly($scopeId);
        }
        if (!empty($storeTenderTypeArray)) {
            foreach ($storeTenderTypeArray as $storeTenderType) {
                $storeTenderTypes[$storeTenderType->getOmniTenderTypeId()] = $storeTenderType->getName();
            }
        }

        return $storeTenderTypes;
    }

    /**
     * For getting tender type information
     *
     * @param $scopeId
     * @return array|null
     */
    public function getTenderTypes($scopeId)
    {
        $items = null;

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('scope_id', $scopeId, 'eq')->create();
        try {
            $items = $this->replStoreTenderTypeRepository->getList($searchCriteria)->getItems();

        } catch (Exception $e) {
            $this->_logger->debug($e->getMessage());
        }

        return $items;
    }

    /**
     * Getting tender types directly through API
     *
     * @param $scopeId
     * @param null $storeId
     * @param null $baseUrl
     * @param null $lsKey
     * @return array
     * @throws NoSuchEntityException
     */
    public function getTenderTypesDirectly($scopeId, $storeId = null, $baseUrl = null, $lsKey = null)
    {
        $result = null;

        if ($baseUrl == null) {
            $baseUrl = $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_BASE_URL, $scopeId);
        }

        if ($storeId == null) {
            $storeId = $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_STORE, $scopeId);
        }

        if ($this->lsr->validateBaseUrl($baseUrl) && $storeId != '') {
            if ($lsKey == null) {
                $lsKey = $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_BASE_URL, $scopeId);
            }
            $request = $this->formulateTenderTypesRequest($baseUrl, $lsKey, $storeId, $scopeId);
            $result  = $request->execute();

            if ($result != null) {
                $result = $result->getResult()->getStoreTenderTypes()->getReplStoreTenderType();
            }
        }

        return $result;
    }

    /**
     * This function is overriding in commerce cloud module
     *
     * Formulate Tender Types Request
     *
     * @param $baseUrl
     * @param $lsKey
     * @param $storeId
     * @param $scopeId
     * @return Operation\ReplEcommStoreTenderTypes
     */
    public function formulateTenderTypesRequest($baseUrl, $lsKey, $storeId, $scopeId)
    {
        //@codingStandardsIgnoreStart
        $service_type = new ServiceType(Operation\ReplEcommStoreTenderTypes::SERVICE_TYPE);
        $url          = OmniService::getUrl($service_type, $baseUrl);
        $client       = new OmniClient($url, $service_type);
        $request      = new Operation\ReplEcommStoreTenderTypes();
        $request->setClient($client);
        $request->setToken($lsKey);
        $client->setClassmap($request->getClassMap());
        $request->getOperationInput()->setReplRequest(
            (new Entity\ReplRequest())
                ->setBatchSize(1000)
                ->setFullReplication(1)
                ->setLastKey('')
                ->setStoreId($storeId)
        );
        //@codingStandardsIgnoreEnd

        return $request;
    }

    /**
     * Fetch cart and returns stock
     *
     * @param $maskedCartId
     * @param $userId
     * @param $scopeId
     * @param $storeId
     * @param null $quote
     * @return mixed
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     * @throws NoSuchEntityException
     */
    public function fetchCartAndReturnStock($maskedCartId, $userId, $scopeId, $storeId, $quote = null)
    {
        // Shopping Cart validation for graphql
        if ($maskedCartId !== "") {
            $this->getCartForUser->execute($maskedCartId, $userId, $scopeId);

            $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
            $quote  = $this->cartRepository->get($cartId);
        }

        if ($quote === "") {
            throw new NoSuchEntityException(
                __("Could not find a valid cart for current user session.")
            );
        }

        try {
            $items = $quote->getAllVisibleItems();

            list($response, $stockCollection) = $this->stockHelper->getGivenItemsStockInGivenStore($items, $storeId);

            if ($response) {
                if (is_object($response)) {
                    if (!is_array($response->getInventoryResponse())) {
                        $response = [$response->getInventoryResponse()];
                    } else {
                        $response = $response->getInventoryResponse();
                    }
                }

                return $this->stockHelper->updateStockCollection($response, $stockCollection);
            }
        } catch (Exception $e) {
            $this->_logger->debug($e->getMessage());
        }

        return null;
    }
}
