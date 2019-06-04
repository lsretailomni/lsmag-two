<?php
namespace Ls\Omni\Block\Product\View\Discount;

use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Class View
 * @package Ls\Omni\Block\Product\View
 */
class Proactive extends \Magento\Catalog\Block\Product\View
{
    /** @var \Ls\Core\Model\LSR */
    public $lsr;

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * Proactive constructor.
     * @param \Ls\Core\Model\LSR $lsr
     * @param LoyaltyHelper $loyaltyHelper
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        \Ls\Core\Model\LSR $lsr,
        LoyaltyHelper $loyaltyHelper,
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $urlEncoder,
            $jsonEncoder,
            $string,
            $productHelper,
            $productTypeConfig,
            $localeFormat,
            $customerSession,
            $productRepository,
            $priceCurrency,
            $data
        );
        $this->lsr = $lsr;
        $this->loyaltyHelper = $loyaltyHelper;
    }

    /**
     * @return array|\Ls\Omni\Client\Ecommerce\Entity\DiscountsGetResponse|\Ls\Omni\Client\Ecommerce\Entity\ProactiveDiscount[]|\Ls\Omni\Client\ResponseInterface|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProactiveDiscounts()
    {
        $currentProduct = $this->getProduct();
        $itemId = $currentProduct->getSku();
        $storeId = $this->lsr->getDefaultWebStore();

        if ($response = $this->loyaltyHelper->getProactiveDiscounts($itemId, $storeId)) {
            if (!is_array($response)) {
                $response = [$response];
            }
            return $response;
        } else {
            return [];
        }
    }

    /**
     * @param \Ls\Omni\Client\Ecommerce\Entity\ProactiveDiscount $discount
     * @return array|string
     */
    public function getFormattedDescription(\Ls\Omni\Client\Ecommerce\Entity\ProactiveDiscount $discount)
    {
        $currentProduct = $this->getProduct();
        $itemId = $currentProduct->getSku();
        $description = [];
        if ($discount->getDescription()) {
            $description[] = $discount->getDescription();
        }
        if (floatval($discount->getMinimumQuantity()) > 0) {
            $description[] = "Minimum Qty : ". number_format((float)$discount->getMinimumQuantity(), 2, '.', '');
        }
        if (floatval($discount->getPercentage()) > 0) {
            $description[] = "Percentage Discount : ".
                number_format((float)$discount->getPercentage(), 2, '.', '') . "%";
        }
        if ($discount->getItemIds()) {
            $itemIds = $discount->getItemIds()->getString();
            if (!is_array($itemIds)) {
                $itemIds = [$discount->getItemIds()->getString()];
            }
            $itemIds = array_unique($itemIds);
            $itemIds = array_diff($itemIds, [$itemId]);
            foreach ($itemIds as &$sku) {
                $url = $this->getProductBySku($sku);
                if (!empty($url)) {
                    $sku = "<a href = '".$url."' target='_blank'>".$sku.'</a>';
                }
            }
            $description[] = "Other Items : ".implode(", ", $itemIds);
        }
        $description = implode("<br/>", $description);
        return $description;
    }

    /**
     * @param $sku
     * @return string
     */
    public function getProductBySku($sku)
    {
        $url = "";
        try {
            $product = $this->productRepository->get($sku);
            $url = $product->getProductUrl();
        } catch (\Exception $e) {
            $this->_logger->debug($e->getMessage());
        }

        return $url;
    }
}
