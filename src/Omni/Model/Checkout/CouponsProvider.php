<?php
declare(strict_types=1);

namespace Ls\Omni\Model\Checkout;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\CentralEcommerce\Entity\PublishedOffer;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class CouponsProvider implements ConfigProviderInterface
{
    /**
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param TimezoneInterface $timeZoneInterface
     * @param ScopeConfigInterface $scopeConfig
     * @param LoyaltyHelper $loyaltyHelper
     * @param LoggerInterface $logger
     * @param LSR $lsr
     */
    public function __construct(
        public CustomerSession $customerSession,
        public CheckoutSession $checkoutSession,
        public StoreManagerInterface $storeManager,
        public TimezoneInterface $timeZoneInterface,
        public ScopeConfigInterface $scopeConfig,
        public LoyaltyHelper $loyaltyHelper,
        public LoggerInterface $logger,
        public LSR $lsr
    ) {
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     * @throws GuzzleException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getConfig(): array
    {
        $additionalVariables['coupons'] = $this->getAvailableCoupons();

        return $additionalVariables;
    }

    /**
     * Get all available coupons applicable on cart items
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException|GuzzleException
     */
    public function getAvailableCoupons(): array
    {
        $response = [];

        if ($this->isCustomerLoggedIn()) {
            $coupons = $this->loyaltyHelper->getAvailableCouponsForLoggedInCustomers();

            foreach ($coupons as $coupon) {
                $response[] = [
                    'discount_code' =>  $coupon->getDiscountno(),
                    'description' =>  $this->getFormattedDescription($coupon)
                ];
            }
        }

        return $response;
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isCustomerLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * Get formatted description
     *
     * @param PublishedOffer $coupon
     * @return string
     */
    public function getFormattedDescription(PublishedOffer $coupon): string
    {
        $description = '';

        if ($coupon->getDiscountno()) {
            $description .= "<span class='coupon-code'>" . $coupon->getDiscountno() . "</span><br/>";
        }

        if ($coupon->getDescription()) {
            $description .= "<span class='coupon-description'>" . $coupon->getDescription() . "</span><br/>";
        }

        if ($coupon->getSecondarytext()) {
            $description .= "<span class='coupon-detail'>" . $coupon->getSecondarytext() . "</span><br/>";
        }

        $expiryDate = $this->getFormattedOfferExpiryDate($coupon->getEndingdate());
        if ($expiryDate) {
            $description .= "<span class='coupon-expiry'>" . __("Valid till") . "&nbsp;" . $expiryDate . "</span>";
        }

        return $description;
    }

    /**
     * Get formatted offer expiry date
     *
     * @param string $date
     * @return string
     */
    public function getFormattedOfferExpiryDate(string $date): string
    {
        $offerExpiryDate = "";
        try {
            $expiryDate = new \DateTimeImmutable($date);

            $offerExpiryDate = $this->timeZoneInterface->date($expiryDate)->format(
                $this->scopeConfig->getValue(
                    LSR::SC_LOYALTY_EXPIRY_DATE_FORMAT,
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    $this->lsr->getCurrentStoreId()
                )
            );
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $offerExpiryDate;
    }
}
