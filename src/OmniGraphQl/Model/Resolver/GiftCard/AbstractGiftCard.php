<?php

namespace Ls\OmniGraphQl\Model\Resolver\GiftCard;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use \Ls\Omni\Helper\GiftCardHelper;
use \Ls\Omni\Model\GiftCard\GiftCardManagement;

/**
 * Class AbstractGiftCard for gift card
 */
abstract class AbstractGiftCard implements ResolverInterface
{

    /**
     * @var GiftCardHelper
     */
    protected $helper;

    /**
     * @var GiftCardManagement
     */
    protected $giftCardManagement;

    /**
     * @var GetCartForUser
     */
    protected $getCartForUser;

    /**
     * AbstractGiftCard constructor.
     * @param GiftCardHelper $helper
     * @param GiftCardManagement $giftCardManagement
     * @param GetCartForUser $getCartForUser
     */
    public function __construct(
        GiftCardHelper $helper,
        GiftCardManagement $giftCardManagement,
        GetCartForUser $getCartForUser
    ) {
        $this->helper = $helper;
        $this->giftCardManagement = $giftCardManagement;
        $this->getCartForUser = $getCartForUser;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!$this->helper->isGiftCardEnabled('cart')) {
            throw new GraphQlInputException(__('The module is not enabled'));
        }

        if (!isset($args['input']['cart_id']) || empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cartId" is missing'));
        }

        return $this->handleArgs($args, $context);
    }

    /**
     * For handle operation for gift card
     * @param array $args
     * @param $context
     * @return mixed
     */
    abstract protected function handleArgs(array $args, $context);
}
