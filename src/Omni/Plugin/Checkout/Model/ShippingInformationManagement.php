<?php

namespace Ls\Omni\Plugin\Checkout\Model;

class ShippingInformationManagement
{
    /** @var \Magento\Quote\Model\QuoteRepository  */
    protected $quoteRepository;

    /**
     * ShippingInformationManagement constructor.
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     */

    public function __construct(
        \Magento\Quote\Model\QuoteRepository $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {
        $extAttributes = $addressInformation->getExtensionAttributes();
        $pickupDate = $extAttributes->getPickupDate();
        $pickupStore = $extAttributes->getPickupStore();
        $quote = $this->quoteRepository->getActive($cartId);
        $quote->setPickupDate($pickupDate);
        $quote->setPickupStore($pickupStore);
    }
}