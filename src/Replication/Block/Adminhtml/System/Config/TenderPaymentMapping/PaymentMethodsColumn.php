<?php

namespace Ls\Replication\Block\Adminhtml\System\Config\TenderPaymentMapping;

use \Ls\Core\Model\LSR;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use \Ls\Omni\Model\System\Source\PaymentOption;

/**
 * payment methods class
 */
class PaymentMethodsColumn extends Select
{
    /**
     * @var PaymentOption
     */
    public $paymentOption;

    /**
     * PaymentMethodsColumn constructor.
     * @param Context $context
     * @param PaymentOption $paymentOption
     * @param array $data
     */
    public function __construct(
        Context $context,
        PaymentOption $paymentOption,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->paymentOption = $paymentOption;
    }

    /**
     * Set "name" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element
     *
     * @param $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::_toHtml();
    }

    /**
     * Return payment options array
     *
     * @return array
     */
    private function getSourceOptions(): array
    {
        $optionsArray   = $this->paymentOption->toOptionArray();
        $optionsArray[] = [
            'value' => LSR::LS_GIFTCARD_TENDER_TYPE,
            'label' => __('Gift Card')
        ];
        $optionsArray[] = [
            'value' => LSR::LS_LOYALTYPOINTS_TENDER_TYPE,
            'label' => __('Loyalty Points')
        ];

        return $optionsArray;
    }
}
