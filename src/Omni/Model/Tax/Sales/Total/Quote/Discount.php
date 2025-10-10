<?php
declare(strict_types=1);

namespace Ls\Omni\Model\Tax\Sales\Total\Quote;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\SalesRule\Api\Data\DiscountDataInterfaceFactory;
use Magento\SalesRule\Api\Data\RuleDiscountInterfaceFactory;
use Magento\SalesRule\Model\Validator;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Discount to apply different type of discounts
 */
class Discount extends \Magento\SalesRule\Model\Quote\Discount
{
    /**
     * @param ManagerInterface $eventManager
     * @param StoreManagerInterface $storeManager
     * @param Validator $validator
     * @param PriceCurrencyInterface $priceCurrency
     * @param BasketHelper $basketHelper
     * @param LoyaltyHelper $loyaltyHelper
     * @param RuleDiscountInterfaceFactory|null $discountInterfaceFactory
     * @param DiscountDataInterfaceFactory|null $discountDataInterfaceFactory
     */
    public function __construct(
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        Validator $validator,
        PriceCurrencyInterface $priceCurrency,
        public BasketHelper $basketHelper,
        public LoyaltyHelper $loyaltyHelper,
        RuleDiscountInterfaceFactory $discountInterfaceFactory = null,
        DiscountDataInterfaceFactory $discountDataInterfaceFactory = null
    ) {
        parent::__construct(
            $eventManager,
            $storeManager,
            $validator,
            $priceCurrency,
            $discountInterfaceFactory,
            $discountDataInterfaceFactory
        );
    }

    /**
     * Discount collect
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this|AbstractTotal
     * @throws Exception|GuzzleException
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        $lsr = $this->basketHelper->getLsrModel();

        if (!$lsr->isLSR(
            $lsr->getCurrentStoreId(),
            false,
            $lsr->getBasketIntegrationOnFrontend()
        )) {
            parent::collect($quote, $shippingAssignment, $total);
        }
        $total->setData('discount_description', ''); //For fixing explode issue on graph ql
        $items = $shippingAssignment->getItems();
        if (!count($items)) {
            return $this;
        }
        $paymentDiscount = $this->getGiftCardLoyaltyDiscount($quote);
        $total->addTotalAmount('grand_total', $paymentDiscount);
        $total->addBaseTotalAmount('grand_total', $paymentDiscount);

        if ($quote->getCouponCode()) {
            $total->setCouponCode($quote->getCouponCode());
        }
        return $this;
    }

    /**
     * Discount Fetch
     *
     * @param Quote $quote
     * @param Total $total
     * @return array|null
     * @throws Exception|GuzzleException
     */
    public function fetch(Quote $quote, Total $total)
    {
        $lsr = $this->basketHelper->getLsrModel();

        if (!$lsr->isLSR(
            $lsr->getCurrentStoreId(),
            false,
            $lsr->getBasketIntegrationOnFrontend()
        )) {
            return parent::fetch($quote, $total);
        }

        $result = null;
        $amount = $this->getTotalDiscount();
        $title = __('Discount');
        if ($amount < 0) {
            $result = [
                'code' => $this->getCode(),
                'title' => $title,
                'value' => $amount
            ];

            $paymentDiscount = $this->getGiftCardLoyaltyDiscount($quote);
            $total->addTotalAmount('discount', $amount);
            $total->addTotalAmount('grand_total', $paymentDiscount);
        } else {
            $total->addTotalAmount('discount', $amount);
            $total->addTotalAmount('grand_total', 0);
            $quote->getBillingAddress()->setDiscountAmount(0)->save();
        }

        return $result;
    }

    /**
     * Get total discount from central basket response
     *
     * @return float|int
     * @throws NoSuchEntityException
     */
    public function getTotalDiscount()
    {
        $amount = 0;
        $basketData = $this->basketHelper->getBasketSessionValue();

        if (!empty($basketData) && !empty($basketData->getMobiletransaction())) {
            $amount = -current((array)$basketData->getMobiletransaction())->getLineDiscount();
        }

        return $amount;
    }

    /**
     * Get gift card & loyalty points discount
     *
     * @param Quote $quote
     * @return float|int
     * @throws NoSuchEntityException|GuzzleException
     */
    public function getGiftCardLoyaltyDiscount(Quote $quote)
    {
        $pointDiscount = 0;
        if ($quote->getLsPointsSpent() > 0) {
            $pointDiscount = $this->loyaltyHelper->getLsPointsDiscount($quote->getLsPointsSpent());
            if ($pointDiscount > 0.001) {
                $quote->setLsPointsDiscount($pointDiscount);
            }
        }
        $giftCardAmount = $quote->getLsGiftCardAmountUsed();

        return -$pointDiscount - $giftCardAmount;
    }
}
