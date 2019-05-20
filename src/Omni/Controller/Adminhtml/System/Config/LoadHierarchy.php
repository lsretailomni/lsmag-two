<?php

namespace Ls\Omni\Controller\Adminhtml\System\Config;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\ReplRequest;
use \Ls\Omni\Client\Ecommerce\Operation\ReplEcommHierarchy;

;

use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Service as OmniService;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use \Ls\Omni\Client\Ecommerce\Operation\StoresGetAll;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

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
     * LoadStore constructor.
     * @param Context $context
     * @param LSR $lsr
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        LSR $lsr
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->lsr = $lsr;
        parent::__construct($context);
    }

    /**
     * Collect relations data
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $option_array = [['value' => '', 'label' => __('Please select your hierarchy code')]];
        try {
            $baseUrl = $this->getRequest()->getParam('baseUrl');
            $storeId = $this->getRequest()->getParam('storeId');
            $hierarchies = $this->getHierarchy($baseUrl, $storeId);
            if (!empty($hierarchies)){
                foreach ($hierarchies as $hierarchy) {
                    $option_array[] = ['value' => $hierarchy->getId(), 'label' => $hierarchy->getDescription()];
                }
            }
        } catch (\Exception $e) {
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
        }
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();
        return $result->setData(['success' => true, 'hierarchy' => $option_array]);
    }


    public function getHierarchy($baseUrl, $storeId)
    {
        if ($this->lsr->validateBaseUrl($baseUrl) && $storeId != "") {
            //@codingStandardsIgnoreStart
            $service_type = new ServiceType(StoresGetAll::SERVICE_TYPE);
            $url = OmniService::getUrl($service_type, $baseUrl);
            $client = new OmniClient($url, $service_type);
            $request = new ReplEcommHierarchy();
            $request->setClient($client);
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

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ls_Omni::config');
    }
}

?>