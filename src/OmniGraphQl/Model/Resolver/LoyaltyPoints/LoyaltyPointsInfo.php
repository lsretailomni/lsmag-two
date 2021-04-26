<?php

namespace Ls\OmniGraphQl\Model\Resolver\LoyaltyPoints;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use \Ls\Omni\Model\LoyaltyPoints\LoyaltyPointsManagement;

/**
 * loyalty points information return in cart
 */
class LoyaltyPointsInfo implements ResolverInterface
{
    /**
     * @var LoyaltyPointsManagement
     */
    private $loyaltyPointsManagement;

    /**
     * AppliedLoyaltyPoints constructor.
     * @param LoyaltyPointsManagement $loyaltyPointsManagement
     */
    public function __construct(
        LoyaltyPointsManagement $loyaltyPointsManagement
    ) {
        $this->loyaltyPointsManagement = $loyaltyPointsManagement;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        $cart = $value['model'];
        $cartId = $cart->getId();
        $loyaltyPoints = $this->loyaltyPointsManagement->get($cartId);
        return !empty($loyaltyPoints) ? $loyaltyPoints : null;
    }
}
