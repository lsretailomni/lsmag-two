<?php


namespace Ls\Omni\Block\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use \Ls\Omni\Helper\LSRecommend as LSRecommendHelper;
use Magento\Framework\View\Element\Template;

/**
 * This file will be used for Shopping Cart/Home/Checkout page
 * Class View
 * @package Ls\Omni\Block\Product\View
 */

class Recommend extends \Magento\Catalog\Block\Product\AbstractProduct
{
    /** @var \Ls\Core\Model\LSR  */
    public $lsr;

    /** @var LSRecommendHelper  */
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
     * @return \Magento\Catalog\Api\Data\ProductInterface[]|null
     */
    public function getProductRecommendationforCart()
    {
        // only process if LS Recommend is enabled in general and on cart page.

        $response = null;

        if (!$this->LSRecommend->isLsRecommendEnable() || !$this->LSRecommend->isLsRecommendEnableOnCartPage()) {
            return $response;
        }
        $productSkus    =   $this->LSRecommend->getProductSkusFromQuote();
        $recommendedProducts = $this->LSRecommend->getProductRecommendationfromOmni($productSkus);

        // this is the recommended products we received from NAV so now we need to get the actual products from that.
        // check to see if we get correct response not full of errors.

        if ($recommendedProducts instanceof \Ls\Omni\Client\Ecommerce\Entity\ArrayOfRecommendedItem) {
            return $this->LSRecommend->parseProductRecommendation($recommendedProducts);
        }
        return $response;
    }
}
