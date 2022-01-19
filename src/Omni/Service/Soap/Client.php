<?php

namespace Ls\Omni\Service\Soap;

use DOMDocument;
use \Ls\Omni\Helper\CacheHelper;
use \Ls\Omni\Service\Metadata;
use \Ls\Omni\Service\ServiceType;
use Laminas\Http\ClientStatic;
use Laminas\Soap\Client as LaminasSoapClient;
use Laminas\Uri\Uri;
use Magento\Framework\App\ObjectManager;

/**
 * soap client class to read xml
 */
class Client extends LaminasSoapClient
{
    /** @var Uri */
    public $URL;

    /** @var  ServiceType */
    public $type;

    /**
     * Client constructor.
     * @param Uri $uri
     * @param ServiceType $type
     */
    public function __construct(Uri $uri, ServiceType $type)
    {
        $this->URL  = $uri;
        $this->type = $type;

        $this->execute();
    }
    /**
     * Get cache helper wsdl options
     * @return void
     */
    public function execute()
    {
        $cacheHelper = ObjectManager::getInstance()->get(CacheHelper::class);
        parent::__construct($this->URL->toString(), $cacheHelper->getWsdlOptions());
    }

    /**
     * @return DOMDocument
     */
    public function getWsdlXml()
    {

        $response = ClientStatic::get($this->URL);
        // @codingStandardsIgnoreLine
        $xml = new DomDocument('1.0');
        $xml->loadXML($response->getBody());
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput       = true;

        return $xml;
    }

    /**
     * @return ServiceType
     */
    public function getServiceType()
    {
        return $this->type;
    }

    /**
     * @param bool $with_replication
     *
     * @return Metadata
     */
    public function getMetadata($with_replication = false)
    {
        // @codingStandardsIgnoreLine
        return new Metadata($this, $with_replication);
    }
}
