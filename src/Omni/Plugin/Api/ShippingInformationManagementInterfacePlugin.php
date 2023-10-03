<?php

namespace Ls\Omni\Plugin\Api;

use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interceptor to intercept ShippingInformationManagementInterface methods
 */
class ShippingInformationManagementInterfacePlugin
{
    /**
     * @var BasketHelper
     */
    private $basketHelper;

    /**
     * @param BasketHelper $basketHelper
     */
    public function __construct(
        BasketHelper $basketHelper
    ) {
        $this->basketHelper = $basketHelper;
    }

    /**
     * After plugin to sync basket with Central for both logged in/guest user
     *
     * @param $subject
     * @param $result
     * @param $cartId
     * @param ShippingInformationInterface $addressInformation
     * @return mixed
     * @throws InvalidEnumException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterSaveAddressInformation(
        $subject,
        $result,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        if (!is_numeric($cartId)) {
            return $result;
        }

        $this->basketHelper->syncBasketWithCentral($cartId);

        return $result;
    }
}
