<?php

namespace Ls\OmniGraphQl\Plugin\Model\Resolver;

use \Ls\Omni\Helper\BasketHelper;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Interceptor to intercept CustomerCart Resolver
 */
class CartPlugin
{
    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * @param BasketHelper $basketHelper
     */
    public function __construct(
        BasketHelper $basketHelper
    ) {
        $this->basketHelper = $basketHelper;
    }

    /**
     * After plugin to set quote one_list_calculate in checkout session
     *
     * @param $subject
     * @param $result
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed
     */
    public function afterResolve(
        $subject,
        $result,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (isset($result['model'])) {
            $quote = $result['model'];

            if ($quote->getBasketResponse()) {
                // phpcs:ignore Magento2.Security.InsecureFunction
                $this->basketHelper->setOneListCalculationInCheckoutSession(unserialize($quote->getBasketResponse()));
            }
        }
        return $result;
    }
}
