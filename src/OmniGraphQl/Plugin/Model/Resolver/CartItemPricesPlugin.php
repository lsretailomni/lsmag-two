<?php

namespace Ls\OmniGraphQl\Plugin\Model\Resolver;

use \Ls\OmniGraphQl\Helper\DataHelper;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\Quote\Item;

/**
 * Correcting the item price in the minicart/cart page
 */
class CartItemPricesPlugin
{

    /**
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * @param DataHelper $dataHelper
     */
    public function __construct(
        DataHelper $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    /**
     * After plugin update the price of the item in the minicart/cart page
     * @param $subject
     * @param $result
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @return mixed
     */
    public function afterResolve(
        $subject,
        $result,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null
    ) {
        if (!isset($value['model'])) {
            return $result;
        }
        /** @var Item $cartItem */
        $cartItem = $value['model'];
        if (isset($result['price']) && isset($result['price']['value'])) {
            $result['price']['value'] = $cartItem->getCustomPrice() ?
                $cartItem->getCustomPrice() : $cartItem->getPrice();
        }
        $result['discounts'][] = [
            'label'  => "",
            'amount' => [
                'value' => 0
            ]
        ];
        return $result;
    }
}
