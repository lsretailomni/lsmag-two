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
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class LoadStore extends Action
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
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $hierarchyPlaceholder = [
            ['value' => '', 'label' => __('No hierarchy code found for the selected store')]
        ];
        $optionList         = [
            ['value' => '', 'label' => __('No store found for entered omni api url')]
        ];
        $lsRetailLicenseIsActive = $lsRetailLicenseUnitEcomIsActive = $lsCentralVersion = '';
        try {
            $baseUrl = $this->getRequest()->getParam('baseUrl');
            $scopeId = $this->getRequest()->getParam('scopeId');
            $tenant = $this->getRequest()->getParam('tenant');
            $clientId = $this->getRequest()->getParam('client_id');
            $clientSecret = $this->getRequest()->getParam('client_secret');
            $companyName = $this->getRequest()->getParam('company_name');
            $environmentName = $this->getRequest()->getParam('environment_name');

            $baseUrl = $this->helper->getBaseUrl($baseUrl);
            $connectionParams = [
                'tenant' => $tenant,
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
                'environmentName' => $environmentName,
            ];
            $pong = $this->helper->omniPing(
                $baseUrl,
                $connectionParams,
                ['companyName' => $companyName]
            );

            if (!empty($pong)) {
                list($lsCentralVersion, $lsRetailLicenseIsActive, $lsRetailLicenseUnitEcomIsActive) =
                    $this->helper->parsePingResponseAndSaveToConfigData($pong, $scopeId);

                if ($this->lsr->validateBaseUrl(
                    $baseUrl,
                    $connectionParams,
                    ['company' => $companyName],
                    $scopeId
                )) {
                    $stores = $this->helper->fetchWebStores();

                    if (!empty($stores)) {
                        $optionList = null;
                        $optionList = [['value' => '', 'label' => __('Please select your web store')]];
                        foreach ($stores as $store) {
                            $optionList[] = ['value' => $store->getNo(), 'label' => $store->getName()];
                        }
                    }
                }
            } else {
                $pong = __('Unfortunately, commerce service ping fails. Please try with valid connection details.');
            }
        } catch (Exception $e) {
            $this->logger->critical($e);
        }
        $result = $this->resultJsonFactory->create();
        $licenseHtml = $this->helper->getLicenseStatusHtml($lsRetailLicenseIsActive, $lsRetailLicenseUnitEcomIsActive);

        return $result->setData(
            [
                'success' => true,
                'store' => $optionList,
                'hierarchy' => $hierarchyPlaceholder,
                'version' => $lsCentralVersion,
                'pong' => $pong->getData(),
                'licenseHtml' => $licenseHtml
            ]
        );
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
