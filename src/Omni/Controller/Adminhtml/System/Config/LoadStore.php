<?php

namespace Ls\Omni\Controller\Adminhtml\System\Config;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Operation\Ping;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Service as OmniService;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use \Ls\Omni\Client\Ecommerce\Operation\StoresGetAll;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
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
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        RawFactory $resultRawFactory,
        WriterInterface $configWriter,
        LSR $lsr,
        LoggerInterface $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->configWriter = $configWriter;
        $this->lsr = $lsr;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Collect relations data
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $pong = 'Omni Ping failed. Please try with valid service base URL.';
        $option_array = [['value' => '', 'label' => __('Please select your web store')]];
        try {
            $baseUrl = $this->getRequest()->getParam('baseUrl');
            $stores = $this->getStores($baseUrl);
            if (!empty($stores)) {
                $pong = $this->omniPing($baseUrl);
                foreach ($stores as $store) {
                    $option_array[] = ['value' => $store->getId(), 'label' => $store->getDescription()];
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();
        return $result->setData(['success' => true, 'store' => $option_array, 'pong' => $pong]);
    }

    /**
     * @param $baseUrl
     * @return array|\Ls\Omni\Client\Ecommerce\Entity\ArrayOfStore|\Ls\Omni\Client\Ecommerce\Entity\StoresGetAllResponse|\Ls\Omni\Client\ResponseInterface
     */
    public function getStores($baseUrl)
    {
        if ($this->lsr->validateBaseUrl($baseUrl)) {
            //@codingStandardsIgnoreStart
            $service_type = new ServiceType(StoresGetAll::SERVICE_TYPE);
            $url = OmniService::getUrl($service_type, $baseUrl);
            $client = new OmniClient($url, $service_type);
            $getStores = new StoresGetAll();
            //@codingStandardsIgnoreEnd
            $getStores->setClient($client);
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
     * @param $baseUrl
     * @return mixed
     */
    public function omniPing($baseUrl)
    {
        //@codingStandardsIgnoreStart
        $service_type = new ServiceType(StoresGetAll::SERVICE_TYPE);
        $url = OmniService::getUrl($service_type, $baseUrl);
        $client = new OmniClient($url, $service_type);
        $ping = new Ping();
        //@codingStandardsIgnoreEnd
        $ping->setClient($client);
        $client->setClassmap($ping->getClassMap());
        $result = $ping->execute();
        $pong = $result->getResult();
        return $pong;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ls_Omni::config');
    }
}