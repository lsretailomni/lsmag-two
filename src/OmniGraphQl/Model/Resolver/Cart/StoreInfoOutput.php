<?php
declare(strict_types=1);

namespace Ls\OmniGraphQl\Model\Resolver\Cart;

use \Ls\Omni\Helper\Data as DataHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Zend_Log_Exception;

class StoreInfoOutput implements ResolverInterface
{
    /**
     * @var DataHelper
     */
    public $dataHelper;
    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * @param DataHelper $dataHelper
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        DataHelper $dataHelper,
        CheckoutSession $checkoutSession
    ) {
        $this->dataHelper       = $dataHelper;
        $this->checkoutSession  = $checkoutSession;
    }

    /**
     * Add proper swatch image path
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     *
     * @return array
     *
     * @throws NoSuchEntityException|Zend_Log_Exception|\Magento\Framework\Exception\LocalizedException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {

        $storeInfo = [];
        $cart   = $this->checkoutSession->getQuote();
        $pickupStoreId = $cart->getPickupStore();
        $pickupStoreName = ($pickupStoreId) ? $this->dataHelper->getStoreNameById($pickupStoreId) : "";

        if ($pickupStoreId && $cart->getShippingAddress()->getShippingMethod() == "clickandcollect_clickandcollect") {

            $pickupDate = $pickupTime = "";
            if ($cart->getPickupDateTimeslot()
                && count($dateTimeArr = explode(" ", $cart->getPickupDateTimeslot()))>0
            ) {
                $pickupDate  = $dateTimeArr[0];
                if (array_key_exists("1", $dateTimeArr)) {
                    $pickupTime  = array_key_exists("2", $dateTimeArr) ?
                        $dateTimeArr["1"]." ".$dateTimeArr["2"]
                        : $dateTimeArr[1];
                }

            }

            $storeInfo["store_id"] = $pickupStoreId;
            $storeInfo["store_name"] = $pickupStoreName;
            $storeInfo["pickup_date"] = $pickupDate;
            $storeInfo["pickup_time"] = $pickupTime;

        }

        return $storeInfo;
    }
}
