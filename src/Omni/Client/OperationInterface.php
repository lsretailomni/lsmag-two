<?php
namespace Ls\Omni\Client;

use Ls\Omni\Service\Soap\Client;

/**
 * Interface OperationInterface
 * @package Ls\Omni\Client
 */
interface OperationInterface
{
    /** @return RequestInterface */
    public function & getOperationInput();

    /**
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     */
    public function execute(RequestInterface $request = null);

    /**
     * @return Client
     */
    public function getClient();
}
