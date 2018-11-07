<?php

namespace Ls\Omni\Helper;

use Magento\Framework\App\Helper\Context;
use Ls\Omni\Client\Ecommerce\Entity;
use Ls\Omni\Client\Ecommerce\Operation;

class StockHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * StockHelper constructor
     * @param Context $context
     */
    public function __construct(
        Context $context
    )
    {
        parent::__construct($context);

    }
    /**
     * @param $storeId
     * @param $itemId
     * @return \Ls\Omni\Client\Ecommerce\Entity\ArrayOfInventoryResponse|\Ls\Omni\Client\Ecommerce\Entity\ItemsInStockGetResponse|\Ls\Omni\Client\ResponseInterface
     */
    public function getItemStockInStore($storeId, $itemId)
    {
        $response = NULL;
        $request = new Operation\ItemsInStockGet();
        $itemStock = new Entity\ItemsInStockGet();
        $itemStock->setItemId($itemId)->setStoreId($storeId);
        try {
            $response = $request->execute($itemStock);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getItemsInStockGetResult() : $response;
    }

}