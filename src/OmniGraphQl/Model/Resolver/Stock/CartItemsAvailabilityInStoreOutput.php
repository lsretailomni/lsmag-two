<?php

namespace Ls\OmniGraphQl\Model\Resolver\Stock;

use \Ls\OmniGraphQl\Helper\DataHelper;
use \Ls\Omni\Helper\Data;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Resolver for finding item availability in all the stores
 */
class CartItemsAvailabilityInStoreOutput implements ResolverInterface
{
    /**
     * @var DataHelper
     */
    public $dataHelper;
    /**
     * @var Data
     */
    private Data $helper;

    /**
     * @param DataHelper $dataHelper
     * @param Data $helper
     */
    public function __construct(
        DataHelper $dataHelper,
        Data $helper
    ) {
        $this->dataHelper = $dataHelper;
        $this->helper     = $helper;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($args['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }

        if (empty($args['store_id'])) {
            throw new GraphQlInputException(__('Required parameter "store_id" is missing'));
        }

        $maskedCartId    = $args['cart_id'];
        $storeId         = $args['store_id'];
        $scopeId         = (int)$context->getExtensionAttributes()->getStore()->getId();
        $userId          = $context->getUserId();
        $stockCollection = $this->helper->fetchCartAndReturnStock($maskedCartId, $userId, $scopeId, $storeId);

        if (!$stockCollection) {
            throw new LocalizedException(__('Oops! Unable to do stock lookup currently.'));
        }

        return [
            'stock' => $stockCollection,
        ];
    }
}
