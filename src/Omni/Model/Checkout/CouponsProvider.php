<?php

namespace Ls\Omni\Model\Checkout;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;

class CouponsProvider implements ConfigProviderInterface
{
    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    public $storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public $timeZoneInterface;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var \Magento\Customer\Model\Session\Proxy
     */
    public $customerSession;

    /**
     * @var \Magento\Checkout\Model\Session\Proxy
     */
    public $checkoutSession;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    public function __construct(
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        StoreManagerInterface $storeManager,
        TimezoneInterface $timeZoneInterface,
        ScopeConfigInterface $scopeConfig,
        LoyaltyHelper $loyaltyHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->loyaltyHelper = $loyaltyHelper;
        $this->storeManager = $storeManager;
        $this->timeZoneInterface = $timeZoneInterface;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    public function getConfig()
    {
        $additionalVariables['coupons'] = $this->getAvailableCoupons();
        return $additionalVariables;
    }

    /**
     * @return array
     */
    public function getAvailableCoupons()
    {
        $coupons = [];
        if ($this->isCustomerLoggedIn()) {
            $coupons = $this->loyaltyHelper->getAvailableCouponsForLoggedInCustomers();
            foreach ($coupons as &$coupon) {
                $each = $coupon;
                $coupon = [];
                $coupon["discount_code"] = $each->getOfferId();
                $coupon["description"] = $this->getFormattedDescription($each);
            }
        }

        return $coupons;
    }

    /**
     * @return bool
     */
    public function isCustomerLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * @param \Ls\Omni\Client\Ecommerce\Entity\PublishedOffer $coupon
     * @return string
     */
    public function getFormattedDescription(\Ls\Omni\Client\Ecommerce\Entity\PublishedOffer $coupon)
    {
        $description = (($coupon->getOfferId()) ? "<span class='coupon-code'>".$coupon->getOfferId()."</span><br/>" : "").
            (($coupon->getDescription()) ? "<span class='coupon-description'>".$coupon->getDescription()."</span><br/>" : "").
            (($coupon->getDetails()) ? "<span class='coupon-detail'>".$coupon->getDetails()."</span><br/>" : "").
            (($this->getFormattedOfferExpiryDate($coupon->getExpirationDate())) ? "<span class='coupon-expiry'>".__("Valid till")."&nbsp". $this->getFormattedOfferExpiryDate($coupon->getExpirationDate())."</span>" : "");
        return $description;
    }

    /**
     * @param $date
     * @return string
     */
    public function getFormattedOfferExpiryDate($date)
    {
        $offerExpiryDate = "";
        try {
            $offerExpiryDate = $this->timeZoneInterface->date($date)->format($this->scopeConfig->getValue(
                LSR::SC_LOYALTY_EXPIRY_DATE_FORMAT,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            ));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $offerExpiryDate;
    }
}
