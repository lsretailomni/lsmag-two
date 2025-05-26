<?php

namespace Ls\Omni\Controller\Adminhtml\System\Config;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Operation\LSCTenderType;
use \Ls\Omni\Helper\Data;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json as SerializerJson;
use Psr\Log\LoggerInterface;

class LoadTenderType extends Action
{
    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param LSR $lsr
     * @param Data $helper
     * @param SerializerJson $serializerJson
     * @param LoggerInterface $logger
     */
    public function __construct(
        public Context $context,
        public JsonFactory $resultJsonFactory,
        public LSR $lsr,
        public Data $helper,
        public SerializerJson $serializerJson,
        public LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Get tender type Data
     *
     * @return Json
     * @throws GuzzleException
     */
    public function execute()
    {
        $optionList = $tenderTypes = [];
        try {
            $storeId = $this->getRequest()->getParam('storeId');
            $baseUrl = $this->getRequest()->getParam('baseUrl');
            $tenant = $this->getRequest()->getParam('tenant');
            $clientId = $this->getRequest()->getParam('client_id');
            $clientSecret = $this->getRequest()->getParam('client_secret');
            $companyName = $this->getRequest()->getParam('company_name');
            $environmentName = $this->getRequest()->getParam('environment_name');
            $scopeId = $this->getRequest()->getParam('scopeId');
            $baseUrl = $this->helper->getBaseUrl($baseUrl);
            $connectionParams = [
                'tenant' => $tenant,
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
                'environmentName' => $environmentName,
            ];

            if ($this->lsr->validateBaseUrl(
                $baseUrl,
                $connectionParams,
                ['company' => $companyName],
                $scopeId
            )) {
                $tenderTypeOperation = new LSCTenderType(
                    $baseUrl,
                    $connectionParams,
                    $companyName,
                );
                $tenderTypeOperation->setOperationInput(
                    [
                        'storeNo' => $storeId,
                        'batchSize' => 100,
                        'fullRepl' => true,
                        'lastKey' => '',
                        'lastEntryNo' => 0
                    ]
                );

                $tenderTypes = $tenderTypeOperation->execute()->getRecords();
            }

            if (!empty($tenderTypes)) {
                $paymentTenderTypesArray = $this->lsr->getStoreConfig(
                    LSR::LSR_PAYMENT_TENDER_TYPE_MAPPING,
                    $this->lsr->getCurrentStoreId()
                );

                if (!is_array($paymentTenderTypesArray)) {
                    $paymentTenderTypesArray = $this->serializerJson->unserialize($paymentTenderTypesArray);
                }
                $optionList = [['value' => '', 'label' => __('Select tender type')]];
                foreach ($tenderTypes as $tenderType) {
                    $keyId        = '';
                    $tenderTypeId = $tenderType->getCode();
                    if (!empty($paymentTenderTypesArray)) {
                        $key = array_search(
                            $tenderTypeId,
                            array_column($paymentTenderTypesArray, 'tender_type')
                        );
                        if (is_numeric($key)) {
                            $keys  = array_keys($paymentTenderTypesArray);
                            $keyId = $keys[$key];
                        }
                    }

                    $optionList[] = [
                        'value'       => $tenderTypeId,
                        'label'       => $tenderType->getDescription(),
                        'selectedKey' => $keyId
                    ];
                }
            } else {
                $optionList = [['value' => '', 'label' => __('No tender type found')]];
            }
        } catch (Exception $e) {
            $this->logger->critical($e);
        }
        $result = $this->resultJsonFactory->create();

        return $result->setData(['success' => true, 'storeTenderTypes' => $optionList]);
    }
}
