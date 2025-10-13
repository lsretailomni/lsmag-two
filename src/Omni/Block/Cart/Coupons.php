<?php
declare(strict_types=1);

namespace Ls\Omni\Block\Cart;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\CentralEcommerce\Entity\PublishedOffer;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Checkout\Block\Cart\Coupon;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template\Context;

class Coupons extends Coupon
{
    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param TimezoneInterface $timeZoneInterface
     * @param ScopeConfigInterface $scopeConfig
     * @param LSR $lsr
     * @param LoyaltyHelper $loyaltyHelper
     * @param array $data
     */
    public function __construct(
        public Context $context,
        public CustomerSession $customerSession,
        public CheckoutSession $checkoutSession,
        public TimezoneInterface $timeZoneInterface,
        public ScopeConfigInterface $scopeConfig,
        public LSR $lsr,
        public LoyaltyHelper $loyaltyHelper,
        array $data = []
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $data);
    }

    /**
     * Check to see if customer is logged in
     *
     * @return bool
     */
    public function isCustomerLoggedIn()
    {
        return $this->_customerSession->isLoggedIn();
    }

    /**
     * Get available coupons applicable to items in the cart
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException|GuzzleException
     */
    public function getAvailableCoupons(): array
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('I am here getAvailableCoupons');
        return $this->loyaltyHelper->getAvailableCouponsForLoggedInCustomers();
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
            $this->_logger->error($e->getMessage());
        }

        return $offerExpiryDate;
    }

    /**
     * Get ajax url for coupons
     *
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->getUrl('omni/ajax/coupons');
    }

    /**
     * Is coupon enabled
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isCouponEnabled(): bool
    {
        return ($this->lsr->getStoreConfig(
            LSR::LS_ENABLE_COUPON_ELEMENTS,
            $this->lsr->getCurrentStoreId()
        ) && $this->lsr->getStoreConfig(
            LSR::LS_COUPON_RECOMMENDATIONS_SHOW_ON_CART_CHECKOUT,
            $this->lsr->getCurrentStoreId()
        ) && $this->lsr->getBasketIntegrationOnFrontend());
    }
}
