<?php
namespace Ls\Omni\Client;

use Ls\Omni\Service\Soap\Client;

interface IOperation
{
    /** @return IRequest */
    function & getOperationInput ();

    /**
     * @param IRequest $request
     *
     * @return IResponse
     */
    function execute ( IRequest $request = NULL );

    /**
     * @return Client
     */
    function getClient ();
}
