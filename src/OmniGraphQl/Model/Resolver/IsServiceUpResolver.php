<?php
namespace Ls\OmniGraphQl\Model\Resolver;

use \Ls\Core\Model\LSR;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * For returning Enable/Disable status of config path mappings
 * based on system configuration and Omni online/offline mode
 */
class IsServiceUpResolver implements ResolverInterface
{
    /**
     * @var LSR
     */
    public LSR $lsr;

    private const CONFIG_PATHS_MAPPING = [
        'ls_coupons_active'       => LSR::LS_ENABLE_COUPON_ELEMENTS,
        'ls_giftcard_active'      => LSR::LS_ENABLE_GIFTCARD_ELEMENTS,
        'loyalty_points_active'   => LSR::LS_ENABLE_LOYALTYPOINTS_ELEMENTS,
        'ls_mag_product_availability' => LSR::SC_CART_PRODUCT_AVAILABILITY,
        'ls_discounts_product_page' => LSR::LS_DISCOUNT_SHOW_ON_PRODUCT
    ];

    /**
     * @param LSR $lsr
     */
    public function __construct(
        LSR $lsr
    ) {
        $this->lsr = $lsr;
    }

    /**
     * Fetch store configuration value based on omni online/offline status.
     *
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
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId()) &&
            isset(self::CONFIG_PATHS_MAPPING[$field->getName()])
        ) {
            return (bool)$this->lsr->getStoreConfig(
                self::CONFIG_PATHS_MAPPING[$field->getName()],
                $this->lsr->getCurrentStoreId()
            );
        }
        return false;
    }
}
