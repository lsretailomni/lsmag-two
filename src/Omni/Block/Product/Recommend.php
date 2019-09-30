<?php

namespace Ls\Omni\Block\Product;

use \Ls\Omni\Helper\LSRecommend as LSRecommendHelper;

/**
 * This file will be used for Shopping Cart/Home/Checkout page
 * Class Recommend
 * @package Ls\Omni\Block\Product\View
 */
class Recommend extends \Magento\Catalog\Block\Product\AbstractProduct
{
    /** @var \Ls\Core\Model\LSR */
    public $lsr;

    /** @var LSRecommendHelper */
    public $LSRecommend;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        LSRecommendHelper $LS_RecommendHelper,
        \Ls\Core\Model\LSR $lsr,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->lsr = $lsr;
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
     * @return \Magento\Catalog\Api\Data\ProductInterface[]|null
     */
    public function getProductRecommendationforCart()
    {
        $response = null;
        $productSkus = $this->LSRecommend->getProductSkusFromQuote();
        $recommendedProducts = $this->LSRecommend->getProductRecommendationfromOmni($productSkus);
        if ($recommendedProducts instanceof \Ls\Omni\Client\Ecommerce\Entity\ArrayOfRecommendedItem) {
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
