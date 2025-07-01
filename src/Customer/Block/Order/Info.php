<?php

namespace Ls\Customer\Block\Order;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\LSCMemberSalesBuffer;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * This class is overriding in hospitality module
 *
 * Block being used for various sections on order detail
 */
class Info extends AbstractOrderBlock
{
    /**
     * @var string
     */
    // @codingStandardsIgnoreStart
    protected $_template = 'Ls_Customer::order/info.phtml';
    // @codingStandardsIgnoreEnd

    /**
     * Get payment info html
     *
     * @return string
     */
    public function getPaymentInfoHtml()
    {
        return $this->getChildHtml('payment_info');
    }

    /**
     * For getting shipping and billing address
     *
     * @param false $isBillingAddress
     * @return string
     */
    public function getFormattedAddress(bool $isBillingAddress = false)
    {
        $order   = $this->getLscMemberSalesBuffer();
        $address = '';
        if ($isBillingAddress) {
            if (!empty($order->getAddress()) && !empty($order->getCountryRegionCode())) {
                $address .= $order->getName() ? $order->getName() . '<br/>' : '';
                $address .= $order->getAddress() ? $order->getAddress() . '<br/>' : '';
                $address .= $order->getAddress2() ? $order->getAddress2() . '<br/>' : '';
                $address .= $order->getCity() ? $order->getCity() . ', ' : '';
                $address .= $order->getCounty() ? $order->getCounty() . ', ' : '';
                $address .= $order->getPostCode() ? $order->getPostCode() . '<br/>' : '';
                $address .= $this->getCountryName($order->getCountryRegionCode()) ?
                    $this->getCountryName($order->getCountryRegionCode()) . '<br/>' : '';
                /** TODO update with Address Phone Number */
                $address .= $order->getPhoneNo() ?
                    "<a href='tel:" . $order->getPhoneNo() . "'>"
                    . $order->getPhoneNo() . '</a>' : '';
            }
        } else {
            if (!empty($order->getShipToName()) && !empty($order->getCountryRegionCode())) {
                $address .= $order->getShipToName() ? $order->getShipToName() . '<br/>' : '';
                $address .= $order->getShipToAddress() ? $order->getShipToAddress() . '<br/>' : '';
                $address .= $order->getShipToAddress2() ? $order->getShipToAddress2() . '<br/>' : '';
                $address .= $order->getShipToCity() ? $order->getShipToCity() . ', ' : '';
                $address .= $order->getShipToCounty() ? $order->getShipToCounty() . ', ' : '';
                $address .= $order->getShipToPostCode() ? $order->getShipToPostCode() . '<br/>' : '';
                $address .= $this->getCountryName($order->getShipToCountryRegionCode()) ?
                    $this->getCountryName($order->getShipToCountryRegionCode()) . '<br/>' : '';
                /** TODO update with Address Phone Number */
                $address .= $order->getShipToPhoneNo() ?
                    "<a href='tel:" . $order->getShipToPhoneNo() . "'>"
                    . $order->getShipToPhoneNo() . '</a>' : '';
            }
        }

        return $address;
    }

    /**
     * Get country name by country code
     *
     * @param $countryCode
     * @return string
     */
    public function getCountryName($countryCode)
    {
        $country = $this->countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
    }

    /**
     * @inheritDoc
     */
    protected function _prepareLayout()
    {
        $order = $this->getLscMemberSalesBuffer();
        if ($order) {
            $customerOrderNo = $this->orderHelper->getParameterValues($order, "Document ID");
            $orderId         = $customerOrderNo ?: $this->orderHelper->getParameterValues($order, "Id");

            if (!empty($customerOrderNo)) {
                $type = __('Order');
            } else {
                $type = $order->getIdType();
            }
            $this->pageConfig->getTitle()->set(__('%1 # %2', $type, $orderId));
        }
    }

    /**
     * Retrieve current order model instance
     *
     * @param $all
     * @return false|mixed|null
     */
    public function getOrder($all = false)
    {
        return $this->orderHelper->getOrder($all);
    }

    /**
     * To fetch Status value from SalesEntryGetResult or SalesEntryGetReturnSalesResult
     * depending on the structure of SalesEntry node
     *
     * @return mixed
     */
    public function getOrderStatus()
    {
        return $this->orderHelper->getOrderStatus($this->getLscMemberSalesBuffer());
    }

    /**
     * To fetch ClickAndCollectOrder value from SalesEntryGetResult or SalesEntryGetReturnSalesResult
     *
     * Depending on the structure of SalesEntry node
     *
     * @return mixed
     */
    public function getClickAndCollectOrder()
    {
        $order = $this->getLscMemberSalesBuffer();
        if ($order->getCreatedAtStore() !== $order->getStoreNo()) {
            return true;
        }
        return false;
    }

    /**
     * To fetch DocumentRegTime value from SalesEntryGetResult or SalesEntryGetReturnSalesResult
     * depending on the structure of SalesEntry node
     * @return string|null
     */
    public function getDocRegistraionTime()
    {
        return $this->orderHelper->getParameterValues($this->getLscMemberSalesBuffer(), "Date Time");
    }

    /**
     * Get order details
     *
     * @return LSCMemberSalesBuffer
     */
    public function getLscMemberSalesBuffer()
    {
        $order = $this->getOrder();

        return $order->getLscMemberSalesBuffer();
    }

    /**
     * Check to see if current order is click and collect
     *
     * @return bool
     */
    public function isClickAndCollectOrder()
    {
        $order = $this->getOrder();
        $isCc = false;

        foreach ($order->getLscMemberSalesDocLine() ?? [] as $line) {
            if ($line->getClickAndCollectLine()) {
                $isCc = true;
                break;
            }
        }

        return $isCc;
    }

    /**
     * Get selected shipment method for the order, use the one in magento if available
     *
     * @return Phrase|string
     */
    public function getShippingDescription()
    {
        $magentoOrder = $this->getMagOrder();
        $status       = $this->getClickAndCollectOrder();

        if ($magentoOrder) {
            return $magentoOrder->getShippingDescription();
        }

        if ($status) {
            return __('Click and Collect');
        }

        return '';
    }

    /**
     * Return Requested Delivery Date
     *
     * @return string|null
     */
    public function getRequestedDeliveryDate()
    {
        $format                = $this->lsr->getStoreConfig(LSR::PICKUP_DATE_FORMAT);
        $requestedDeliveryDate = $this->getLscMemberSalesBuffer()->getRequestedDeliveryDate();

        if ($requestedDeliveryDate && $requestedDeliveryDate != '0001-01-01T00:00:00') {
            return $this->orderHelper->getDateTimeObject()->date(
                $format,
                $this->getLscMemberSalesBuffer()->getRequestedDeliveryDate()
            );
        }

        return null;
    }

    /**
     * DEV Notes:
     * 1st entry is for normal tender type
     * 2nd entry is specific for Giftcard.
     * @return array
     * @throws NoSuchEntityException
     */
    public function getPaymentDescription()
    {
        $paymentLines      = $this->getOrderPayments();
        $methods           = $giftCardInfo = [];
        $tenderTypeMapping = $this->dataHelper->getTenderTypesPaymentMapping();
        if ($paymentLines) {
            foreach ($paymentLines as $line) {
                /**
                 * Payments line can include multiple payment types
                 * i-e Refunds etc, but we only need to show Payment Type
                 * whose type == Payment and Pre Authorization
                 */
//                if ($line->getType() === PaymentType::PAYMENT || $line->getType() === PaymentType::PRE_AUTHORIZATION
//                    || $line->getType() === PaymentType::NONE) {
                if ($line->getEntryType() == 1) {
                    $tenderTypeId = $line->getNumber();
                    if (array_key_exists($tenderTypeId, $tenderTypeMapping)) {
                        $method    = $tenderTypeMapping[$tenderTypeId];
                        $methods[] = __($method);
                        if (!empty($line->getCardNo())) {
                            $giftCardTenderId = $this->orderHelper->getPaymentTenderTypeId(LSR::LS_GIFTCARD_TENDER_TYPE);
                            if ($giftCardTenderId == $tenderTypeId) {
                                $giftCardInfo[0] = $line->getCardNo();
                                $giftCardInfo[1] = $line->getAmount();
                            }
                        }
                    } else {
                        $methods[] = __('Unknown');
                    }
                }
            }

            $methods = array_unique($methods);
            if (empty($paymentLines)) {
                $magOrder = $this->getMagOrder();
                if ($magOrder != null) {
                    $magPaymentMethod = $this->getMagOrder()->getPayment()->getMethodInstance()->getTitle();
                    $methods[]        = $magPaymentMethod;
                }
            }

        }

        return [implode(', ', $methods), $giftCardInfo];
    }

    /**
     * To fetch Payments value from SalesEntryGetResult or SalesEntryGetReturnSalesResult
     *
     * Depending on the structure of SalesEntry node
     *
     * @return array
     */
    public function getOrderPayments()
    {
        $paymentLines = [];
        $orderTransactions = $this->getOrder(true)->getData();
        foreach ($orderTransactions as $key => $lines) {
            if ($key != "LSCMemberSalesDocLine") {
                continue;
            }
            if (!is_array($lines)) {
                $lines = [$lines];
            }
            foreach ($lines as $line) {
                if ($line->getEntryType() == 1) {
                    $paymentLines[] = $line;
                }
            }
        }
        return $paymentLines;
    }

    /**
     * Format loyalty points
     * @return string
     */
    public function getFormattedLoyaltyPoints()
    {
        $orderTransactions = $this->getOrder(true)->getData();
        $points            = 0;

        if (!is_array($orderTransactions)) {
            $orderTransactions = [$orderTransactions];
        }

        $points += $this->orderHelper->getFilterValues($orderTransactions, "Points Rewarded", "LSCMemberSalesBuffer");
        return number_format((float)$points, 2, '.', '');
    }

    /**
     * Format gift card price
     * @param $giftCardAmount
     * @return string
     */
    public function getGiftCardFormattedPrice($giftCardAmount)
    {
        return $this->priceHelper->currency($giftCardAmount, true, false);
    }

    /**
     * Formulating order printing url
     *
     * @param $order
     * @return string
     */
    public function getPrintUrl($order)
    {
        $reqType = $this->getRequest()->getParam('type');

        $params ['order_id'] = $order->getCustomerOrderNo() ?: $order->getId();

        if ($reqType) {
            $params ['order_id'] = $this->getRequest()->getParam('order_id');
            $params ['type']     = $reqType;
        }

        return $order ? $this->getUrl(
            'customer/order/print',
            $params
        ) : '';
    }

    /**
     * Formulating reordering url
     *
     * @param $order
     * @return string
     */
    public function getReorderUrl($order)
    {
        return $order ? $this->getUrl('sales/order/reorder', ['order_id' => $order->getId()]) : '';
    }

    /**
     * Formulating order canceling url
     *
     * @param OrderInterface $magentoOrder
     * @param SalesEntry $centralOrder
     * @return string
     */
    public function getCancelUrl(OrderInterface $magentoOrder, SalesEntry $centralOrder)
    {
        return $magentoOrder && $centralOrder ? $this->getUrl(
            'customer/order/cancel',
            [
                'magento_order_id' => $magentoOrder->getId(),
                'central_order_id' => $centralOrder->getId(),
                'id_type'          => $centralOrder->getIdType()
            ]
        ) : '';
    }

    /**
     * Check if order cancellation on frontend is enabled or not
     *
     * @return bool|string
     * @throws NoSuchEntityException
     */
    public function orderCancellationOnFrontendIsEnabled()
    {
        return $this->lsr->orderCancellationOnFrontendIsEnabled();
    }

    /**
     * Can show click and collect yes/no on order view frontend
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function canShowClickAndCollect()
    {
        return $this->lsr->getCurrentIndustry($this->_storeManager->getStore()->getId())
            === LSR::LS_INDUSTRY_VALUE_RETAIL;
    }

    /**
     * Can show requested delivery date on order view frontend
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function canShowRequestedDeliveryDate()
    {
        return $this->lsr->isPickupTimeslotsEnabled();
    }
}
