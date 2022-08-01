<?php

namespace Ls\OmniGraphQl\Plugin\Model\Resolver;

use \Ls\Hospitality\Model\LSR;
use \Ls\Replication\Model\ResourceModel\ReplStore\Collection;
use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * AvailablePaymentMethods plugin responsible for filtering payment methods based on click and collect configuration
 */
class AvailablePaymentMethodsPlugin
{
    /**
     * @var PaymentInformationManagementInterface
     */
    private $informationManagement;
    /**
     * @var LSR
     */
    private LSR $lsr;

    /**
     * @param PaymentInformationManagementInterface $informationManagement
     * @param LSR $lsr
     */
    public function __construct(
        PaymentInformationManagementInterface $informationManagement,
        LSR $lsr
    ) {
        $this->informationManagement = $informationManagement;
        $this->lsr                   = $lsr;
    }

    /**
     * Around plugin to filter payment methods based on click and collect stores configuration
     *
     * @param AvailablePaymentMethods $subject
     * @param Collection $result
     * @param CartInterface $cart
     * @return array
     * @throws \Zend_Log_Exception
     */
    public function aroundGetPaymentMethodsData(
        AvailablePaymentMethods $subject,
        $result,
        CartInterface $cart
    ) {

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $paymentInformation                 = $this->informationManagement->getPaymentInformation($cart->getId());
        $paymentMethods                     = $paymentInformation->getPaymentMethods();
        $clickAndCollectPaymentMethodsArr   = [];
        $clickAndCollectPaymentMethods      = $this->lsr->getStoreConfig(LSR::SC_CLICKCOLLECT_PAYMENT_OPTION);
        $shippingMethod                     = $cart->getShippingAddress()->getShippingMethod();

        $logger->info('im here at AvailablePaymentMethods plugin '.$shippingMethod);

        if ($shippingMethod == "clickandcollect_clickandcollect" &&
            $clickAndCollectPaymentMethods
        ) {
            $clickAndCollectPaymentMethodsArr = explode(",", $clickAndCollectPaymentMethods);
        }

        $paymentMethodsData = [];
        foreach ($paymentMethods as $paymentMethod) {
            if ($shippingMethod == "clickandcollect_clickandcollect") {
                if (in_array($paymentMethod->getCode(), $clickAndCollectPaymentMethodsArr)) {
                    $paymentMethodsData[] = [
                        'title' => $paymentMethod->getTitle(),
                        'code' => $paymentMethod->getCode(),
                    ];
                }
            } else {
                $paymentMethodsData[] = [
                    'title' => $paymentMethod->getTitle(),
                    'code' => $paymentMethod->getCode(),
                ];
            }
        }

        return $paymentMethodsData;
    }
}
