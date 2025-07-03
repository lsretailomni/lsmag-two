<?php
declare(strict_types=1);

namespace Ls\Omni\Model\System\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Payment\Model\Config;

class PaymentOption implements OptionSourceInterface
{
    /**
     * @param ScopeConfigInterface $appConfigScopeConfigInterface
     * @param Config $paymentModelConfig
     */
    public function __construct(
        public ScopeConfigInterface $appConfigScopeConfigInterface,
        public Config $paymentModelConfig
    ) {
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $payments = $this->paymentModelConfig->getActiveMethods();
        $methods  = [];
        foreach ($payments as $paymentCode => $paymentModel) {
            $paymentTitle = $this->appConfigScopeConfigInterface->getValue('payment/' . $paymentCode . '/title');
            if ($paymentCode != "free") {
                $methods[$paymentCode] = [
                    'label' => $paymentTitle,
                    'value' => $paymentCode
                ];
            }
        }
        return $methods;
    }
}
