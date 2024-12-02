<?php

namespace Ls\OmniGraphQl\Plugin\Omni\Model\Api;

use \Ls\Omni\Api\DiscountManagementInterface;
use \Ls\OmniGraphQl\Helper\DataHelper;

class DiscountManagementPlugin
{
    /**
     * @var DataHelper
     */
    public $dataHelper;

    /**
     * @param DataHelper $dataHelper
     */
    public function __construct(
        DataHelper $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    /**
     * After plugin to set required data in the session
     *
     * @param DiscountManagementInterface $subject
     * @param $cartId
     * @return float[]|int[]|string[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeCheckDiscountValidity(
        DiscountManagementInterface $subject,
        $cartId
    ) {
        if (!is_numeric($cartId)) {
            $quoteId = $subject->quoteIdMaskFactory->create()->load($cartId, 'masked_id')->getQuoteId();
        }
        $quote = $subject->basketHelper->getCartRepositoryObject()->get($quoteId);
        $this->dataHelper->setCurrentQuoteDataInCheckoutSession($quote);

        return [$cartId];
    }
}
