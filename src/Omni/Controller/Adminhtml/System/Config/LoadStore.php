<?php

namespace Ls\Omni\Controller\Adminhtml\System\Config;

use Exception;
use Ls\Core\Model\LSR;
use Ls\Omni\Client\Ecommerce\Entity\ArrayOfStore;
use Ls\Omni\Client\Ecommerce\Entity\StoresGetAllResponse;
use Ls\Omni\Client\Ecommerce\Operation\StoresGetAll;
use Ls\Omni\Client\ResponseInterface;
use Ls\Omni\Helper\Data;
use Ls\Omni\Service\Service as OmniService;
use Ls\Omni\Service\ServiceType;
use Ls\Omni\Service\Soap\Client as OmniClient;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Psr\Log\LoggerInterface;

/**
 * Class LoadStore
 * @package Ls\Omni\Controller\Adminhtml\System\Config
 */
class LoadStore extends Action
{

    /**
     * @var JsonFactory
     */
    public $resultJsonFactory;

    /**
     * @var RawFactory
     */
    public $resultRawFactory;

    /**
     * @var WriterInterface
     */
    public $configWriter;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var Data
     */
    public $helper;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * LoadStore constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param RawFactory $resultRawFactory
     * @param WriterInterface $configWriter
     * @param LSR $lsr
     * @param Data $helper
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        RawFactory $resultRawFactory,
        WriterInterface $configWriter,
        LSR $lsr,
        Data $helper,
        LoggerInterface $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory  = $resultRawFactory;
        $this->configWriter      = $configWriter;
        $this->lsr               = $lsr;
        $this->helper            = $helper;
        $this->logger            = $logger;
        parent::__construct($context);
    }

    /**
     * Collect relations data
     *
     * @return Json
     */
    public function execute()
    {
        $pong                 = 'Omni Ping failed. Please try with valid service base URL.';
        $hierarchyPlaceholder = [
            ['value' => '', 'label' => __('No hierarchy code found for the selected store')]
        ];
        $option_array         = [];
        try {
            $baseUrl   = $this->getRequest()->getParam('baseUrl');
            $lsKey     = $this->getRequest()->getParam('lsKey');
            $websiteId = $this->getRequest()->getParam('websiteId');
            $stores    = $this->getStores($baseUrl, $lsKey);
            if (!empty($stores)) {
                $option_array = [['value' => '', 'label' => __('Please select your web store')]];
                $pong         = $this->helper->omniPing($baseUrl, $lsKey);
                $version      = $this->helper->parsePingResponseAndSaveToConfigData($pong, $websiteId);
                foreach ($stores as $store) {
                    $option_array[] = ['value' => $store->getId(), 'label' => $store->getDescription()];
                }
            } else {
                $option_array = [['value' => '', 'label' => __('No store found for entered omni api url')]];
            }
        } catch (Exception $e) {
            $this->logger->critical($e);
        }
        /** @var Json $result */
        $result = $this->resultJsonFactory->create();
        return $result->setData(
            [
                'success' => true, 'store' => $option_array, 'pong' => $pong, 'hierarchy' => $hierarchyPlaceholder,
                'version' => $version
            ]
        );
    }

    /**
     * @param $baseUrl
     * @param $lsKey
     * @return array|ArrayOfStore|StoresGetAllResponse|ResponseInterface
     */
    public function getStores($baseUrl, $lsKey)
    {
        if ($this->lsr->validateBaseUrl($baseUrl)) {
            //@codingStandardsIgnoreStart
            $service_type = new ServiceType(StoresGetAll::SERVICE_TYPE);
            $url          = OmniService::getUrl($service_type, $baseUrl);
            $client       = new OmniClient($url, $service_type);
            $getStores    = new StoresGetAll();
            //@codingStandardsIgnoreEnd
            $getStores->setClient($client);
            $getStores->setToken($lsKey);
            $client->setClassmap($getStores->getClassMap());
            $result = $getStores->execute();
            if ($result != null) {
                $result = $result->getResult();
            }
            if (!is_array($result)) {
                return $resultArray[] = $result;
            } else {
                return $result;
            }
        }
        return [];
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ls_Omni::config');
    }
}
