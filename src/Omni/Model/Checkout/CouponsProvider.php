<?php

namespace Ls\Omni\Model\Checkout;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\PublishedOffer;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class CouponsProvider implements ConfigProviderInterface
{
    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /** @var StoreManagerInterface */
    public $storeManager;

    /**
     * @var TimezoneInterface
     */
    public $timeZoneInterface;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var \Magento\Customer\Model\Session\Proxy
     */
    public $customerSession;

    /**
     * @var Proxy
     */
    public $checkoutSession;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * CouponsProvider constructor.
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param TimezoneInterface $timeZoneInterface
     * @param ScopeConfigInterface $scopeConfig
     * @param LoyaltyHelper $loyaltyHelper
     * @param LoggerInterface $logger
     * @param LSR $lsr
     */
    public function __construct(
        \Magento\Customer\Model\Session\Proxy $customerSession,
        Proxy $checkoutSession,
        StoreManagerInterface $storeManager,
        TimezoneInterface $timeZoneInterface,
        ScopeConfigInterface $scopeConfig,
        LoyaltyHelper $loyaltyHelper,
        LoggerInterface $logger,
        LSR $lsr
    ) {
        $this->customerSession   = $customerSession;
        $this->checkoutSession   = $checkoutSession;
        $this->loyaltyHelper     = $loyaltyHelper;
        $this->storeManager      = $storeManager;
        $this->timeZoneInterface = $timeZoneInterface;
        $this->scopeConfig       = $scopeConfig;
        $this->logger            = $logger;
        $this->lsr = $lsr;
    }

    public function getConfig()
    {
        $additionalVariables['coupons'] = $this->getAvailableCoupons();
        return $additionalVariables;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAvailableCoupons()
    {
        $coupons = [];
        if ($this->isCustomerLoggedIn()) {
            $coupons = $this->loyaltyHelper->getAvailableCouponsForLoggedInCustomers();
            foreach ($coupons as &$coupon) {
                $each                    = $coupon;
                $coupon                  = [];
                $coupon["discount_code"] = $each->getOfferId();
                $coupon["description"]   = $this->getFormattedDescription($each);
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
     * @param PublishedOffer $coupon
     * @return string
     */
    public function getFormattedDescription(PublishedOffer $coupon)
    {
        $description = (($coupon->getOfferId()) ? "<span class='coupon-code'>" . $coupon->getOfferId() . "</span><br/>" : "") .
            (($coupon->getDescription()) ? "<span class='coupon-description'>" . $coupon->getDescription() . "</span><br/>" : "") .
            (($coupon->getDetails()) ? "<span class='coupon-detail'>" . $coupon->getDetails() . "</span><br/>" : "") .
            (($this->getFormattedOfferExpiryDate($coupon->getExpirationDate())) ? "<span class='coupon-expiry'>" . __("Valid till") . "&nbsp" . $this->getFormattedOfferExpiryDate($coupon->getExpirationDate()) . "</span>" : "");
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
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $this->lsr->getCurrentStoreId()
            ));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $offerExpiryDate;
    }
}
