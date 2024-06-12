<?php

namespace Ls\Omni\Plugin\Block\Adminhtml\Order\Create\Shipping;

use Magento\Directory\Model\Currency;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Block\Adminhtml\Order\Create\Shipping\Method\Form;
use \Ls\Core\Model\LSR;

/**
 * Plugin for shipping methods order editing
 */
class FormPlugin
{
    /**
     * @var Currency
     */
    public $currency;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @param Currency $currency
     * @param LSR $lsr
     */
    public function __construct(
        Currency $currency,
        LSR $lsr
    ) {
        $this->lsr = $lsr;
        $this->currency = $currency;
    }

    /**
     * Plugin to intercept get shipping rates while editing the order
     *
     * @param Form $subject
     * @param $result
     * @return void
     */
    public function afterGetShippingRates(Form $subject, $result)
    {
        $isOrderEdit = $this->lsr->getStoreConfig(
            LSR::LSR_ORDER_EDIT,
            $subject->getQuote()->getStoreId()
        );
        $url = $subject->getCurrentUrl();
        if ($isOrderEdit && strpos($url, 'order_edit') !== false)  {
            $carrier           = 'clickandcollect';
            $quote             = $subject->getQuote();
            $shippingMethod    = $quote->getShippingAddress()->getShippingMethod();
            $shippingMethodArr = explode('_', $shippingMethod);
            if (!empty($shippingMethodArr)) {
                $carrierCode = $shippingMethodArr[0];
            }
            if ($carrier != $carrierCode && array_key_exists($carrier, $result)) {
                unset($result[$carrier]);
            }
            if (array_key_exists($carrier, $result) && $carrier == $carrierCode) {
                foreach ($result as $carrierName => $rate) {
                    if ($carrierName != $carrier) {
                        unset($result[$carrierName]);
                    }
                }
            }
        }

        return $result;
    }
}
