<?php
declare(strict_types=1);

namespace Ls\Omni\Observer;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\ResourceModel\Quote;
use Psr\Log\LoggerInterface;

class HidePaymentMethods implements ObserverInterface
{
    /**
     * @param BasketHelper $basketHelper
     * @param Data $data
     * @param LoggerInterface $logger
     * @param Quote $quoteResourceModel
     * @param LSR $lsr
     */
    public function __construct(
        public BasketHelper $basketHelper,
        public Data $data,
        public LoggerInterface $logger,
        public Quote $quoteResourceModel,
        public LSR $lsr
    ) {
    }

    /**
     * Entry point for the observer
     *
     * @param Observer $observer
     * @return void
     * @throws GuzzleException
     */
    public function execute(Observer $observer)
    {
        try {
            $basketData         = $this->basketHelper->getBasketSessionValue();
            $quote              = $this->basketHelper->checkoutSession->getQuote();
            $shippingAmount     = $quote->getShippingAddress()->getShippingAmount();
            $shippingMethod     = $quote->getShippingAddress()->getShippingMethod();
            $paymentOptionArray = explode(
                ',',
                $this->lsr->getStoreConfig(LSR::SC_PAYMENT_OPTION, $this->lsr->getCurrentStoreId())
            );
            $orderTotal      = $this->data->getOrderBalance(
                $quote->getLsGiftCardAmountUsed(),
                $quote->getLsPointsSpent(),
                $basketData
            );
            $orderTotal      = $orderTotal + $shippingAmount;
            $method_instance = $observer->getEvent()->getMethodInstance()->getCode();
            $result          = $observer->getEvent()->getResult();
            if ($shippingMethod == "clickandcollect_clickandcollect") {
                if (in_array($method_instance, $paymentOptionArray)) {
                    $result->setData('is_available', true);
                } else {
                    $result->setData('is_available', false);
                }
            } else {
                if ($method_instance == "ls_payment_method_pay_at_store") {
                    $result->setData('is_available', false);
                }
            }
            if ($orderTotal <= 0) {
                if ($method_instance == 'free') {
                    $quote->setBaseGrandTotal(0);
                    $quote->setGrandTotal(0);
                    $quote->getShippingAddress()->setTaxAmount(0);
                    $quote->getShippingAddress()->setBaseTaxAmount(0);
                    $this->quoteResourceModel->save($quote);
                    $result->setData('is_available', true);
                } else {
                    $result->setData('is_available', false);
                }
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
