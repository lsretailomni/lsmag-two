<?php
declare(strict_types=1);

namespace Ls\Omni\Block\Cart;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Ls\Core\Model\LSR;
use Ls\Omni\Client\CentralEcommerce\Entity\PublishedOffer;
use Ls\Omni\Helper\LoyaltyHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Hyvä-specific block for cart coupon recommendations.
 *
 * Extends Template directly instead of Checkout\Block\Cart\Coupon so that
 * _prepareLayout() does not attempt to add a captcha child block, which
 * crashes in a standalone AJAX layout context.
 */
class HyvaCoupons extends Template
{
    public function __construct(
        Context $context,
        private readonly CustomerSession $customerSession,
        private readonly CheckoutSession $checkoutSession,
        private readonly TimezoneInterface $timeZoneInterface,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LSR $lsr,
        private readonly LoyaltyHelper $loyaltyHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function isCustomerLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws GuzzleException
     */
    public function getAvailableCoupons(): array
    {
        return $this->loyaltyHelper->getAvailableCouponsForLoggedInCustomers();
    }

    public function getFormattedDescription(PublishedOffer $coupon): string
    {
        $description = '';

        if ($coupon->getDiscountno()) {
            $description .= "<span class='coupon-code font-bold'>" . $coupon->getDiscountno() . "</span><br/>";
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

    public function getFormattedOfferExpiryDate(string $date): string
    {
        $offerExpiryDate = '';
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

    public function isCouponEnabled(): bool
    {
        return (bool) ($this->lsr->getStoreConfig(
            LSR::LS_ENABLE_COUPON_ELEMENTS,
            $this->lsr->getCurrentStoreId()
        ) && $this->lsr->getStoreConfig(
            LSR::LS_COUPON_RECOMMENDATIONS_SHOW_ON_CART_CHECKOUT,
            $this->lsr->getCurrentStoreId()
        ) && $this->lsr->getBasketIntegrationOnFrontend());
    }

    public function getCouponCode(): ?string
    {
        return $this->checkoutSession->getQuote()->getCouponCode();
    }
}
