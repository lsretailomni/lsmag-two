<?php

namespace Ls\Omni\Plugin\Checkout\Model;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteRepository;

/**
 * Class ShippingInformationManagement
 * @package Ls\Omni\Plugin\Checkout\Model
 */
class ShippingInformationManagement
{
    /** @var QuoteRepository */
    public $quoteRepository;

    /**
     * ShippingInformationManagement constructor.
     * @param QuoteRepository $quoteRepository
     */

    public function __construct(
        QuoteRepository $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
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
    }
}
