<?php

namespace Ls\Omni\Plugin\Api;

use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;

/**
 * Interceptor to intercept ShipmentEstimationInterface methods
 */
class ShipmentEstimationInterfacePlugin
{
    /**
     * @var BasketHelper
     */
    private $basketHelper;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @param BasketHelper $basketHelper
     * @param CartRepositoryInterface $quoteRepository
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     */
    public function __construct(
        BasketHelper $basketHelper,
        CartRepositoryInterface $quoteRepository,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
    ) {
        $this->basketHelper           = $basketHelper;
        $this->quoteRepository        = $quoteRepository;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
    }

    /**
     * After plugin to sync basket with Central for guest user
     *
     * @param $subject
     * @param $result
     * @param $cartId
     * @param AddressInterface $address
     * @return mixed
     * @throws InvalidEnumException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterEstimateByExtendedAddress(
        $subject,
        $result,
        $cartId,
        AddressInterface $address
    ) {
        if (is_numeric($cartId) || !$address->getPostcode()) {
            return $result;
        }

        $cartId = $this->maskedQuoteIdToQuoteId->execute($cartId);

        $this->basketHelper->syncBasketWithCentral($cartId);

        return $result;
    }

    /**
     * After plugin to sync basket with Central for logged in user
     *
     * @param $subject
     * @param $result
     * @param $cartId
     * @param $addressId
     * @return mixed
     * @throws InvalidEnumException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterEstimateByAddressId(
        $subject,
        $result,
        $cartId,
        $addressId
    ) {
        $this->basketHelper->syncBasketWithCentral($cartId);

        return $result;
    }
}
