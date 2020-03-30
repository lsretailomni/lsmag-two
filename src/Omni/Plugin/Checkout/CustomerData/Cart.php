<?php

namespace Ls\Omni\Plugin\Checkout\CustomerData;

use Exception;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Tax\Block\Item\Price\Renderer;
use Psr\Log\LoggerInterface;

/**
 * Class Cart
 * @package Ls\Omni\Plugin\Checkout\CustomerData
 */
class Cart
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
     * @var $checkoutHelper
     */
    public $checkoutHelper;

    /**
     * @var Data
     */
    public $data;

    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /** @var LoggerInterface */
    public $logger;

    /**
     * @var Renderer
     */
    public $itemPriceRenderer;

    /**
     * Cart constructor.
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $quoteRepository
     * @param \Magento\Checkout\Helper\Data $checkoutHelper
     * @param Data $data
     * @param BasketHelper $basketHelper
     * @param LoggerInterface $logger
     * @param Renderer $itemPriceRenderer
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        Data $data,
        BasketHelper $basketHelper,
        LoggerInterface $logger,
        Renderer $itemPriceRenderer
    ) {
        $this->checkoutSession   = $checkoutSession;
        $this->quoteRepository   = $quoteRepository;
        $this->checkoutHelper    = $checkoutHelper;
        $this->data              = $data;
        $this->basketHelper      = $basketHelper;
        $this->logger            = $logger;
        $this->itemPriceRenderer = $itemPriceRenderer;
    }

    /**
     * @param \Magento\Checkout\CustomerData\Cart $subject
     * @param array $result
     * @return array
     */
    public function afterGetSectionData(\Magento\Checkout\CustomerData\Cart $subject, array $result)
    {
        try {
            $quote                     = $this->checkoutSession->getQuote();
            $discountAmountTextMessage = __("Save");
            $items                     = $quote->getAllVisibleItems();
            if (is_array($result['items'])) {
                foreach ($result['items'] as $key => $itemAsArray) {
                    if ($item = $this->findItemById($itemAsArray['item_id'], $items)) {
                        $item->setCustomPrice($this->basketHelper->getItemRowTotal($item));
                        $this->itemPriceRenderer->setItem($item);
                        $this->itemPriceRenderer->setTemplate('Magento_Tax::checkout/cart/item/price/sidebar.phtml');
                        $originalPrice  = '';
                        $discountAmount = '';
                        if ($item->getDiscountAmount() > 0) {
                            $discountAmount = $this->checkoutHelper->formatPrice($item->getDiscountAmount());
                            $originalPrice  = $item->getProduct()->getPrice() * $item->getQty();
                        }
                        $result['items'][$key]['lsPriceOriginal']  = ($originalPrice != "") ?
                            $this->checkoutHelper->formatPrice($originalPrice) : $originalPrice;
                        $result['items'][$key]['lsDiscountAmount'] = ($discountAmount != "") ?
                            '(' . __($discountAmountTextMessage) . ' ' . $discountAmount . ')' : $discountAmount;
                        $result['items'][$key]['product_price']    = $this->itemPriceRenderer->toHtml();
                    }
                }
            }
            $grandTotalAmount = $this->data->getOrderBalance(
                $quote->getLsGiftCardAmountUsed(),
                $quote->getLsPointsSpent(),
                $this->basketHelper->getBasketSessionValue()
            );
            if ($grandTotalAmount > 0) {
                $result['subtotalAmount'] = $grandTotalAmount;
                $result['subtotal']       = isset($grandTotalAmount)
                    ? $this->checkoutHelper->formatPrice($grandTotalAmount)
                    : 0;
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $result;
    }

    /**
     * Find item by id in items haystack
     *
     * @param int $id
     * @param array $itemsHaystack
     * @return Item | bool
     */
    public function findItemById($id, $itemsHaystack)
    {
        if (is_array($itemsHaystack)) {
            foreach ($itemsHaystack as $item) {
                /** @var $item Item */
                if ((int)$item->getItemId() == $id) {
                    return $item;
                }
            }
        }
        return false;
    }
}
