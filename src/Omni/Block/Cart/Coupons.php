<?php

namespace Ls\Omni\Block\Cart;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\PublishedOffer;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Checkout\Block\Cart\Coupon;
use Magento\Checkout\Model\Session\Proxy as CheckoutProxy;
use Magento\Customer\Model\Session\Proxy as CustomerProxy;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template\Context;

/**
 * @api
 */
class Coupons extends Coupon
{

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @var Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public $timeZoneInterface;

    /**
     * @var Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * Coupons constructor.
     * @param Context $context
     * @param CustomerProxy $customerSession
     * @param CheckoutProxy $checkoutSession
     * @param TimezoneInterface $timeZoneInterface
     * @param ScopeConfigInterface $scopeConfig
     * @param LSR $lsr
     * @param LoyaltyHelper $loyaltyHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        CustomerProxy $customerSession,
        CheckoutProxy $checkoutSession,
        TimezoneInterface $timeZoneInterface,
        ScopeConfigInterface $scopeConfig,
        LSR $lsr,
        LoyaltyHelper $loyaltyHelper,
        array $data = []
    ) {
        $this->_isScopePrivate   = true;
        $this->loyaltyHelper     = $loyaltyHelper;
        $this->timeZoneInterface = $timeZoneInterface;
        $this->scopeConfig       = $scopeConfig;
        $this->lsr               = $lsr;
        parent::__construct($context, $customerSession, $checkoutSession, $data);
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
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getAvailableCoupons()
    {
        return $this->loyaltyHelper->getAvailableCouponsForLoggedInCustomers();
    }

    /**
     * @param PublishedOffer $coupon
     * @return string
     */
    public function getFormattedDescription(PublishedOffer $coupon)
    {
        return "<div class='coupon-description-wrapper'>" .
            (($coupon->getOfferId()) ? "<span class='coupon-code'>" . $coupon->getOfferId() . "</span><br/>" : "") .
            (($coupon->getDescription()) ? "<span class='coupon-description'>" . $coupon->getDescription() . "</span><br/>" : "") .
            (($coupon->getDetails()) ? "<span class='coupon-detail'>" . $coupon->getDetails() . "</span><br/>" : "") .
            (($this->getFormattedOfferExpiryDate($coupon->getExpirationDate())) ? "<span class='coupon-expiry'>" . __("Valid till") . "&nbsp" . $this->getFormattedOfferExpiryDate($coupon->getExpirationDate()) . "</span>" : "") .
            "</div>";
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
     * @throws NoSuchEntityException
     */
    public function isCouponEnable()
    {
        return $this->lsr->getStoreConfig(
            LSR::LS_COUPON_RECOMMENDATIONS_SHOW_ON_CART_CHECKOUT,
            $this->lsr->getCurrentStoreId()
        );
    }
}
