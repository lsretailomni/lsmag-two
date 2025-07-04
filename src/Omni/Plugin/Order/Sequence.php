<?php
declare(strict_types=1);

namespace Ls\Omni\Plugin\Order;

use \Ls\Core\Model\LSR;
use Magento\Framework\Exception\NoSuchEntityException;

class Sequence
{

    /**
     * @param LSR $lsr
     */
    public function __construct(
        public LSR $lsr
    ) {
    }

    /**
     * Fetch magento order number prefix based on current store id
     *
     * @param \Magento\SalesSequence\Model\Sequence $subject
     * @param callable $proceed
     * @return string
     * @throws NoSuchEntityException
     */
    public function aroundGetCurrentValue(
        \Magento\SalesSequence\Model\Sequence $subject,
        callable $proceed
    ) {
        $prefix      = $this->lsr->getStoreConfig(
            LSR::LS_ORDER_NUMBER_PREFIX_PATH,
            $this->lsr->getCurrentStoreId()
        );
        $returnValue = $proceed();
        if (!empty($prefix)) {
            return $prefix . $returnValue;
        }
        return $returnValue;
    }
}
