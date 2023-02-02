<?php

namespace Ls\OmniGraphQl\Plugin\Model\Resolver;

use \Ls\OmniGraphQl\Helper\DataHelper;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\ItemHelper;
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
     * @var BasketHelper
     */
    private $basketHelper;

    /**
     * @var ItemHelper
     */
    private $itemHelper;

    /**
     * @param DataHelper $dataHelper
     * @param BasketHelper $basketHelper
     * @param ItemHelper $itemHelper
     */
    public function __construct(
        DataHelper $dataHelper,
        BasketHelper $basketHelper,
        ItemHelper $itemHelper
    ) {
        $this->dataHelper   = $dataHelper;
        $this->basketHelper = $basketHelper;
        $this->itemHelper   = $itemHelper;
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
        $basketData              = $this->basketHelper->getBasketSessionValue();
        $discountDescriptionData = $this->itemHelper->getOrderDiscountLinesForItem($cartItem, $basketData);
        foreach ($discountDescriptionData as $discountDescription) {
            $discountDescription = str_replace('<br />', '', $discountDescription);
            if ($discountDescription != __('Save')) {
                $result['discounts'][] = [
                    'label'  => $discountDescription,
                    'amount' => [
                        'value' => $cartItem->getDiscountAmount()
                    ]
                ];
            }
        }

        return $result;
    }
}
