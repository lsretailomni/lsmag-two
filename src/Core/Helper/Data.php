<?php

namespace Ls\Core\Helper;

use Exception;
use Ls\Core\Model\LSR;
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
 * Class Data
 * @package Ls\Core\Helper
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
     * Data constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $state
     * @param WriterInterface $configWriter
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        TransportBuilder $transportBuilder,
        StateInterface $state,
        WriterInterface $configWriter
    ) {
        $this->storeManager      = $storeManager;
        $this->transportBuilder  = $transportBuilder;
        $this->inlineTranslation = $state;
        $this->configWriter      = $configWriter;
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
        $email = $this->scopeConfig->getValue(
            LSR::LS_DISASTER_RECOVERY_NOTIFICATION_EMAIL,
            ScopeInterface::SCOPE_STORES,
            $this->storeManager->getStore()->getId()
        );
        return $email;
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function isNotificationEmailSent()
    {
        $email = $this->scopeConfig->getValue(
            LSR::LS_DISASTER_RECOVERY_NOTIFICATION_EMAIL_STATUS,
            ScopeInterface::SCOPE_STORES,
            $this->storeManager->getStore()->getId()
        );
        return $email;
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
                [
                    'features'       => SOAP_SINGLE_ELEMENT_ARRAYS,
                    'cache_wsdl'     => WSDL_CACHE_NONE,
                    'stream_context' => $context
                ]
            );
            // @codingStandardsIgnoreEnd
            if ($soapClient) {
                if ($this->isNotificationEmailSent()) {
                    $this->setNotificationEmailSent(0);
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

    public function sendEmail($message)
    {
        $templateId = 'ls_omni_disaster_recovery_email';

        $toEmail = $this->getNotificationEmail();

        try {
            // template variables pass here
            $templateVars = [
                'message' => $message
            ];

            $storeId = $this->storeManager->getStore()->getId();

            $this->inlineTranslation->suspend();

            $storeScope      = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $templateOptions = [
                'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $storeId
            ];
            $transport       = $this->transportBuilder->setTemplateIdentifier($templateId, $storeScope)
                ->setTemplateOptions($templateOptions)
                ->setTemplateVars($templateVars)
                ->addTo($toEmail)
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
            $this->setNotificationEmailSent(1);
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }
}
