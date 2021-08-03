<?php

namespace Ls\Omni\Controller\Adminhtml\System\Config;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\Data;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json as SerializerJson;
use Psr\Log\LoggerInterface;

/**
 * Class for loading tender type
 */
class LoadTenderType extends Action
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
     * @var Data
     */
    public $helper;

    /**
     * @var SerializerJson
     */
    public $serializerJson;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param LSR $lsr
     * @param Data $helper
     * @param SerializerJson $serializerJson
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        LSR $lsr,
        Data $helper,
        SerializerJson $serializerJson,
        LoggerInterface $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->lsr               = $lsr;
        $this->serializerJson    = $serializerJson;
        $this->logger            = $logger;
        $this->helper            = $helper;
        parent::__construct($context);
    }

    /**
     * Get tender type Data
     *
     * @return Json
     */
    public function execute()
    {
        $option_array = [];
        try {
            $baseUrl     = $this->getRequest()->getParam('baseUrl');
            $storeId     = $this->getRequest()->getParam('storeId');
            $lsKey       = $this->getRequest()->getParam('lsKey');
            $scopeId     = $this->getRequest()->getParam('scopeId');
            $tenderTypes = $this->helper->getTenderTypesDirectly($scopeId, $storeId, $baseUrl, $lsKey);
            if (!empty($tenderTypes)) {
                $paymentTenderTypesArray = $this->lsr->getStoreConfig(
                    LSR::LSR_PAYMENT_TENDER_TYPE_MAPPING,
                    $this->lsr->getCurrentStoreId()
                );

                if (!is_array($paymentTenderTypesArray)) {
                    $paymentTenderTypesArray = $this->serializerJson->unserialize($paymentTenderTypesArray);
                }
                $option_array = [['value' => '', 'label' => __('Select tender type')]];
                foreach ($tenderTypes as $tenderType) {
                    $keyId        = '';
                    $tenderTypeId = $tenderType->getOmniTenderTypeId();
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

                    $option_array[] = [
                        'value'       => $tenderTypeId,
                        'label'       => $tenderType->getName(),
                        'selectedKey' => $keyId
                    ];
                }
            } else {
                $option_array = [['value' => '', 'label' => __('No tender type found')]];
            }
        } catch (Exception $e) {
            $this->logger->critical($e);
        }
        /** @var Json $result */
        $result = $this->resultJsonFactory->create();
        return $result->setData(['success' => true, 'storeTenderTypes' => $option_array]);
    }
}
