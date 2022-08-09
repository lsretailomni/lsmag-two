<?php

namespace Ls\OmniGraphQl\Model\Resolver;

use \Ls\Core\Model\LSR;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * For returning Enable/Disable status of Coupon code
 * elements based on system configuration and Omni online/offline mode
 */
class EnableCouponElements implements ResolverInterface
{
    /**
     * @var LSR
     */
    private LSR $lsr;

    /**
     * @param LSR $lsr
     */
    public function __construct(
        LSR $lsr
    ) {
        $this->lsr        = $lsr;
    }

    /**
     * Show club information
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            return (bool)$this->lsr->getStoreConfig(
                LSR::LS_ENABLE_COUPON_ELEMENTS,
                $this->lsr->getCurrentStoreId()
            );
        }
        return false;
    }
}
