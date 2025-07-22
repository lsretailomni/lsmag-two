<?php
declare(strict_types=1);

namespace Ls\Omni\Plugin\Checkout\CustomerData;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
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
     * @param CheckoutSession $checkoutSession
     * @param \Magento\Checkout\Helper\Data $checkoutHelper
     * @param Data $data
     * @param BasketHelper $basketHelper
     * @param LoggerInterface $logger
     * @param Renderer $itemPriceRenderer
     * @param LSR $lsr
     */
    public function __construct(
        public CheckoutSession $checkoutSession,
        public \Magento\Checkout\Helper\Data $checkoutHelper,
        public Data $data,
        public BasketHelper $basketHelper,
        public LoggerInterface $logger,
        public Renderer $itemPriceRenderer,
        public LSR $lsr
    ) {
    }

    /**
     * After plugin to set price, discount, row_total in minicart
     *
     * @param \Magento\Checkout\CustomerData\Cart $subject
     * @param array $result
     * @return array
     * @throws NoSuchEntityException|LocalizedException|GuzzleException
     */
    public function afterGetSectionData(
        \Magento\Checkout\CustomerData\Cart $subject,
        array $result
    ) {
        $quote = $this->checkoutSession->getQuote();

        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            try {
                $discountAmountTextMessage = __("Save");
                $items = $quote->getAllVisibleItems();
                if (is_array($result['items'])) {
                    foreach ($result['items'] as $key => $itemAsArray) {
                        if ($item = $this->findItemById($itemAsArray['item_id'], $items)) {
                            $lineDiscount = $this->basketHelper->getItemRowDiscount($item);
                            $customPrice = $this->basketHelper->getItemRowTotal($item);
                            $item->setCustomPrice($customPrice);
                            $item->setDiscountAmount($lineDiscount);
                            $this->itemPriceRenderer->setItem($item);
                            $this->itemPriceRenderer->setTemplate(
                                'Magento_Tax::checkout/cart/item/price/sidebar.phtml'
                            );
                            $originalPrice = '';
                            $discountAmount = '';
                            if ($item->getDiscountAmount() > 0) {
                                $discountAmount = $this->checkoutHelper->formatPrice($item->getDiscountAmount());
                                $originalPrice = $item->getProductType() == Type::TYPE_BUNDLE ?
                                    $item->getRowTotal() :
                                    $this->basketHelper->itemHelper->convertToCurrentStoreCurrency(
                                        $item->getProduct()->getPrice() * $item->getQty()
                                    );
                                $originalPrice = $this->basketHelper->getPriceAddingCustomOptions(
                                    $item,
                                    $originalPrice
                                );
                            }

                            $item->setPriceInclTax($customPrice);
                            $result['items'][$key]['lsPriceOriginal'] = ($originalPrice != "") ?
                                $this->checkoutHelper->formatPrice($originalPrice) : $originalPrice;
                            $result['items'][$key]['lsDiscountAmount'] = ($discountAmount != "") ?
                                '(' . __($discountAmountTextMessage) . ' ' . $discountAmount . ')' : $discountAmount;
                            $result['items'][$key]['product_price'] = $this->itemPriceRenderer->toHtml();
                        }
                    }
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        } else {
            if (is_array($result['items'])) {
                foreach ($result['items'] as $key => $itemAsArray) {
                    $result['items'][$key]['lsPriceOriginal'] = "";
                    $result['items'][$key]['lsDiscountAmount'] = "";
                }
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
