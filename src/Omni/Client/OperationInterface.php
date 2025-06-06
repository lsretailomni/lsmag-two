<?php
namespace Ls\Omni\Client;

use Magento\Catalog\Model\AbstractModel;

interface OperationInterface
{
    /**
     * Set operation input
     *
     * @param array $params
     * @return AbstractModel
     */
    public function & setOperationInput(array $params = []);

    /**
     * Method responsible to make request
     *
     * @return AbstractModel
     */
    public function execute();
}
