<?php
namespace Ls\Omni\Client;

use Ls\Omni\Service\Soap\Client;

interface OperationInterface
{
    /** @return RequestInterface */
    function & getOperationInput ();

    /**
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     */
    function execute ( RequestInterface $request = NULL );

    /**
     * @return Client
     */
    function getClient ();
}
