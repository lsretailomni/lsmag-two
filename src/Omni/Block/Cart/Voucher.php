<?php
declare(strict_types=1);

namespace Ls\Omni\Block\Cart;

use \Ls\Omni\Helper\VoucherHelper;
use Magento\Checkout\Block\Cart\AbstractCart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context;

class Voucher extends AbstractCart
{
    /**
     * @param VoucherHelper $voucherHelper
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param array $data
     */
    public function __construct(
        public VoucherHelper $voucherHelper,
        Context $context,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $data);
    }

    /**
     * Get voucher amount used
     *
     * @return string
     */
    public function getVoucherAmountUsed()
    {
        $amounts = array_column(json_decode((string)$this->getQuote()->getLsPosDataEntries(), true) ?? [], 'amount');
        $total = array_sum($amounts);
        return $total > 0 ? $total : "";
    }

    /**
     * Get voucher number
     *
     * @return string
     */
    public function getVoucherNo()
    {
        return $this->getQuote()->getLsPosDataEntries();
    }

    /**
     * Get voucher pin
     *
     * @return string
     */
    public function getVoucherPin()
    {
        $entries = json_decode((string)$this->getQuote()->getLsPosDataEntries(), true) ?? [];
        return $entries ? (end($entries)['pin_code'] ?? null) : null;
    }

    /**
     * Get voucher is enable on cart page
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function getVoucherActive()
    {
        return $this->voucherHelper->isVoucherEnabled('cart');
    }

    /**
     * Get is pin code field enable
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isPinCodeFieldEnable()
    {
        return $this->voucherHelper->isPinCodeFieldEnable();
    }
}

