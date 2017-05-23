<?php
namespace Ls\Omni\Service;

use Zend\Uri\Uri;
use Zend\Uri\UriFactory;

class Service
{
    const DEFAULT_BASE_URL = 'http://lsretail.cloudapp.net/lsomniservice';
    static protected $endpoints = [
        ServiceType::ECOMMERCE => 'ecommerceservice.svc',
        ServiceType::LOYALTY => 'loyservice.svc',
        ServiceType::GENERAL => 'service.svc',
    ];

    /**
     * @param ServiceType $type
     * @param string      $base_url
     * @param bool        $wsdl
     *
     * @return Uri
     */
    public static function getUrl ( ServiceType $type,
                                    $base_url = self::DEFAULT_BASE_URL,
                                    $wsdl = TRUE ) {

        $url = join( '/', [ $base_url, static::$endpoints[ $type->getValue() ] ] );
        if ( $wsdl ) $url .= '?singlewsdl';

        return UriFactory::factory( $url );
    }
}
