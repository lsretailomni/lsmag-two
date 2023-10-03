<?php

namespace Ls\Omni\Plugin\Quote\Model;

use \Ls\Omni\Helper\BasketHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Plugin to store basket_response in the quote table
 */
class QuoteRepositoryPlugin
{
    /**
     * @var BasketHelper
     */
    private $basketHelper;

    /**
     * @param BasketHelper $basketHelper
     */
    public function __construct(
        BasketHelper $basketHelper
    ) {
        $this->basketHelper = $basketHelper;
    }

    /**
     * Setting basket_response into quote
     *
     * @param $subject
     * @param CartInterface $quote
     * @return CartInterface[]
     * @throws NoSuchEntityException
     */
    public function beforeSave($subject, CartInterface $quote)
    {
        $lsr = $this->basketHelper->getLsrModel();

        if ($lsr->isLSR($lsr->getCurrentStoreId())) {
            $oneListCalculate = $this->basketHelper->getOneListCalculationFromCheckoutSession();

            if ($oneListCalculate) {
                // phpcs:ignore Magento2.Security.InsecureFunction
                $quote->setBasketResponse(serialize($oneListCalculate));
            }
        }

        return [$quote];
    }
}
