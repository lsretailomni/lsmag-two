<?php
namespace Ls\Omni\Service\Soap;

use DOMDocument;
use Ls\Omni\Client\Ecommerce\ClassMap;
use Ls\Omni\Service\Metadata;
use Ls\Omni\Service\ServiceType;
use Zend\Http\ClientStatic;
use Zend\Soap\Client as ZendSoapClient;
use Zend\Uri\Uri;

class Client extends ZendSoapClient
{
//    use ClassMap;

    /** @var Uri */
    protected $URL;
    /** @var  ServiceType */
    protected $type;
    /** @var array */
    protected $soap_options = [ 'soap_version' => SOAP_1_1 ];

    /**
     * BaseClient constructor.
     *
     * @param Uri         $uri
     * @param ServiceType $type
     */
    public function __construct ( Uri $uri, ServiceType $type ) {

        parent::__construct( $uri->toString(), array_merge( $this->soap_options ) );

        $this->URL = $uri;
        $this->type = $type;
    }

    /**
     * @return DOMDocument
     */
    public function getWsdlXml () {

        $response = ClientStatic::get( $this->URL );

        $xml = new DomDocument( '1.0' );
        $xml->loadXML( $response->getBody() );
        $xml->preserveWhiteSpace = FALSE;
        $xml->formatOutput = TRUE;

        return $xml;
    }

    /**
     * @return ServiceType
     */
    public function getServiceType () {
        return $this->type;
    }


    /**
     * @return Metadata
     */
    public function getMetadata () {
        return new Metadata( $this );
    }
}
