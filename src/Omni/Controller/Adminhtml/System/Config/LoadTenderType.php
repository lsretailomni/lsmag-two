<?php
declare(strict_types=1);

namespace Ls\Omni\Controller\Adminhtml\System\Config;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
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
            $baseUrl = $this->getRequest()->getParam('baseUrl');
            $tenant = $this->getRequest()->getParam('tenant');
            $clientId = $this->getRequest()->getParam('client_id');
            $clientSecret = $this->getRequest()->getParam('client_secret');
            $companyName = $this->getRequest()->getParam('company_name');
            $environmentName = $this->getRequest()->getParam('environment_name');
            $centralType = $this->getRequest()->getParam('central_type');
            $webServiceUri = $this->getRequest()->getParam('web_service_uri');
            $odataUri = $this->getRequest()->getParam('odata_uri');
            $username = $this->getRequest()->getParam('username');
            $password = $this->getRequest()->getParam('password');
            $scopeId = $this->getRequest()->getParam('scopeId');
            $storeId = $this->getRequest()->getParam('storeId');
            $connectionParams = [
                'tenant' => $tenant,
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
                'environmentName' => $environmentName,
                'centralType' => $centralType,
                'webServiceUri' => $webServiceUri,
                'odataUri' => $odataUri,
                'username' => $username,
                'password' => $password
            ];

            if ($this->lsr->validateBaseUrl(
                $baseUrl,
                $connectionParams,
                ['company' => $companyName],
                $scopeId
            )) {
                $tenderTypes = $this->helper->fetchWebStoreTenderTypes(
                    $baseUrl,
                    $connectionParams,
                    ['company' => $companyName],
                    $storeId
                );

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
            }
        } catch (Exception $e) {
            $this->logger->critical($e);
        }
        $result = $this->resultJsonFactory->create();

        return $result->setData(['success' => true, 'storeTenderTypes' => $optionList]);
    }
}
