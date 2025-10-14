<?php
declare(strict_types=1);

namespace Ls\OmniGraphQl\Model\Resolver\LoyaltyPoints;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Omni\Model\LoyaltyPoints\LoyaltyPointsManagement;

/**
 * Class AbstractLoyaltyPoints for loyalty points
 */
abstract class AbstractLoyaltyPoints implements ResolverInterface
{
    /**
     * @param LoyaltyHelper $helper
     * @param LoyaltyPointsManagement $loyaltyPointsManagement
     * @param GetCartForUser $getCartForUser
     */
    public function __construct(
        public LoyaltyHelper $helper,
        public LoyaltyPointsManagement $loyaltyPointsManagement,
        public GetCartForUser $getCartForUser
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {

        if (!$this->helper->isLoyaltyPointsEnabled('cart')) {
            throw new GraphQlInputException(__('The module is not enabled'));
        }

        if ($context->getUserId() == 0) {
            throw new GraphQlInputException(__('Only logged in user can use loyalty points'));
        }

        if (empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cartId" is missing'));
        }

        return $this->handleArgs($args, $context);
    }

    /**
     * For handle operation for loyalty points
     *
     * @param array $args
     * @param $context
     * @return mixed
     * @throws GraphQlInputException
     */
    abstract protected function handleArgs(array $args, $context);
}
