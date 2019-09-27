<?php

namespace Ls\Omni\Block\Cart;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @api
 */
class Coupons extends \Magento\Checkout\Block\Cart\Coupon
{

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    public $storeManager;

    /**
     * @var Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public $timeZoneInterface;

    /**
     * @var Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var \Ls\Core\Model\LSR
     */
    public $lsr;

    /**
     * Coupons constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param TimezoneInterface $timeZoneInterface
     * @param ScopeConfigInterface $scopeConfig
     * @param LSR $lsr
     * @param LoyaltyHelper $loyaltyHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        StoreManagerInterface $storeManager,
        TimezoneInterface $timeZoneInterface,
        ScopeConfigInterface $scopeConfig,
        LSR $lsr,
        LoyaltyHelper $loyaltyHelper,
        array $data = []
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $data);
        $this->_isScopePrivate = true;
        $this->loyaltyHelper = $loyaltyHelper;
        $this->storeManager = $storeManager;
        $this->timeZoneInterface = $timeZoneInterface;
        $this->scopeConfig = $scopeConfig;
        $this->lsr = $lsr;
    }

    /**
     * @return bool
     */
    public function isCustomerLoggedIn()
    {
        return $this->_customerSession->isLoggedIn();
    }

    /**
     * @return array
     */
    public function getAvailableCoupons()
    {
        $coupons = $this->loyaltyHelper->getAvailableCouponsForLoggedInCustomers();
        return $coupons;
    }

    /**
     * @param \Ls\Omni\Client\Ecommerce\Entity\PublishedOffer $coupon
     * @return string
     */
    public function getFormattedDescription(\Ls\Omni\Client\Ecommerce\Entity\PublishedOffer $coupon)
    {
        $description = "<div class='coupon-description-wrapper'>" .
            (($coupon->getOfferId()) ? "<span class='coupon-code'>" . $coupon->getOfferId() . "</span><br/>" : "") .
            (($coupon->getDescription()) ? "<span class='coupon-description'>" . $coupon->getDescription() . "</span><br/>" : "") .
            (($coupon->getDetails()) ? "<span class='coupon-detail'>" . $coupon->getDetails() . "</span><br/>" : "") .
            (($this->getFormattedOfferExpiryDate($coupon->getExpirationDate())) ? "<span class='coupon-expiry'>" . __("Valid till") . "&nbsp" . $this->getFormattedOfferExpiryDate($coupon->getExpirationDate()) . "</span>" : "") .
            "</div>";
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
            $this->_logger->error($e->getMessage());
        }
        return $offerExpiryDate;
    }

    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('omni/ajax/coupons');
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isCouponEnable()
    {
        return $this->lsr->getStoreConfig(
            LSR::LS_COUPON_SHOW_ON_CART_CHECKOUT,
            $this->lsr->getCurrentStoreId()
        );
    }
}
