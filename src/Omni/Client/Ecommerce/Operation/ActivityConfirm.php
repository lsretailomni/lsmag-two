<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Operation;

use Ls\Omni\Client\RequestInterface;
use Ls\Omni\Client\ResponseInterface;
use Ls\Omni\Client\AbstractOperation;
use Ls\Omni\Service\Service as OmniService;
use Ls\Omni\Service\ServiceType;
use Ls\Omni\Service\Soap\Client as OmniClient;
use Ls\Omni\Client\Ecommerce\ClassMap;
use Ls\Omni\Client\Ecommerce\Entity\ActivityConfirm as ActivityConfirmRequest;
use Ls\Omni\Client\Ecommerce\Entity\ActivityConfirmResponse as ActivityConfirmResponse;

class ActivityConfirm extends AbstractOperation
{

    const OPERATION_NAME = 'ACTIVITY_CONFIRM';

    const SERVICE_TYPE = 'ecommerce';

    /**
     * @property OmniClient $client
     */
    protected $client = null;

    /**
     * @property ActivityConfirmRequest $request
     */
    protected $request = null;

    /**
     * @property ActivityConfirmResponse $response
     */
    protected $response = null;

    /**
     * @property string $request_xml
     */
    protected $request_xml = null;

    /**
     * @property string $response_xml
     */
    protected $response_xml = null;

    /**
     * @property Exception $error
     */
    protected $error = null;

    public function __construct($baseUrl = '')
    {
        $service_type = new ServiceType( self::SERVICE_TYPE );
        parent::__construct( $service_type );
        $url = OmniService::getUrl( $service_type,$baseUrl );
        $this->client = new OmniClient( $url, $service_type );
        $this->client->setClassmap( $this->getClassMap() );
    }

    /**
     * @param ActivityConfirmRequest $request
     * @return ResponseInterface|ActivityConfirmResponse
     */
    public function execute(RequestInterface $request = null)
    {
        if ( !is_null( $request ) ) {
            $this->setRequest( $request );
        }
        return $this->makeRequest( 'ActivityConfirm' );
    }

    /**
     * @return ActivityConfirmRequest
     */
    public function & getOperationInput()
    {
        if ( is_null( $this->request ) ) {
            $this->request = new ActivityConfirmRequest();
        }
        return $this->request;
    }

    /**
     * @return array
     */
    public function getClassMap()
    {
        return ClassMap::getClassMap();
    }

    public function isTokenized()
    {
        return FALSE;
    }

    /**
     * @param OmniClient $client
     * @return $this
     */
    public function setClient($client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @return OmniClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param ActivityConfirmRequest $request
     * @return $this
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return ActivityConfirmRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param ActivityConfirmResponse $response
     * @return $this
     */
    public function setResponse($response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @return ActivityConfirmResponse
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string $request_xml
     * @return $this
     */
    public function setRequestXml($request_xml)
    {
        $this->request_xml = $request_xml;
        return $this;
    }

    /**
     * @return string
     */
    public function getRequestXml()
    {
        return $this->request_xml;
    }

    /**
     * @param string $response_xml
     * @return $this
     */
    public function setResponseXml($response_xml)
    {
        $this->response_xml = $response_xml;
        return $this;
    }

    /**
     * @return string
     */
    public function getResponseXml()
    {
        return $this->response_xml;
    }

    /**
     * @param Exception $error
     * @return $this
     */
    public function setError($error)
    {
        $this->error = $error;
        return $this;
    }

    /**
     * @return Exception
     */
    public function getError()
    {
        return $this->error;
    }


}

