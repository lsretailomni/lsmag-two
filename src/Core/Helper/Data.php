<?php

namespace Ls\Core\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\CacheHelper;
use Magento\Framework\App\Area;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use SoapClient;

/**
 * Magento configuration related class
 */
class Data extends AbstractHelper
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
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var CacheHelper
     */
    private $cacheHelper;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $state
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param CacheHelper $cacheHelper
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        TransportBuilder $transportBuilder,
        StateInterface $state,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        CacheHelper $cacheHelper
    ) {
        $this->storeManager      = $storeManager;
        $this->transportBuilder  = $transportBuilder;
        $this->inlineTranslation = $state;
        $this->configWriter      = $configWriter;
        $this->cacheTypeList     = $cacheTypeList;
        $this->cacheHelper       = $cacheHelper;
        parent::__construct($context);
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
        return $enabled === '1' or $enabled === 1;
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
        return $enabled === '1' or $enabled === 1;
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
        return $this->scopeConfig->getValue(
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
     * @param $url
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isEndpointResponding($url)
    {
        $opts    = [
            'http' => [
                'timeout' => floatval($this->scopeConfig->getValue(
                    LSR::SC_SERVICE_TIMEOUT,
                    ScopeInterface::SCOPE_STORES,
                    $this->storeManager->getStore()->getId()
                ))
            ]
        ];
        $context = stream_context_create($opts);
        try {
            // @codingStandardsIgnoreStart
            $soapClient = new SoapClient(
                $url . '?singlewsdl',
                array_merge(['stream_context' => $context], $this->cacheHelper->getWsdlOptions())

            );
            // @codingStandardsIgnoreEnd
            if ($soapClient) {
                if ($this->isNotificationEmailSent()) {
                    $this->setNotificationEmailSent(0);
                    $this->flushCache('config');
                }
                return true;
            }
        } catch (Exception $e) {
            $this->_logger->critical($e->getMessage());
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
            $this->flushCache('config');
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }

    /**
     * @param $typeCode
     */
    public function flushCache($typeCode)
    {
        $this->cacheTypeList->cleanType($typeCode);
    }
}
