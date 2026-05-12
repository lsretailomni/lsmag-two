<?php
declare(strict_types=1);

namespace Ls\Omni\Plugin\Checkout\Model;

use \Ls\Omni\Helper\BasketHelper;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteRepository;

/**
 * Interceptor to intercept methods from ShippingInformationManagement
 */
class ShippingInformationManagement
{
    /**
     * @param QuoteRepository $quoteRepository
     * @param BasketHelper $basketHelper
     */
    public function __construct(
        public QuoteRepository $quoteRepository,
        public BasketHelper $basketHelper
    ) {
    }

    /**
     * Before plugin to persist values in quote
     *
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param int $cartId
     * @param ShippingInformationInterface $addressInformation
     * @throws NoSuchEntityException
     */
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        $extAttributes      = $addressInformation->getExtensionAttributes();
        $pickupStore        = $extAttributes->getPickupStore();
        $quote              = $this->quoteRepository->getActive($cartId);
        $pickupDate         = $extAttributes->getPickupDate();
        $pickupTimeslot     = $extAttributes->getPickupTimeslot();
        $subscriptionId     = $extAttributes->getSubscriptionId();
        $pickupDateTimeslot = $this->basketHelper->getPickupTimeSlot($pickupDate, $pickupTimeslot);

        $quote
            ->setPickupStore($pickupStore)
            ->setPickupDateTimeslot($pickupDateTimeslot)
            ->setLsSubscriptionId($subscriptionId);
    }
}
