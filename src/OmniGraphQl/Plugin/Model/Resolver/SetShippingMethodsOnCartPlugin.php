<?php

namespace Ls\OmniGraphQl\Plugin\Model\Resolver;

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
 * Interceptor to interceptor ShippingMethodsOnCart methods
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
     * @param DataHelper $dataHelper
     * @param Clickandcollect $carrierModel
     */
    public function __construct(
        DataHelper $dataHelper,
        Clickandcollect $carrierModel
    ) {
        $this->dataHelper   = $dataHelper;
        $this->carrierModel = $carrierModel;
    }

    /**
     * Around plugin to validate cart items stock before setting pickup store for click and collect
     *
     * @param $subject
     * @param callable $proceed
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array[]
     * @throws GraphQlInputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws GraphQlAuthorizationException
     * @throws GraphQlNoSuchEntityException
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

        if ($validForClickAndCollect && isset($result['cart']) && isset($result['cart']['model'])) {
            $cart = $result['cart']['model'];
            $this->dataHelper->setPickUpStoreGivenCart($cart, $storeId);

            return [
                'cart' => [
                    'model' => $cart,
                ],
            ];
        }

        return $result;
    }
}
