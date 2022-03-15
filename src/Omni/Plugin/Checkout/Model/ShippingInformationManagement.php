<?php

namespace Ls\Omni\Plugin\Checkout\Model;

use \Ls\Core\Model\LSR;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteRepository;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Class ShippingInformationManagement
 * @package Ls\Omni\Plugin\Checkout\Model
 */
class ShippingInformationManagement
{
    /** @var QuoteRepository */
    public $quoteRepository;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var DateTime
     */
    public $dateTime;

    /**
     * @param QuoteRepository $quoteRepository
     * @param DateTime $dateTime
     * @param LSR $lsr
     */
    public function __construct(
        QuoteRepository $quoteRepository,
        DateTime $dateTime,
        LSR $lsr
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->dateTime = $dateTime;
        $this->lsr = $lsr;
    }

    /**
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param $cartId
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
        $pickupDateTimeslot = '';
        if (!empty($pickupDate) && !empty($pickupTimeslot)) {
            $pickupDateFormat   = $this->lsr->getStoreConfig(LSR::PICKUP_DATE_FORMAT);
            $pickupTimeFormat   = $this->lsr->getStoreConfig(LSR::PICKUP_TIME_FORMAT);
            $pickupDateTimeslot = $pickupDate . ' ' . $pickupTimeslot;
            $pickupDateTimeslot = $this->dateTime->date(
                $pickupDateFormat . ' ' . $pickupTimeFormat,
                strtotime($pickupDateTimeslot));
        }
        $quote->setPickupDateTimeslot($pickupDateTimeslot);
    }
}
