<?php
namespace Ls\Omni\Client;

use Ls\Omni\Exception\TokenNotFoundException;
use Ls\Omni\Service\ServiceType;
use Ls\Omni\Service\Soap\Client as OmniClient;

abstract class AbstractOperation implements OperationInterface
{
    private static $header = 'LSRETAIL-TOKEN';
    /** @var  ServiceType */
    protected $service_type;
    /** @var string */
    protected $token = NULL;

    public function __construct ( ServiceType $service_type ) {
        $this->service_type = $service_type;
    }

    /**
     * @return ServiceType
     */
    public function getServiceType () {
        return $this->service_type;
    }

    /**
     * @return bool
     */
    protected function isTokenized () {

        return FALSE;
    }

    /**
     * @param string $token
     *
     * @return $this
     */
    public function setToken ( $token ) {
        $this->token = $token;

        return $this;
    }

    /**
     * @param $operation_name
     *
     * @return ResponseInterface
     * @throws TokenNotFoundException
     */
    protected function makeRequest ( $operation_name ) {
        $request_input = $this->getOperationInput();
        $client = $this->getClient();

        if ( $this->isTokenized() && is_null( $this->token ) ) throw new TokenNotFoundException();

        if ( !is_null( $this->token ) || $this->isTokenized() ) {
            $header = self::$header;
            $client->setStreamContext(
                stream_context_create( [ 'http' => [ 'header' => "$header: {$this->token}" ] ] ) );
        }

        $response = $client->{$operation_name}( $request_input );

        return $response;
    }

    /**
     * @return OmniClient
     */
    abstract function getClient ();
}
