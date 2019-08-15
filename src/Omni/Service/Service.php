<?php

namespace Ls\Omni\Service;

use Zend\Uri\Uri;
use Zend\Uri\UriFactory;
use \Ls\Core\Model\LSR;

/**
 * Class Service
 * @package Ls\Omni\Service
 */
class Service
{

    const DEFAULT_BASE_URL = null;

    /** @var  LSR $_lsr */
    public $lsr;

    /** @var null|string  */
    public $baseurl = null;

    static public $endpoints = [
        ServiceType::ECOMMERCE => 'ecommerceservice.svc',
        ServiceType::LOYALTY => 'loyservice.svc',
        ServiceType::GENERAL => 'service.svc',
    ];

    /**
     * Service constructor.
     * @param LSR $Lsr
     */
    public function __construct()
    {
        $this->baseurl = $this->getOmniBaseUrl();
    }

    /**
     * @param ServiceType $type
     * @param string $base_url
     * @param bool $wsdl
     *
     * @return Uri
     */
    public static function getUrl(
        ServiceType $type,
        $base_url = self::DEFAULT_BASE_URL,
        $wsdl = true
    ) {
       if ($base_url==null) {
            // @codingStandardsIgnoreLine
           $base_url = (new self)->getOmniBaseUrl();
        }
        $url = join('/', [$base_url, static::$endpoints[$type->getValue()]]);
        if ($wsdl) {
            $url .= '?singlewsdl';
        }
        return UriFactory::factory($url);
    }

    /**
     * @return string
     * Use this in combination with \Ls\Core\Model\LSR::isLSR funciton
     */
    public function getOmniBaseUrl($magentoStoreId = '')
    {

        /** @var \Magento\Framework\App\ObjectManager  $objectManager */
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        /** @var \Ls\Core\Model\LSR $lsr */
        $lsr = $objectManager->create('Ls\Core\Model\LSR');

        if($magentoStoreId == ''){
            // get storeId from default loaded store.
            $magentoStoreId = $lsr->getCurrentStoreId();
        }
        // @codingStandardsIgnoreLine

        return $lsr->getStoreConfig(LSR::SC_SERVICE_BASE_URL,$magentoStoreId);
    }
}
