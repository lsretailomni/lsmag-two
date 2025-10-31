<?php
declare(strict_types=1);

namespace Ls\Omni\Service\Soap;

use DOMDocument;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\CacheHelper;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Service\Metadata;
use \Ls\Omni\Service\ServiceType;
use Laminas\Soap\Client as LaminasSoapClient;
use Laminas\Uri\Uri;
use Magento\Framework\App\ObjectManager;

/**
 * SOAP client class to read XML
 */
class Client extends LaminasSoapClient
{
    /** @var Uri */
    public $url;

    /** @var ServiceType */
    public $type;

    public const SERVICE_TYPE = 'ecommerce';

    /**
     * @param Uri $uri
     */
    public function __construct(Uri $uri)
    {
        $this->url = $uri;
        $this->type = new ServiceType(self::SERVICE_TYPE);

        $this->execute();
    }

    /**
     * Get cache helper WSDL options
     *
     * @return void
     */
    public function execute()
    {
        $lsr = ObjectManager::getInstance()->get(LSR::class);
        $omniDataHelper = ObjectManager::getInstance()->get(Data::class);
        $cacheHelper = ObjectManager::getInstance()->get(CacheHelper::class);
        $centralType = $this->getCentralType();
        $soapOptions = $cacheHelper->getWsdlOptions();
        $token = $this->getToken();

        if ($centralType == '1') {
            $opts = ['http' => ['header' => "Authorization: Bearer " . $token, 'timeout' => $this->getTimeout()]];
            $this->url->setQuery([
                'company' => $this->getCompanyName()
            ]);
        } else {
            $opts = ['http' => ['header' => "Authorization: Basic " . $token, 'timeout' => $this->getTimeout()]];
            $this->url->setQuery([
                'company' => $omniDataHelper->extractCompanyNameFromWebServiceUri(
                    $lsr->getWebsiteConfig(LSR::SC_WEB_SERVICE_URI, $lsr->getWebsiteId())
                )
            ]);
        }
        // @codingStandardsIgnoreStart
        $context = stream_context_create($opts);
        $soapOptions['stream_context'] = $context;


        parent::__construct($this->url->toString(), $soapOptions);
    }

    /**
     * Get configured timeout
     *
     * @return float
     */
    public function getTimeout()
    {
        $lsr = ObjectManager::getInstance()->get(LSR::class);

        return floatval($lsr->getWebsiteConfig(LSR::SC_SERVICE_TIMEOUT, $lsr->getWebsiteId()));
    }

    /**
     * Get valid token
     *
     * @return string
     */
    public function getToken()
    {
        $lsr = ObjectManager::getInstance()->get(LSR::class);
        $dataHelper = ObjectManager::getInstance()->get(Data::class);
        $clientId = $lsr->getWebsiteConfig(LSR::SC_CLIENT_ID, $lsr->getWebsiteId());
        $clientSecret = $lsr->getWebsiteConfig(LSR::SC_CLIENT_SECRET, $lsr->getWebsiteId());
        $tenant = $lsr->getWebsiteConfig(LSR::SC_TENANT, $lsr->getWebsiteId());
        $username = $lsr->getWebsiteConfig(LSR::SC_USERNAME, $lsr->getWebsiteId());
        $password = $lsr->getWebsiteConfig(LSR::SC_PASSWORD, $lsr->getWebsiteId());
        $centralType = $this->getCentralType();
        $options = [
            'username' => $username,
            'password' => $password,
            'centralType' => $centralType,
            'tenant' => $tenant,
            'clientId' => $clientId,
            'clientSecret' => $clientSecret
        ];
        return $dataHelper->fetchValidToken($options);
    }

    /**
     * Get configured central type
     *
     * @return string
     */
    public function getCentralType()
    {
        $lsr = ObjectManager::getInstance()->get(LSR::class);
        return $lsr->getWebsiteConfig(LSR::SC_REPLICATION_CENTRAL_TYPE, $lsr->getWebsiteId());
    }

    /**
     * Get configured company name
     *
     * @return string
     */
    public function getCompanyName()
    {
        $lsr = ObjectManager::getInstance()->get(LSR::class);

        return $lsr->getWebsiteConfig(LSR::SC_COMPANY_NAME, $lsr->getWebsiteId());
    }

    /**
     * Get dom xml from wsdl
     *
     * @return DOMDocument
     */
    public function getWsdlXml()
    {
        $centralType = $this->getCentralType();

        if ($centralType == '1') {
            $opts = [
                'http' => [
                    'header' => [
                        "Authorization: Bearer " . $this->getToken(),
                        'timeout' => $this->getTimeout()
                    ]
                ]
            ];
        } else {
            $opts = ['http' => ['header' => "Authorization: Basic " . $this->getToken(), 'timeout' => $this->getTimeout()]];
        }

        $context = stream_context_create($opts);

        $response = file_get_contents($this->url->toString(), false, $context);
        $xml      = new DomDocument('1.0');
        $xml->loadXML($response);
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput       = true;

        return $xml;
    }

    /**
     * Get service type
     *
     * @return ServiceType
     */
    public function getServiceType()
    {
        return $this->type;
    }

    /**
     * Get meta data
     *
     * @param bool $withReplication
     *
     * @return Metadata
     */
    public function getMetadata($withReplication = false)
    {
        return new Metadata($this, $withReplication);
    }
}
