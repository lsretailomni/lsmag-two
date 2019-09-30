<?php

namespace Ls\Omni\Controller\Adminhtml\System\Config;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\ReplRequest;
use \Ls\Omni\Client\Ecommerce\Operation\ReplEcommHierarchy;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Service as OmniService;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use \Ls\Omni\Client\Ecommerce\Operation\StoresGetAll;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;

/**
 * Class LoadHierarchy
 * @package Ls\Omni\Controller\Adminhtml\System\Config
 */
class LoadHierarchy extends Action
{

    /**
     * @var JsonFactory
     */
    public $resultJsonFactory;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * LoadHierarchy constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param LSR $lsr
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        LSR $lsr,
        LoggerInterface $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
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
        $option_array = [];
        try {
            $baseUrl = $this->getRequest()->getParam('baseUrl');
            $storeId = $this->getRequest()->getParam('storeId');
            $lsKey   = $this->getRequest()->getParam('lsKey');
            $hierarchies = $this->getHierarchy($baseUrl, $storeId, $lsKey);
            if (!empty($hierarchies)) {
                $option_array = [['value' => '', 'label' => __('Please select your hierarchy code')]];
                foreach ($hierarchies as $hierarchy) {
                    $option_array[] = ['value' => $hierarchy->getId(), 'label' => $hierarchy->getDescription()];
                }
            } else {
                $option_array = [['value' => '', 'label' => __('No hierarchy code found for the selected store')]];
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();
        return $result->setData(['success' => true, 'hierarchy' => $option_array]);
    }

    /**
     * @param $baseUrl
     * @param $storeId
     * @param $lsKey
     * @return array|\Ls\Omni\Client\Ecommerce\Entity\ReplEcommHierarchyResponse|\Ls\Omni\Client\Ecommerce\Entity\ReplHierarchy[]|\Ls\Omni\Client\ResponseInterface
     */
    public function getHierarchy($baseUrl, $storeId, $lsKey)
    {
        if ($this->lsr->validateBaseUrl($baseUrl) && $storeId != "") {
            //@codingStandardsIgnoreStart
            $service_type = new ServiceType(StoresGetAll::SERVICE_TYPE);
            $url = OmniService::getUrl($service_type, $baseUrl);
            $client = new OmniClient($url, $service_type);
            $request = new ReplEcommHierarchy();
            $request->setClient($client);
            $request->setToken($lsKey);
            $client->setClassmap($request->getClassMap());
            $request->getOperationInput()->setReplRequest((new ReplRequest())->setBatchSize(100)
                ->setFullReplication(1)
                ->setLastKey('')
                ->setStoreId($storeId));
            //@codingStandardsIgnoreEnd
            $result = $request->execute();
            if ($result != null) {
                $result = $result->getResult()->getHierarchies()->getReplHierarchy();
            }
            if (!is_array($result)) {
                $resultArray[] = $result;
                return $resultArray;
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
