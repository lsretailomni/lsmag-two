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
use Psr\Log\LoggerInterface;

class LoadHierarchy extends Action
{
    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param LSR $lsr
     * @param Data $helper
     * @param LoggerInterface $logger
     */
    public function __construct(
        public Context $context,
        public JsonFactory $resultJsonFactory,
        public LSR $lsr,
        public Data $helper,
        public LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Collect relations data
     *
     * @return Json
     * @throws GuzzleException
     */
    public function execute()
    {
        $optionList = [];
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

            if ($centralType == '1') {
                $baseUrl = $this->helper->getBaseUrl($baseUrl);
            }

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
                ['companyName' => $companyName],
                $scopeId
            )) {
                $hierarchies = $this->helper->fetchWebStoreHierarchies(
                    $baseUrl,
                    $connectionParams,
                    ['companyName' => $companyName],
                    $storeId
                );

                if (!empty($hierarchies)) {
                    $optionList = [['value' => '', 'label' => __('Please select your hierarchy code')]];
                    foreach ($hierarchies as $hierarchy) {
                        $optionList[] = [
                            'value' => $hierarchy->getHierarchyCode(),
                            'label' => $hierarchy->getDescription()
                        ];
                    }
                } else {
                    $optionList = [['value' => '', 'label' => __('No hierarchy code found for the selected store')]];
                }
            }
        } catch (Exception $e) {
            $this->logger->critical($e);
        }
        $result = $this->resultJsonFactory->create();
        return $result->setData(['success' => true, 'hierarchy' => $optionList]);
    }

    /**
     * Check controller access permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ls_Core::config');
    }
}
