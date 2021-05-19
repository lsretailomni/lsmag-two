<?php

namespace Ls\Omni\Plugin\Quote\Model;

use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\QuoteRepository;

/**
 * Plugin to store one_list_id in the quote table
 */
class QuoteRepositoryPlugin
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var BasketHelper
     */
    private $basketHelper;

    /**
     * @param BasketHelper $basketHelper
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        BasketHelper $basketHelper,
        CheckoutSession $checkoutSession
    ) {
        $this->basketHelper = $basketHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Setting ls_one_list_id into quote only if its not there and there are items in the cart
     * @param QuoteRepository $subject
     * @param CartInterface $quote
     * @return CartInterface[]
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function beforeSave(QuoteRepository $subject, CartInterface $quote)
    {
        if ($this->basketHelper->lsr->isLSR($this->basketHelper->lsr->getCurrentStoreId())) {
            if (!$quote->getLsOneListId() && !empty($quote->getAllVisibleItems())) {
                $basketHelper = $this->basketHelper->get();
                if ($basketHelper) {
                    $this->basketHelper->setOneListInCustomerSession($basketHelper);
                    $quote->setLsOneListId($basketHelper->getId());
                }
            }
        }

        return [$quote];
    }
}
