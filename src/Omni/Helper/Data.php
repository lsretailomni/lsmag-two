<?php

namespace Ls\Omni\Helper;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\StoreHourCalendarType;
use \Ls\Omni\Client\Ecommerce\Entity\StoreHours;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Omni\Client\Ecommerce\Operation\HierarchyView;
use \Ls\Omni\Client\Ecommerce\Operation\LSCTenderType;
use \Ls\Omni\Client\Ecommerce\Operation\StoreGetById;
use \Ls\Omni\Client\Ecommerce\Operation\TestConnectionResponse;
use \Ls\Omni\Model\Cache\Type;
use \Ls\Omni\Model\Central\GuzzleClient;
use \Ls\Omni\Model\Central\TokenRequestService;
use \Ls\Omni\Service\Service as OmniService;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use \Ls\Replication\Api\ReplStoreRepositoryInterface;
use \Ls\Replication\Api\ReplStoreTenderTypeRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var CheckoutSession
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
    public GetCartForUser $getCartForUser;
    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    public MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId;
    /**
     * @var StockHelper
     */
    public StockHelper $stockHelper;

    /**
     * @var File
     */
    public File $fileSystemDriver;

    /**
     * @var GuzzleClient
     */
    public $guzzleClient;

    /**
     * @var TokenRequestService
     */
    public $tokenRequestService;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var RequestInterface
     */
    public $request;

    /**
     * @param Context $context
     * @param ReplStoreRepositoryInterface $storeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SessionManagerInterface $session
     * @param CheckoutSession $checkoutSession
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
     * @param File $fileSystemDriver
     * @param GuzzleClient $guzzleClient
     * @param StoreManagerInterface $storeManager
     * @param TokenRequestService $tokenRequestService
     * @param RequestInterface $request
     */
    public function __construct(
        Context                                $context,
        ReplStoreRepositoryInterface           $storeRepository,
        SearchCriteriaBuilder                  $searchCriteriaBuilder,
        SessionManagerInterface                $session,
        CheckoutSession                        $checkoutSession,
        ManagerInterface                       $messageManager,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        LoyaltyHelper                          $loyaltyHelper,
        CartRepositoryInterface                $cartRepository,
        CacheHelper                            $cacheHelper,
        LSR                                    $lsr,
        DateTime                               $date,
        WriterInterface                        $configWriter,
        DirectoryList                          $directoryList,
        StockHelper                            $stockHelper,
        GetCartForUser                         $getCartForUser,
        MaskedQuoteIdToQuoteIdInterface        $maskedQuoteIdToQuoteId,
        ReplStoreTenderTypeRepositoryInterface $storeTenderTypeRepository,
        File                                   $fileSystemDriver,
        GuzzleClient                           $guzzleClient,
        StoreManagerInterface                  $storeManager,
        TokenRequestService                    $tokenRequestService,
        RequestInterface                       $request,
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
        $this->fileSystemDriver              = $fileSystemDriver;
        $this->guzzleClient                  = $guzzleClient;
        $this->storeManager                  = $storeManager;
        $this->tokenRequestService           = $tokenRequestService;
        $this->request                       = $request;
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
                $request->setOperationInput()->setStoreId($storeId);
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
     * Set message value in session
     *
     * @param mixed $value
     * @return void
     */
    public function setValue($value)
    {
        $this->session->start();
        $this->session->setMessage($value);
    }

    /**
     * Get message value from session
     *
     * @return mixed
     */
    public function getValue()
    {
        $this->session->start();
        return $this->session->getMessage();
    }

    /**
     * Unset message from the session
     *
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
                $totalAmount = $basketData->getTotalAmount() + $quote->getShippingAddress()->getShippingInclTax();
            } else {
                $totalAmount = $quote->getGrandTotal();
            }
            return $totalAmount - $giftCardAmount - $loyaltyAmount;
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
     * Parse ping response and save configuration
     *
     * @param mixed $pingResponseText
     * @param string $websiteId
     * @return array
     */
    public function parsePingResponseAndSaveToConfigData($pingResponseText, $websiteId = null)
    {
        try {
            $lsCentralVersion = "";
            $lsRetailLicenseIsActive = $lsRetailLicenseUnitEcomIsActive = false;

            if (!empty($pingResponseText->getLSRetailVersion() &&
                str_contains($pingResponseText->getLSRetailVersion(), 'LS Central'))
            ) {
                $lsCentralVersion = explode('LS Central ', $pingResponseText->getLSRetailVersion())[1];

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

            $lsRetailLicenseIsActive = $pingResponseText->getLSRetailLicenseKeyActive();

            $lsRetailLicenseUnitEcomIsActive = $pingResponseText->getLSRetailLicenseUnitEcom();
        } catch (Exception $e) {
            $this->_logger->critical($e);
        }

        return [$lsCentralVersion, $lsRetailLicenseIsActive, $lsRetailLicenseUnitEcomIsActive];
    }

    /**
     * Get Extension version
     *
     * @return mixed|void
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
                    $content = $this->fileSystemDriver->fileGetContents($modulePathVendor);
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
                        $content = $this->fileSystemDriver->fileGetContents($modulePathApp);
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
     * Set missing parameters required for making a call to Central
     *
     * @param string $baseUrl
     * @param array $connectionParams
     * @param array $query
     * @return void
     * @throws NoSuchEntityException
     */
    public function setMissingParameters(&$baseUrl, &$connectionParams, &$query)
    {
        if (isset($connectionParams['clientSecret']) && $connectionParams['clientSecret'] === '******') {
            $connectionParams['clientSecret'] = $this->lsr->getWebsiteConfig(
                LSR::SC_CLIENT_SECRET,
                $this->getScopeId()
            );
        }

        $baseUrl = $this->getBaseUrl(!empty($baseUrl) ?
            $baseUrl : $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_BASE_URL, $this->getScopeId()));
        $connectionParams['clientId'] = $connectionParams['clientId'] ??
            $this->lsr->getWebsiteConfig(LSR::SC_CLIENT_ID, $this->getScopeId());
        $connectionParams['clientSecret'] = $connectionParams['clientSecret'] ??
            $this->lsr->getWebsiteConfig(LSR::SC_CLIENT_SECRET, $this->getScopeId());

        $connectionParams['tenant'] = $connectionParams['tenant'] ??
            $this->lsr->getWebsiteConfig(LSR::SC_TENANT, $this->getScopeId());
        $connectionParams['environmentName'] = $connectionParams['environmentName'] ??
            $this->lsr->getWebsiteConfig(LSR::SC_ENVIRONMENT_NAME, $this->getScopeId());
        $query['company'] = !empty($query['company']) ?
            $query['company'] : $this->lsr->getWebsiteConfig(LSR::SC_COMPANY_NAME, $this->getScopeId());

        $connectionParams['token'] =
            $this->fetchValidToken(
                $connectionParams['tenant'],
                $connectionParams['clientId'],
                $connectionParams['clientSecret']
            );
    }

    /**
     * Function for central service ping
     *
     * @param string $baseUrl
     * @param array $connectionParams
     * @param array $companyName
     * @return false|Entity\TestConnectionResponse|mixed
     */
    public function omniPing($baseUrl = '', $connectionParams = [], $companyName = [])
    {
        $testConnectionOperation = new TestConnectionResponse(
            $baseUrl,
            $connectionParams,
            $companyName['company'] ?? ''
        );

        return current($testConnectionOperation->execute()->getRecords());
    }

    /**
     * Fetch webstores from central
     *
     * @return array
     */
    public function fetchWebStores()
    {
        $webStoreOperation = new Operation\GetStores_GetStores();
        $webStoreOperation->setOperationInput(
            ['storeGetType' => '3', 'searchText' => '', 'includeDetail' => false]
        );

        return $webStoreOperation->execute()->getRecords();
    }

    /**
     * Fetch hierarchies from central
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function fetchWebStoreHierarchies()
    {
        $storeCode = $this->lsr->getWebsiteConfig(
            LSR::SC_SERVICE_STORE,
            $this->getScopeId()
        );
        $hierarchyOperation = new HierarchyView();
        $hierarchyOperation->setOperationInput(
            [
                'storeNo' => $storeCode,
                'batchSize' => 100,
                'fullRepl' => true,
                'lastKey' => '',
                'lastEntryNo' => 0
            ]
        );

        return $hierarchyOperation->execute()->getRecords() ?? [];
    }

    /**
     * Fetch hierarchies from central
     *
     * @return mixed
     * @throws NoSuchEntityException|GuzzleException
     */
    public function fetchWebStoreTenderTypes()
    {
        $storeCode = $this->lsr->getWebsiteConfig(
            LSR::SC_SERVICE_STORE,
            $this->getScopeId()
        );
        $tenderTypeOperation = new LSCTenderType();
        $tenderTypeOperation->setOperationInput(
            [
                'storeNo' => $storeCode,
                'batchSize' => 100,
                'fullRepl' => true,
                'lastKey' => '',
                'lastEntryNo' => 0
            ]
        );

        return $tenderTypeOperation->execute()->getRecords() ?? [];
    }

    /**
     * Make given odata request
     *
     * @param string $action
     * @param string $baseUrl
     * @param array $connectionParams
     * @param array $query
     * @param array $data
     * @param string $method
     * @return mixed|string|null
     * @throws GuzzleException
     * @throws NoSuchEntityException
     */
    public function fetchGivenOdata(
        $action,
        $baseUrl = '',
        $connectionParams = [],
        $query = [],
        $data = [],
        $method = 'POST'
    ) {
        $this->setMissingParameters($baseUrl, $connectionParams, $query);

        return $this->guzzleClient->makeRequest(
            $baseUrl,
            $action,
            $method,
            'odata',
            $connectionParams,
            $query,
            $data
        );
    }

    /**
     * Make odata request
     *
     * @param string $action
     * @param string $responseName
     * @param mixed $data
     * @param string $baseUrl
     * @param array $connectionParams
     * @param array $query
     * @return mixed
     * @throws GuzzleException
     * @throws NoSuchEntityException
     */
    public function makeRequest(
        $action,
        $responseName,
        $data,
        $baseUrl = '',
        $connectionParams = [],
        $query = []
    ) {
        $this->setMissingParameters($baseUrl, $connectionParams, $query);

        return $this->guzzleClient->makeRequest(
            $baseUrl,
            $action,
            'POST',
            'odata',
            $connectionParams,
            $query,
            $data
        );
    }

    /**
     * Fetch codeunit from central
     *
     * @param string $baseUrl
     * @param array $connectionParams
     * @param array $query
     * @param array $data
     * @return \DOMXPath
     * @throws GuzzleException|NoSuchEntityException
     */
    public function fetchOdataV4Xml($baseUrl = '', $connectionParams = [], $query = [], $data = [])
    {
        $this->setMissingParameters($baseUrl, $connectionParams, $query);

        $response = $this->guzzleClient->makeRequest(
            $baseUrl,
            '$metadata',
            'GET',
            'odata',
            $connectionParams,
            $query,
            $data
        );

        $dom = new \DomDocument('1.0');
        $dom->loadXML($response);
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput       = true;
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('edm', 'http://docs.oasis-open.org/odata/ns/edm');

        return $xpath;
    }

    /**
     * Fetch given table data from central using web request 1.0
     *
     * @param string $tableName
     * @param string $baseUrl
     * @param string|null $filterFieldName
     * @param string|null $filterValue
     * @return array
     * @throws NoSuchEntityException
     */
    public function fetchGivenTableData(
        string $tableName,
        string $baseUrl = '',
        ?string $filterFieldName = null,
        ?string $filterValue = null
    ): array {
        $baseUrl = !empty($baseUrl) ? $baseUrl :
            $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_BASE_URL, $this->getScopeId());
        $url = join('/', [$baseUrl, 'WS/Codeunit/RetailWebServices']);
        $url = OmniService::getUrl($url);
        $client = new OmniClient($url);

        $requestXml = new \SimpleXMLElement('<Request/>');
        $requestXml->addChild('Request_ID', 'GET_TABLE_DATA');
        $requestBody = $requestXml->addChild('Request_Body');
        $requestBody->addChild('Table_Name', $tableName);
        $requestBody->addChild('Read_Direction', 'Forward');
        $requestBody->addChild('Max_Number_Of_Records', '0');
        $requestBody->addChild('Ignore_Extra_Fields', '1');

        if ($filterFieldName && $filterValue) {
            $filterBuffer = $requestBody->addChild('WS_Table_Filter_Buffer');
            $filterBuffer->addChild('Field_Index', '1'); // adjust if dynamic
            $filterBuffer->addChild('Field_Name', $filterFieldName);
            $filterBuffer->addChild('Filter', $filterValue);
        }

        $params = [
            'pxmlRequest' => $requestXml->asXML(),
            'pxmlResponse' => '<Response/>'
        ];
        $response = $client->__call('WebRequest', [$params]);
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        $decoded = html_entity_decode($response->pxmlResponse ?? '');
        $response = simplexml_load_string($decoded);

        return $this->formatTableDataResponse($response);
    }

    /**
     * Format table data into a flat array
     *
     * @param \SimpleXMLElement $response
     * @return array
     */
    public function formatTableDataResponse(\SimpleXMLElement $response): array
    {
        $fieldMap = $records = [];

        foreach ($response->Response_Body->WS_Table_Field_Buffer ?? [] as $field) {
            $node = (string)($field->Node_Name ?? '');
            $name = (string)($field->Field_Name ?? '');
            if ($node && $name) {
                $fieldMap[$node] = $name;
            }
        }

        foreach ($response->Response_Body->Table_Data ?? [] as $row) {
            $record = [];
            foreach ($fieldMap as $node => $fieldName) {
                $record[$fieldName] = isset($row->{$node}) ? (string)$row->{$node} : null;
            }
            $records[] = $record;
        }

        return $records;
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
        $lsDiscountAmount = $invoiceCreditMemo->getOrder()->getLsDiscountAmount();

        if ($pointsSpent > 0 || $giftCardAmount > 0 || $lsDiscountAmount > 0) {
            $totalItemsQuantities = $totalItemsInvoice = 0;
            $pointsEarn           = $invoiceCreditMemo->getOrder()->getLsPointsEarn();
            $invoiceCreditMemo->setLsPointsEarn($pointsEarn);
            $invoiceCreditMemo->setLsDiscountAmount($lsDiscountAmount);
            $allVisibleItems = $invoiceCreditMemo->getOrder()->getAllVisibleItems();
            /** @var $item \Magento\Sales\Model\Order\Invoice\Item */
            foreach ($allVisibleItems as $item) {
                if (!$item->getParentItem()) {
                    $totalItemsQuantities = $totalItemsQuantities + $item->getQtyOrdered();
                }
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

            $storeId = $invoiceCreditMemo->getOrder()->getStoreId();

            $pointRate         = ($this->loyaltyHelper->getPointRate($storeId)) ?
                $this->loyaltyHelper->getPointRate($storeId) : 0;
            $totalPointsAmount = $pointsSpent * $pointRate;
            $totalPointsAmount = ($totalPointsAmount / $totalItemsQuantities) * $totalItemsInvoice;
            $pointsSpent       = ($pointsSpent / $totalItemsQuantities) * $totalItemsInvoice;
            $giftCardAmount    = ($giftCardAmount / $totalItemsQuantities) * $totalItemsInvoice;

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
        $scopeId              = $this->lsr->getCurrentWebsiteId();
        $storeTenderTypeArray = $this->getTenderTypes($scopeId);
        if (empty($storeTenderTypeArray)) {
            $storeTenderTypeArray = $this->getTenderTypesDirectly($scopeId);
        }
        if (!empty($storeTenderTypeArray)) {
            foreach ($storeTenderTypeArray as $storeTenderType) {
                $storeTenderTypes[$storeTenderType->getTenderTypeId()] = $storeTenderType->getName();
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

        if ($lsKey == null) {
            $lsKey = $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_LS_KEY, $scopeId);
        }

        if ($this->lsr->validateBaseUrl($baseUrl, $scopeId) && $storeId != '') {
            try {
                $request = $this->formulateTenderTypesRequest($baseUrl, $lsKey, $storeId, $scopeId);
                $result  = $request->execute();

                if ($result != null) {
                    $result = $result->getResult()->getStoreTenderTypes()->getReplStoreTenderType();
                }
            } catch (Exception $e) {
                $this->_logger->critical($e->getMessage());
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
        $request->setOperationInput()->setReplRequest(
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

    /**
     * Get license status html
     *
     * @param string $lsRetailLicenseIsActive
     * @param string $lsRetailLicenseUnitEcomIsActive
     * @return string
     * @throws NoSuchEntityException
     */
    public function getLicenseStatusHtml($lsRetailLicenseIsActive, $lsRetailLicenseUnitEcomIsActive)
    {
        if ($lsRetailLicenseIsActive === true && $lsRetailLicenseUnitEcomIsActive === true) {
            $this->lsr->setLicenseValidity("1");
            $licenseValidity = 1;
        } else {
            $licenseValidity = 0;
            $this->lsr->setLicenseValidity("0");
        }

        $validClass   = 'valid-license';
        $invalidClass = 'invalid-license';
        $licenseHtml  = "<div class='1 control-value ";
        $licenseHtml  .= $licenseValidity == "1" ? $validClass : $invalidClass;
        $licenseHtml  .= "'>";
        $licenseHtml  .= $licenseValidity == "1" ? __('Valid') : __('Invalid');
        $licenseHtml  .= "</div>";

        return $licenseHtml;
    }

    /**
     * Get base url
     *
     * @param string $url
     * @return string
     */
    public function getBaseUrl($url)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $parts = parse_url($url);

        $baseUrl = $parts['scheme'] . '://' . $parts['host'];

        if (isset($parts['port'])) {
            $baseUrl .= ':' . $parts['port'];
        }

        return $baseUrl;
    }

    /**
     * Get persisted token
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getAvailableToken()
    {
        return $this->scopeConfig->getValue(
            LSR::SC_SERVICE_TOKEN,
            ScopeInterface::SCOPE_WEBSITES,
            $this->storeManager->getStore()->getWebsiteId()
        );
    }

    /**
     * Get token expiry
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getTokenExpiry()
    {
        return $this->scopeConfig->getValue(
            LSR::SC_SERVICE_TOKEN_EXPIRY,
            ScopeInterface::SCOPE_WEBSITES,
            $this->storeManager->getStore()->getWebsiteId()
        );
    }

    /**
     * Persist valid token
     *
     * @param array $token
     * @throws NoSuchEntityException
     */
    public function persistValidToken($token)
    {
        if (isset($token['access_token']) && isset($token['expires_in'])) {
            $this->configWriter->save(
                LSR::SC_SERVICE_TOKEN,
                $token['access_token'],
                ScopeInterface::SCOPE_WEBSITES,
                $this->storeManager->getStore()->getWebsiteId()
            );

            $issuedAt = time();
            $expiresAt = $issuedAt + $token['expires_in'];
            $this->configWriter->save(
                LSR::SC_SERVICE_TOKEN_EXPIRY,
                $expiresAt,
                ScopeInterface::SCOPE_WEBSITES,
                $this->storeManager->getStore()->getWebsiteId()
            );
        }
    }

    /**
     * Fetch valid token
     *
     * @param string $tenant
     * @param string $clientId
     * @param string $clientSecret
     * @return string
     * @throws NoSuchEntityException
     */
    public function fetchValidToken($tenant, $clientId, $clientSecret)
    {
        $token = $this->getAvailableToken();
        $expiry = $this->getTokenExpiry();
        $currentTime = time();
        $buffer = 300; // 5 minutes

        if ($token && $expiry && $currentTime < $expiry - $buffer) {
            return $token;
        }

        $token = $this->tokenRequestService->requestToken($tenant, $clientId, $clientSecret);
        $this->persistValidToken($token);

        return $token['access_token'] ?? null;
    }

    /**
     * Get scope_id
     *
     * @return int|mixed
     * @throws NoSuchEntityException
     */
    public function getScopeId()
    {
        return $this->request->getParam('website') ?? $this->storeManager->getStore()->getWebsiteId();
    }
}
