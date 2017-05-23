<?php
namespace Ls\Omni\Client;

use Ls\Omni\Service\ServiceType;

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

    abstract function getClient ();

    protected function makeRequest ( $operation_name ) {
        $request_input = $this->getOperationInput();
        $response = $this->client->{$operation_name}( $request_input );

        return $response;
    }
}
