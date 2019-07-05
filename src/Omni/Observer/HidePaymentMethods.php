<?php

namespace Ls\Omni\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use \Ls\Core\Model\LSR;

/**
 * Class HidePaymentMethods
 * @package Ls\Omni\Observer
 */
class HidePaymentMethods implements ObserverInterface
{

    /**
     * @var  BasketHelper
     */
    private $basketHelper;

    /**
     * @var Data
     */
    private $data;

    /**
     * @var quoteResourceModel
     */
    private $quoteResourceModel;

    /**
     * @var  \Ls\Core\Model\LSR
     */
    private $lsr;

    /**
     * HidePaymentMethods constructor.
     * @param BasketHelper $basketHelper
     * @param Data $data
     * @param LoggerInterface $logger
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResourceModel
     * @param LSR $lsr
     */
    public function __construct(
        BasketHelper $basketHelper,
        Data $data,
        LoggerInterface $logger,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResourceModel,
        LSR $lsr
    ) {
        $this->basketHelper = $basketHelper;
        $this->quoteResourceModel = $quoteResourceModel;
        $this->lsr = $lsr;
        $this->data = $data;
        $this->_logger = $logger;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            $basketData = $this->basketHelper->getBasketSessionValue();
            $quote = $this->basketHelper->checkoutSession->getQuote();
            $shippingAmount = $quote->getShippingAddress()->getShippingAmount();
            $shippingMethod = $quote->getShippingAddress()->getShippingMethod();
            $paymentOption = $this->lsr->getStoreConfig(LSR::SC_PAYMENT_OPTION);
            if (!empty($basketData)) {
                $orderTotal = $this->data->getOrderBalance(
                    $quote->getLsGiftCardAmountUsed(),
                    $quote->getLsPointsSpent(),
                    $basketData
                );
                $orderTotal = $orderTotal + $shippingAmount;
                $method_instance = $observer->getEvent()->getMethodInstance()->getCode();
                $result = $observer->getEvent()->getResult();
                if ($shippingMethod == "clickandcollect_clickandcollect") {
                    if ($method_instance == "ls_payment_method_pay_at_store") {
                        $result->setData('is_available', true);
                    } else {
                        if ($paymentOption == 1) {
                            $result->setData('is_available', true);
                        } else {
                            $result->setData('is_available', false);
                        }
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
            }
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }
}