<?php

namespace Ls\Omni\Plugin\Checkout\Model;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\ItemHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;

/**
 * Class DefaultConfigProvider
 * @package Ls\Omni\Plugin\Checkout\Model
 */
class DefaultConfigProvider
{
    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    /**
     * @var $quoteRepository
     */
    public $quoteRepository;

    /**
     * @var itemHelper
     */
    public $itemHelper;

    /**
     * @var pricingHelper
     */
    public $pricingHelper;

    /**
     * DefaultConfigProvider constructor.
     * @param CheckoutSession $checkoutSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param ItemHelper $itemHelper
     * @param PricingHelper $pricingHelper
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        ItemHelper $itemHelper,
        PricingHelper $pricingHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->itemHelper = $itemHelper;
        $this->pricingHelper = $pricingHelper;
    }

    public function afterGetConfig(
        \Magento\Checkout\Model\DefaultConfigProvider $subject,
        array $result
    ) {
        $items = $result['totalsData']['items'];
        foreach ($items as $index => $item) {
            $quoteItem = $this->checkoutSession->getQuote()->getItemById($item['item_id']);
            $originalPrice = $quoteItem->getProduct()->getPrice() * $quoteItem->getQty();
            if ($quoteItem->getCustomPrice() > 0) {
                $result['quoteItemData'][$index]['discountprice'] =
                    $this->pricingHelper->currency($quoteItem->getCustomPrice(), true, false);
                $discountAmount = $quoteItem->getDiscountAmount();
                $result['quoteItemData'][$index]['discountamount'] =
                    ($discountAmount > 0 && $discountAmount != null) ?
                        $this->pricingHelper->currency(
                            $discountAmount,
                            true,
                            false
                        ) : '';
                $result['quoteItemData'][$index]['discountamounttext'] = LSR::LS_DISCOUNT_PRICE_PERCENTAGE_TEXT;
            } else {
                $result['quoteItemData'][$index]['discountprice'] = '';
            }
            $result['quoteItemData'][$index]['originalprice'] =
                $this->pricingHelper->currency($originalPrice, true, false);
        }
        return $result;
    }
}
