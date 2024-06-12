<?php

namespace Ls\Omni\Plugin\Checkout\CustomerData;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use Magento\Catalog\Model\Product\Type;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Item;
use Magento\Tax\Block\Item\Price\Renderer;
use Psr\Log\LoggerInterface;

/**
 * Interceptor to intercept minicart data
 */
class Cart
{
    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

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

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var Renderer
     */
    public $itemPriceRenderer;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * Cart constructor.
     * @param CheckoutSession $checkoutSession
     * @param \Magento\Checkout\Helper\Data $checkoutHelper
     * @param Data $data
     * @param BasketHelper $basketHelper
     * @param LoggerInterface $logger
     * @param Renderer $itemPriceRenderer
     * @param LSR $lsr
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        Data $data,
        BasketHelper $basketHelper,
        LoggerInterface $logger,
        Renderer $itemPriceRenderer,
        LSR $lsr
    ) {
        $this->checkoutSession   = $checkoutSession;
        $this->checkoutHelper    = $checkoutHelper;
        $this->data              = $data;
        $this->basketHelper      = $basketHelper;
        $this->logger            = $logger;
        $this->itemPriceRenderer = $itemPriceRenderer;
        $this->lsr               = $lsr;
    }

    /**
     * @param \Magento\Checkout\CustomerData\Cart $subject
     * @param array $result
     * @return array
     * @throws NoSuchEntityException|LocalizedException
     */
    public function afterGetSectionData(\Magento\Checkout\CustomerData\Cart $subject, array $result)
    {
        $quote = $this->checkoutSession->getQuote();

        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            try {
                $discountAmountTextMessage = __("Save");
                $items                     = $quote->getAllVisibleItems();
                if (is_array($result['items'])) {
                    foreach ($result['items'] as $key => $itemAsArray) {
                        if ($item = $this->findItemById($itemAsArray['item_id'], $items)) {
                            $lineDiscount = $this->basketHelper->getItemRowDiscount($item);
                            $item->setCustomPrice($this->basketHelper->getItemRowTotal($item));
                            $item->setDiscountAmount($lineDiscount);
                            $this->itemPriceRenderer->setItem($item);
                            $this->itemPriceRenderer->setTemplate(
                                'Magento_Tax::checkout/cart/item/price/sidebar.phtml'
                            );
                            $originalPrice  = '';
                            $discountAmount = '';
                            if ($item->getDiscountAmount() > 0) {
                                $discountAmount = $this->checkoutHelper->formatPrice($item->getDiscountAmount());
                                $originalPrice  = $item->getProductType() == Type::TYPE_BUNDLE ?
                                    $item->getRowTotal()  : $item->getPrice();
                            }
                            $result['items'][$key]['lsPriceOriginal']  = ($originalPrice != "") ?
                                $this->checkoutHelper->formatPrice($originalPrice) : $originalPrice;
                            $result['items'][$key]['lsDiscountAmount'] = ($discountAmount != "") ?
                                '(' . __($discountAmountTextMessage) . ' ' . $discountAmount . ')' : $discountAmount;
                            $result['items'][$key]['product_price']    = $this->itemPriceRenderer->toHtml();
                        }
                    }
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        } else {
            if (is_array($result['items'])) {
                foreach ($result['items'] as $key => $itemAsArray) {
                    $result['items'][$key]['lsPriceOriginal']  = "";
                    $result['items'][$key]['lsDiscountAmount'] = "";
                }
            }

            if ($this->lsr->isEnabled()) {
                $result['subtotalAmount'] = $quote->getGrandTotal();
                $result['subtotal']       = $this->checkoutHelper->formatPrice($quote->getGrandTotal());
            }
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
