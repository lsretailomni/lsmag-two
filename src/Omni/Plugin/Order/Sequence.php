<?php

namespace Ls\Omni\Plugin\Order;

use \Ls\Core\Model\LSR;

/**
 * Class Sequence
 * @package Ls\Omni\Plugin\Order
 */
class Sequence
{

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * Sequence constructor.
     * @param LSR $lsr
     */
    public function __construct(
        LSR $lsr
    ) {
        $this->lsr = $lsr;
    }

    /**
     * @param \Magento\SalesSequence\Model\Sequence $subject
     * @param callable $proceed
     * @return string
     */
    public function aroundGetCurrentValue(
        \Magento\SalesSequence\Model\Sequence $subject,
        callable $proceed
    ) {
        $prefix      = $this->lsr->getStoreConfig(LSR::LS_ORDER_NUMBER_PREFIX_PATH);
        $returnValue = $proceed();
        return $prefix . $returnValue;
    }
}
