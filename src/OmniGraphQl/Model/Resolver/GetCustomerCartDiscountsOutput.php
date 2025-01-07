<?php

namespace Ls\OmniGraphQl\Model\Resolver;

use \Ls\Omni\Client\Ecommerce\Entity\Enum\DiscountType;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\OmniGraphQl\Helper\DataHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * To get discounts in cart and checkout view page in graphql
 */
class GetCustomerCartDiscountsOutput implements ResolverInterface
{
    /**
     * @var LoyaltyHelper
     */
    private LoyaltyHelper $loyaltyHelper;

    /**
     * @var DataHelper
     */
    private DataHelper $dataHelper;

    /**
     * @var CustomerSession
     */
    public CustomerSession $customerSession;

    /**
     * @param CustomerSession $customerSession
     * @param LoyaltyHelper $loyaltyHelper
     * @param DataHelper $dataHelper
     */
    public function __construct(
        CustomerSession $customerSession,
        LoyaltyHelper $loyaltyHelper,
        DataHelper $dataHelper
    ) {
        $this->customerSession = $customerSession;
        $this->loyaltyHelper   = $loyaltyHelper;
        $this->dataHelper      = $dataHelper;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {

        if (!($this->customerSession->isLoggedIn())) {
            throw new GraphQlInputException(__('Customer session not active. Please log in.'));
        }

        $couponsObj = $this->getAvailableCoupons();
        $couponsArr = [];

        if (!empty($couponsObj != '')) {
            foreach ($couponsObj as $coupon) {
                if ($coupon->getCode() == DiscountType::COUPON || $coupon->getCode() == DiscountType::PROMOTION) {
                    $couponsArr[] = $this->dataHelper->getFormattedDescriptionCoupon($coupon);
                }
            }
        }

        return [
            'coupons' => $couponsArr
        ];
    }

    /**
     * Get available coupons
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getAvailableCoupons()
    {
        return $this->loyaltyHelper->getAvailableCouponsForLoggedInCustomers();
    }
}
