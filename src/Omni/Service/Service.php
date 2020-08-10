<?php

namespace Ls\Omni\Service;

use \Ls\Core\Model\LSR;
use Magento\Framework\App\ObjectManager;
use Laminas\Uri\Uri;
use Laminas\Uri\UriFactory;

/**
 * Class Service
 * @package Ls\Omni\Service
 */
class Service
{
    const DEFAULT_BASE_URL = null;

    /** @var  LSR $_lsr */
    public $lsr;

    /** @var null|string */
    public $baseurl = null;

    public static $endpoints = [
        ServiceType::ECOMMERCE => 'UCService.svc'
    ];

    /**
     * Service constructor.
     * @param LSR $Lsr
     */
    public function __construct()
    {
        //Commented because we are not using it
        //$this->baseurl = $this->getOmniBaseUrl();
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
        if ($base_url == null) {
            // @codingStandardsIgnoreLine
            $base_url = (new self())->getOmniBaseUrl();
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
        $objectManager = ObjectManager::getInstance();
        // @codingStandardsIgnoreLine
        $lsr = $objectManager->create('Ls\Core\Model\LSR');
        if ($magentoStoreId == '') {
            // get storeId from default loaded store.
            $magentoStoreId = $lsr->getCurrentStoreId();
        }
        return $lsr->getStoreConfig(LSR::SC_SERVICE_BASE_URL, $magentoStoreId);
    }
}
