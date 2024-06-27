<?php

namespace Ls\Core\Model;

use Exception;
use \Ls\Omni\Client\Ecommerce\Entity\PingResponse;
use \Ls\Omni\Client\Ecommerce\Operation\Ping;
use \Ls\Omni\Client\Ecommerce\Operation\StoresGetAll;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Helper\CacheHelper;
use \Ls\Omni\Model\Cache\Type;
use \Ls\Omni\Service\Service as OmniService;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigCollectionFactory;
use Magento\Config\Model\ResourceModel\Config\Data\Collection as ConfigDataCollection;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Magento configuration related class
 */
class Data
{
    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;
    /**
     * @var StateInterface
     */
    private $inlineTranslation;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var CacheHelper
     */
    private $cacheHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /** @var ConfigCollectionFactory */
    public $configDataCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @param StoreManagerInterface $storeManager
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $state
     * @param WriterInterface $configWriter
     * @param ConfigCollectionFactory $configDataCollectionFactory
     * @param CacheHelper $cacheHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        TransportBuilder $transportBuilder,
        StateInterface $state,
        WriterInterface $configWriter,
        ConfigCollectionFactory $configDataCollectionFactory,
        CacheHelper $cacheHelper,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->storeManager                = $storeManager;
        $this->transportBuilder            = $transportBuilder;
        $this->inlineTranslation           = $state;
        $this->configWriter                = $configWriter;
        $this->cacheHelper                 = $cacheHelper;
        $this->scopeConfig                 = $scopeConfig;
        $this->configDataCollectionFactory = $configDataCollectionFactory;
        $this->logger                      = $logger;
    }

    /**
     * @return bool
     * @throws NoSuchEntityException
     */
    public function enabled()
    {
        $enabled = $this->scopeConfig->getValue(
            LSR::SC_SERVICE_ENABLE,
            ScopeInterface::SCOPE_STORES,
            $this->storeManager->getStore()->getId()
        );
        return $enabled === '1' || $enabled === 1;
    }

    /**
     * @return bool
     * @throws NoSuchEntityException
     */
    public function checkNotificationEmailEnabled()
    {
        $enabled = $this->scopeConfig->getValue(
            LSR::LS_DISASTER_RECOVERY_STATUS,
            ScopeInterface::SCOPE_STORES,
            $this->storeManager->getStore()->getId()
        );
        return $enabled === '1' || $enabled === 1;
    }


    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getNotificationEmail()
    {
        return $this->scopeConfig->getValue(
            LSR::LS_DISASTER_RECOVERY_NOTIFICATION_EMAIL,
            ScopeInterface::SCOPE_STORES,
            $this->storeManager->getStore()->getId()
        );
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function isNotificationEmailSent()
    {
        return $this->getConfigValueFromDb(
            LSR::LS_DISASTER_RECOVERY_NOTIFICATION_EMAIL_STATUS,
            ScopeInterface::SCOPE_STORES,
            $this->storeManager->getStore()->getId()
        );
    }

    /**
     * @param $status
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function setNotificationEmailSent($status)
    {
        $this->configWriter->save(
            LSR::LS_DISASTER_RECOVERY_NOTIFICATION_EMAIL_STATUS,
            $status,
            ScopeInterface::SCOPE_STORES,
            $this->storeManager->getStore()->getId()
        );
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getStoreEmail()
    {
        return $this->scopeConfig->getValue(
            'trans_email/ident_general/email',
            ScopeInterface::SCOPE_STORES,
            $this->storeManager->getStore()->getId()
        );
    }

    /**
     * Get commerce service heartbeat timeout
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCommerceServiceHeartbeatTimeout()
    {
        return $this->scopeConfig->getValue(
            LSR::SC_SERVICE_HEART_BEAT_TIMEOUT,
            ScopeInterface::SCOPE_STORES,
            $this->storeManager->getStore()->getId()
        );
    }

    /**
     * Function for commerce service ping
     *
     * @param $baseUrl
     * @param $lsKey
     * @return PingResponse|ResponseInterface
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

        return $ping->execute();
    }

   /**
    * Checks heartbeat of commerce service
    *
    * @param $url
    * @param $lsKey
    * @param $websiteId
    * @return bool
    * @throws NoSuchEntityException
    */
    public function isEndpointResponding($url, $lsKey, $websiteId)
    {
        try {
            $cacheId       = LSR::PING_RESPONSE_CACHE . $websiteId;
            $cachedContent = $this->cacheHelper->getCachedContent($cacheId);

            if (!empty($cachedContent)) {
                return true;
            }

            $response = $this->omniPing($url, $lsKey);

            if ($response &&
                strpos($response->getResult(), 'ERROR') === false &&
                strpos($response->getResult(), 'Failed') === false
            ) {
                $this->cacheHelper->persistContentInCache(
                    $cacheId,
                    $response->getResult(),
                    [Type::CACHE_TAG],
                    $this->getCommerceServiceHeartbeatTimeout()
                );

                if ($this->isNotificationEmailSent()) {
                    $this->setNotificationEmailSent(0);
                }
                $string =  $response->getResult();
                //Set license validity
                if(strpos($string,'CL:') !== false) {
                    if(strpos($response->getResult(), 'CL:True EL:True') !== false) {
                        $this->setLicenseStatus("1");
                    } else {
                        $this->setLicenseStatus("0");
                    }
                }

                return true;
            }
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
            if ($this->checkNotificationEmailEnabled() && !$this->isNotificationEmailSent()) {
                $this->sendEmail($e->getMessage());
            }
        }

        return false;
    }

    /**
     * @param $message
     * @throws NoSuchEntityException
     */
    public function sendEmail($message)
    {
        $templateId = LSR::EMAIL_TEMPLATE_ID_FOR_OMNI_SERVICE_DOWN;

        $toEmail    = $this->getNotificationEmail();
        $storeEmail = $this->getStoreEmail();
        try {
            // template variables pass here
            $templateVars = [
                'message' => $message
            ];

            $storeId = $this->storeManager->getStore()->getId();

            $this->inlineTranslation->suspend();

            $storeScope      = ScopeInterface::SCOPE_STORE;
            $templateOptions = [
                'area'  => Area::AREA_FRONTEND,
                'store' => $storeId
            ];
            $sender          = [
                'name'  => $this->storeManager->getStore()->getName(),
                'email' => $storeEmail,
            ];
            $transport       = $this->transportBuilder->setTemplateIdentifier($templateId, $storeScope)
                ->setTemplateOptions($templateOptions)
                ->setTemplateVars($templateVars)
                ->addTo($toEmail)
                ->setFromByScope($sender, $storeId)
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
            $this->setNotificationEmailSent(1);
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }

    /**
     * Use this where we want to retrieve non-cached value from core_config_data
     * i-e like in processing crons.
     * @param $path
     * @param string $scope
     * @param int $scopeId
     * @return mixed|null
     */
    public function getConfigValueFromDb($path, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0)
    {
        if ($this->storeManager->isSingleStoreMode()) {
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeId = 0;
        }
        /** @var ConfigDataCollection $configDataCollection */
        $configDataCollection = $this->configDataCollectionFactory->create();
        $configDataCollection->addFieldToFilter('scope', $scope);
        $configDataCollection->addFieldToFilter('scope_id', $scopeId);
        $configDataCollection->addFieldToFilter('path', $path);
        if ($configDataCollection->count() !== 0) {
            return $configDataCollection->getFirstItem()->getValue();
        }
        return null;
    }

    /**
     * Set license status
     *
     * @param $status
     * @throws NoSuchEntityException
     */
    public function setLicenseStatus($status)
    {
        $this->configWriter->save(
            LSR::SC_SERVICE_LICENSE_VALIDITY,
            $status,
            ScopeInterface::SCOPE_WEBSITES,
            $this->storeManager->getStore()->getWebsiteId()
        );
    }
}
