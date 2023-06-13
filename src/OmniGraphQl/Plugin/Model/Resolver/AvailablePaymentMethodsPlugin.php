<?php
declare(strict_types=1);

namespace Ls\OmniGraphQl\Plugin\Model\Resolver;

use \Ls\Core\Model\LSR;
use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
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
     * @param callable $proceed
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws LocalizedException
     */
    public function aroundResolve(
        $subject,
        callable $proceed,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        $cart = $value['model'];
        return $this->getPaymentMethodsData($cart);
    }

    /**
     * Collect and return information about available payment methods
     *
     * @param CartInterface $cart
     * @return array
     * @throws NoSuchEntityException
     */
    public function getPaymentMethodsData(CartInterface $cart): array
    {
        $paymentInformation                 = $this->informationManagement->getPaymentInformation($cart->getId());
        $paymentMethods                     = $paymentInformation->getPaymentMethods();
        $clickAndCollectPaymentMethodsArr   = [];
        $clickAndCollectPaymentMethods      = $this->lsr->getStoreConfig(
            LSR::SC_PAYMENT_OPTION,
            $this->lsr->getCurrentStoreId()
        );
        $shippingMethod                     = $cart->getShippingAddress()->getShippingMethod();

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
                if ($paymentMethod->getCode() == "ls_payment_method_pay_at_store") {
                    continue;
                }
                $paymentMethodsData[] = [
                    'title' => $paymentMethod->getTitle(),
                    'code' => $paymentMethod->getCode(),
                ];
            }
        }

        return $paymentMethodsData;
    }
}
