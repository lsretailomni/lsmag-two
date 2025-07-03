<?php
declare(strict_types=1);

namespace Ls\Omni\Plugin\Checkout\Model;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Quote\Api\CartRepositoryInterface;

class DefaultConfigProvider
{
    /**
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $quoteRepository
     * @param BasketHelper $basketHelper
     * @param PricingHelper $pricingHelper
     */
    public function __construct(
        public CheckoutSession $checkoutSession,
        public CartRepositoryInterface $quoteRepository,
        public BasketHelper $basketHelper,
        public PricingHelper $pricingHelper
    ) {
    }

    /**
     * After plugin to set amounts on cart page
     *
     * @param \Magento\Checkout\Model\DefaultConfigProvider $subject
     * @param array $result
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InvalidEnumException|GuzzleException
     */
    public function afterGetConfig(
        \Magento\Checkout\Model\DefaultConfigProvider $subject,
        array $result
    ) {
        $items = $result['totalsData']['items'];

        foreach ($items as $index => $item) {
            $quoteItem = $this->checkoutSession->getQuote()->getItemById($item['item_id']);
            $originalPrice = $quoteItem->getPrice() * $quoteItem->getQty();

            if ($quoteItem->getCustomPrice() > 0) {
                $result['quoteItemData'][$index]['discountprice'] =
                    $this->pricingHelper->currency($this->basketHelper->getItemRowTotal($quoteItem), true, false);
                $discountAmount = $quoteItem->getDiscountAmount();
                $result['quoteItemData'][$index]['discountamount'] =
                    ($discountAmount > 0 && $discountAmount != null) ?
                        $this->pricingHelper->currency(
                            $discountAmount,
                            true,
                            false
                        ) : '';
                $result['quoteItemData'][$index]['discountamounttext'] = __("Save");
            } else {
                $result['quoteItemData'][$index]['discountprice'] = '';
            }
            $result['quoteItemData'][$index]['originalprice'] =
                $this->pricingHelper->currency($originalPrice, true, false);
        }

        return $result;
    }
}
