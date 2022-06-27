<?php

namespace Ls\OmniGraphQl\Plugin\Model\Resolver;

use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Model\Carrier\Clickandcollect;
use \Ls\OmniGraphQl\Helper\DataHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Interceptor to intercept ShippingMethodsOnCart methods
 */
class SetShippingMethodsOnCartPlugin
{
    /**
     * @var DataHelper
     */
    public $dataHelper;

    /**
     * @var Clickandcollect
     */
    public $carrierModel;

    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * @param DataHelper $dataHelper
     * @param Clickandcollect $carrierModel
     * @param BasketHelper $basketHelper
     */
    public function __construct(
        DataHelper $dataHelper,
        Clickandcollect $carrierModel,
        BasketHelper $basketHelper
    ) {
        $this->dataHelper   = $dataHelper;
        $this->carrierModel = $carrierModel;
        $this->basketHelper = $basketHelper;
    }

    /**
     * Around plugin to validate cart items stock before setting pickup store for click and collect
     *
     * @param mixed $subject
     * @param callable $proceed
     * @param Field $field
     * @param mixed $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array[]
     * @throws GraphQlInputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws GraphQlAuthorizationException
     * @throws GraphQlNoSuchEntityException|InvalidEnumException
     */
    public function aroundResolve(
        $subject,
        callable $proceed,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (empty($args['input']['shipping_methods'])) {
            throw new GraphQlInputException(__('Required parameter "shipping_methods" is missing'));
        }

        $shippingMethods = reset($args['input']['shipping_methods']);
        $validForClickAndCollect = false;

        if ($shippingMethods['carrier_code'] === $this->carrierModel->getCarrierCode()) {
            if (empty($args['input']['store_id'])) {
                throw new GraphQlInputException(__('Required parameter "store_id" is missing'));
            }

            if (empty($args['input']['cart_id'])) {
                throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
            }

            $maskedCartId    = $args['input']['cart_id'];
            $storeId         = $args['input']['store_id'];
            $pickupDate = $pickupTimeslot = '';

            if (isset($args['input']['pickup_date']) && !empty($args['input']['pickup_date'])) {
                $pickupDate = $args['input']['pickup_date'];
            }

            if (isset($args['input']['pickup_time_slot']) && !empty($args['input']['pickup_time_slot'])) {
                $pickupTimeslot = $args['input']['pickup_time_slot'];
            }

            $scopeId         = (int)$context->getExtensionAttributes()->getStore()->getId();
            $userId          = $context->getUserId();
            $stockCollection = $this->dataHelper->fetchCartAndReturnStock($maskedCartId, $userId, $scopeId, $storeId);

            if (!$stockCollection) {
                throw new LocalizedException(__('Oops! Unable to do stock lookup currently.'));
            }

            foreach ($stockCollection as $stock) {
                if (!$stock['status']) {
                    throw new LocalizedException(
                        __('Unable to use selected shipping method since some or all of the cart items are not available in selected store.')
                    );
                }
            }
            $validForClickAndCollect = true;
        }
        $result = $proceed($field, $context, $info, $value, $args);
        if (isset($result['cart']) && isset($result['cart']['model'])) {
            $cart = $result['cart']['model'];
            $this->basketHelper->syncBasketWithCentral($cart->getId());
            if ($validForClickAndCollect) {
                $this->dataHelper->setPickUpStoreGivenCart($cart, $storeId, $pickupDate, $pickupTimeslot);

                return [
                    'cart' => [
                        'model' => $cart,
                    ],
                ];
            }
        }

        return $result;
    }
}
