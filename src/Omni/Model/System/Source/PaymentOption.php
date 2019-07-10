<?php

namespace Ls\Omni\Model\System\Source;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Payment\Model\Config;

/**
 * Class PaymentOption
 * @package Ls\Omni\Model\System\Source
 */
class PaymentOption implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var ScopeConfigInterface
     */
    public $appConfigScopeConfigInterface;

    /**
     * @var Config
     */
    public $paymentModelConfig;

    /**
     * PaymentOption constructor.
     * @param ScopeConfigInterface $appConfigScopeConfigInterface
     * @param Config $paymentModelConfig
     */
    public function __construct(
        ScopeConfigInterface $appConfigScopeConfigInterface,
        Config $paymentModelConfig
    ) {
        $this->appConfigScopeConfigInterface = $appConfigScopeConfigInterface;
        $this->paymentModelConfig = $paymentModelConfig;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $payments = $this->paymentModelConfig->getActiveMethods();
        $methods = [];
        foreach ($payments as $paymentCode => $paymentModel) {
            $paymentTitle = $this->appConfigScopeConfigInterface->getValue('payment/' . $paymentCode . '/title');
            if ($paymentCode != "free") {
                $methods[$paymentCode] =[
                    'label' => $paymentTitle,
                    'value' => $paymentCode
                ];
            }
        }
        return $methods;
    }
}