<?php

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
    /** @var QuoteRepository */
    public $quoteRepository;

    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * @param QuoteRepository $quoteRepository
     * @param BasketHelper $basketHelper
     */
    public function __construct(
        QuoteRepository $quoteRepository,
        BasketHelper $basketHelper
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->basketHelper    = $basketHelper;
    }

    /**
     * Before plugin to persist values in quote
     *
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param mixed $cartId
     * @param ShippingInformationInterface $addressInformation
     * @throws NoSuchEntityException
     */
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        $extAttributes = $addressInformation->getExtensionAttributes();
//        $pickupDate = $extAttributes->getPickupDate();
        $pickupStore = $extAttributes->getPickupStore();
        $quote       = $this->quoteRepository->getActive($cartId);
//        $quote->setPickupDate($pickupDate);
        $quote->setPickupStore($pickupStore);
        $pickupDate         = $extAttributes->getPickupDate();
        $pickupTimeslot     = $extAttributes->getPickupTimeslot();
        $pickupDateTimeslot = $this->basketHelper->getPickupTimeSlot($pickupDate, $pickupTimeslot);

        $quote->setPickupDateTimeslot($pickupDateTimeslot);
    }
}
