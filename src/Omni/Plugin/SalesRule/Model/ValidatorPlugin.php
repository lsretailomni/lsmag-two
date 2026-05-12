<?php
declare(strict_types=1);

namespace Ls\Omni\Plugin\SalesRule\Model;

use \Ls\Core\Model\LSR;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\SalesRule\Model\Validator;

/**
 * Interceptor to intercept sales rule validation
 */
class ValidatorPlugin
{
    /**
     * @param LSR $lsr
     */
    public function __construct(public LSR $lsr)
    {
    }

    /**
     * Around plugin to stop coupon validation
     *
     * @param Validator $subject
     * @param $proceed
     * @param CartInterface $quote
     * @return Validator|mixed
     * @throws NoSuchEntityException
     */
    public function aroundInitFromQuote(
        Validator $subject,
        $proceed,
        CartInterface $quote
    ) {
        if (!$this->lsr->isLSR($this->lsr->getCurrentStoreId(),
            false,
            $this->lsr->getBasketIntegrationOnFrontend()
        )) {
            return $proceed($quote);
        }

        return $subject;
    }
}
