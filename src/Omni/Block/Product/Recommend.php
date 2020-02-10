<?php

namespace Ls\Omni\Block\Product;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfRecommendedItem;
use \Ls\Omni\Helper\LSRecommend as LSRecommendHelper;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;

/**
 * This file will be used for Shopping Cart/Home/Checkout page
 * Class Recommend
 * @package Ls\Omni\Block\Product\View
 */
class Recommend extends AbstractProduct
{
    /** @var LSR */
    public $lsr;

    /** @var LSRecommendHelper */
    public $LSRecommend;

    public function __construct(
        Context $context,
        LSRecommendHelper $LS_RecommendHelper,
        LSR $lsr,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->lsr         = $lsr;
        $this->LSRecommend = $LS_RecommendHelper;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        if ($this->LSRecommend->isLsRecommendEnable() && $this->LSRecommend->isLsRecommendEnableOnCartPage()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return ProductInterface[]|null
     */
    public function getProductRecommendationforCart()
    {
        $response            = null;
        $productSkus         = $this->LSRecommend->getProductSkusFromQuote();
        $recommendedProducts = $this->LSRecommend->getProductRecommendationfromOmni($productSkus);
        if ($recommendedProducts instanceof ArrayOfRecommendedItem) {
            return $this->LSRecommend->parseProductRecommendation($recommendedProducts);
        }
        return $response;
    }

    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('omni/ajax/RecommendationCart');
    }

    /**
     * @return bool
     */
    public function checkCartItems()
    {
        if ($this->_cartHelper->getItemsCount() === 0) {
            return false;
        } else {
            return true;
        }
    }
}
