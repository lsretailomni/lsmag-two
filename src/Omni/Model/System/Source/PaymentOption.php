<?php

namespace Ls\Omni\Model\System\Source;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Payment\Model\Config;

/**
 * Class Paymethods
 * @package Ls\Omni\Model\System\Source
 */
class PaymentOption implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var ScopeConfigInterface
     */
    public $_appConfigScopeConfigInterface;

    /**
     * @var Config
     */
    public $_paymentModelConfig;

    /**
     * Paymethods constructor.
     * @param ScopeConfigInterface $appConfigScopeConfigInterface
     * @param Config $paymentModelConfig
     */
    public function __construct(
        ScopeConfigInterface $appConfigScopeConfigInterface,
        Config $paymentModelConfig
    ){
        $this->_appConfigScopeConfigInterface = $appConfigScopeConfigInterface;
        $this->_paymentModelConfig = $paymentModelConfig;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $payments = $this->_paymentModelConfig->getActiveMethods();
        $methods = array();
        foreach ($payments as $paymentCode => $paymentModel) {
            $paymentTitle = $this->_appConfigScopeConfigInterface->getValue('payment/' . $paymentCode . '/title');
            if($paymentCode!="free") {
                $methods[$paymentCode] = array(
                    'label' => $paymentTitle,
                    'value' => $paymentCode
                );
            }
        }
        return $methods;
    }
}