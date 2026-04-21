<?php

namespace Ls\Webhooks\Model;

use \Ls\Webhooks\Api\OrderReturnsInterface;
use \Ls\Webhooks\Model\Order\Returns;
use \Ls\Webhooks\Helper\Data;
use \Ls\Webhooks\Logger\Logger;

/**
 * Class for handling order returns
 */
class OrderReturns implements OrderReturnsInterface
{
    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var Returns
     */
    public $returns;

    /**
     * @var Data
     */
    public $helper;

    /**
     * OrderReturns constructor.
     * @param Logger $logger
     * @param Returns $returns
     * @param Data $helper
     */
    public function __construct(
        Logger $logger,
        Returns $returns,
        Data $helper
    ) {
        $this->logger  = $logger;
        $this->returns = $returns;
        $this->helper  = $helper;
    }

    /**
     * @inheritdoc
     */
    public function set($OrderId, $ReturnType, $Amount, $Lines)
    {
        try {
            $data = [
                'OrderId'    => $OrderId,
                'ReturnType' => $ReturnType,
                'Amount'     => $Amount,
                'Lines'      => $Lines
            ];

            $this->logger->info('OrderReturns = ', $data);

            if (!empty($data['OrderId'])) {
                return $this->returns->returns($data);
            }

            return $this->helper->outputMessage(false, 'Order Id is not valid.');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->helper->outputMessage(false, $e->getMessage());
        }
    }
}
