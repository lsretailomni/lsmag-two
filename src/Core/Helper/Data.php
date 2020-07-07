<?php

namespace Ls\Core\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use SoapClient;

/**
 * Class Data
 * @package Ls\Core\Helper
 */
class Data extends AbstractHelper
{
    /** @var ObjectManagerInterface */
    private $object_manager;

    /** @var StoreManagerInterface */
    private $store_manager;

    /**
     * Data constructor.
     * @param Context $context
     * @param ObjectManagerInterface $object_manager
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $object_manager,
        StoreManagerInterface $storeManager
    ) {
        $this->object_manager = $object_manager;
        $this->store_manager  = $storeManager;
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function enabled()
    {
        $enabled = $this->scopeConfig->getValue(LSR::SC_SERVICE_ENABLE);
        return $enabled === '1' or $enabled === 1;
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
                    $this->store_manager->getStore()->getId()
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
                return true;
            }
        } catch (Exception $e) {
            $this->_logger->critical($e->getMessage());
        }
        return false;
    }
}
