<?php

namespace Ls\OmniGraphQl\Plugin\Model\Resolver;

use \Ls\Omni\Helper\BasketHelper;

/**
 * Interceptor to intercept GetCartForUser methods
 */
class GetCartForUserPlugin
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
     * @return mixed
     */
    public function afterExecute(
        $subject,
        $result
    ) {
        if ($result && $result->getBasketResponse()) {
            $this->basketHelper->setOneListCalculationInCheckoutSession(unserialize($result->getBasketResponse()));
        }

        return $result;
    }
}
