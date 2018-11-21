<?php

namespace Ls\Omni\Helper;

use Magento\Framework\App\Helper\Context;
use Ls\Omni\Client\Ecommerce\Entity;
use Ls\Omni\Client\Ecommerce\Operation;

/**
 * Class StockHelper
 * @package Ls\Omni\Helper
 */
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
     * @param $parentProductId
     * @param $childProductId
     * @return \Ls\Omni\Client\Ecommerce\Entity\ArrayOfInventoryResponse|\Ls\Omni\Client\Ecommerce\Entity\ItemsInStockGetResponse|\Ls\Omni\Client\ResponseInterface
     */
    public function getItemStockInStore(
        $storeId,
        $parentProductId,
        $childProductId
    )
    {
        $response = NULL;
        $request = new Operation\ItemsInStockGet();
        $itemStock = new Entity\ItemsInStockGet();
        if (!empty($parentProductId) && !empty($childProductId)) {
            $itemStock->setItemId($parentProductId)->
            setVariantId($childProductId)->setStoreId($storeId);
        } else {
            $itemStock->setItemId($parentProductId)->setStoreId($storeId);
        }
        try {
            $response = $request->execute($itemStock);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getItemsInStockGetResult() : $response;
    }

}