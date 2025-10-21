<?php

namespace Ls\OmniGraphQl\Plugin\Model\Resolver;

use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Model\Carrier\Clickandcollect;
use \Ls\OmniGraphQl\Helper\DataHelper;
use \Ls\Omni\Helper\Data;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\OfflineShipping\Model\Carrier\Flatrate;

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
     * @var Data
     */
    public Data $helper;

    /**
     * @var Flatrate
     */
    public $flatRateCarrier;

    /**
     * @param DataHelper $dataHelper
     * @param Clickandcollect $carrierModel
     * @param Flatrate $flatRateCarrier
     * @param BasketHelper $basketHelper
     * @param Data $helper
     */
    public function __construct(
        DataHelper $dataHelper,
        Clickandcollect $carrierModel,
        Flatrate $flatRateCarrier,
        BasketHelper $basketHelper,
        Data $helper
    ) {
        $this->dataHelper      = $dataHelper;
        $this->carrierModel    = $carrierModel;
        $this->basketHelper    = $basketHelper;
        $this->helper          = $helper;
        $this->flatRateCarrier = $flatRateCarrier;
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
        ?array $value = null,
        ?array $args = null
    ) {
        if (empty($args['input']['shipping_methods'])) {
            throw new GraphQlInputException(__('Required parameter "shipping_methods" is missing'));
        }

        $shippingMethods         = reset($args['input']['shipping_methods']);
        $validForClickAndCollect = false;
        $storeId                 = $selectedDate = $selectedDateTimeslot = '';

        if ($shippingMethods['carrier_code'] === $this->carrierModel->getCarrierCode() ||
            $shippingMethods['carrier_code'] === $this->flatRateCarrier->getCarrierCode()
        ) {

            if (empty($args['input']['cart_id'])) {
                throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
            }

            $maskedCartId = $args['input']['cart_id'];

            if (isset($args['input']['store_id']) && !empty($args['input']['store_id'])) {
                $storeId = $args['input']['store_id'];
            }

            if (isset($args['input']['selected_date']) && !empty($args['input']['selected_date'])) {
                $selectedDate = $args['input']['selected_date'];
            }

            if (isset($args['input']['selected_date_time_slot']) && !empty($args['input']['selected_date_time_slot'])) {
                $selectedDateTimeslot = $args['input']['selected_date_time_slot'];
            }

            $scopeId = (int)$context->getExtensionAttributes()->getStore()->getId();
            $userId  = $context->getUserId();

            if (!empty($storeId) && $shippingMethods['carrier_code'] === $this->carrierModel->getCarrierCode()
                && empty($this->dataHelper->getCheckoutSession()->getNoManageStock())) {
                $stockCollection = $this->helper->fetchCartAndReturnStock(
                    $maskedCartId,
                    $userId,
                    $scopeId,
                    $storeId
                );
                if (empty($this->dataHelper->getCheckoutSession()->getNoManageStock())) {
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
                }
                $validForClickAndCollect = true;
            }
        }

        $result = $proceed($field, $context, $info, $value, $args);
        if (!empty($this->dataHelper->getCheckoutSession()->getNoManageStock())) {
            $validForClickAndCollect = true;
        }
        if (isset($result['cart']) && isset($result['cart']['model'])) {
            $cart = $result['cart']['model'];
            $this->basketHelper->syncBasketWithCentral($cart->getId());

            if ($validForClickAndCollect ||
                $shippingMethods['carrier_code'] === $this->flatRateCarrier->getCarrierCode()
            ) {
                $this->dataHelper->setPickUpStoreGivenCart($cart, $storeId, $selectedDate, $selectedDateTimeslot);

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
