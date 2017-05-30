<?php
namespace Ls\Omni\Client;

use Ls\Omni\Service\ServiceType;
use Ls\Omni\Service\Soap\Client as OmniClient;

abstract class AbstractOperation implements IOperation
{
    /** @var  ServiceType */
    protected $service_type;

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
     * @return OmniClient
     */
    abstract function getClient ();

    protected function makeRequest ( $operation_name ) {
        $request_input = $this->getOperationInput();
        $client = $this->getClient();
        $response = $client->{$operation_name}( $request_input );

        return $response;
    }
}
