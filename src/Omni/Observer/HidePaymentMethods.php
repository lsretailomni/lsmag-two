<?php

namespace Ls\Omni\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

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
     * @var \Magento\Quote\Model\ResourceModel\Quote
     */
    private $quoteResourceModel;

    /**
     * @var  \Ls\Core\Model\LSR
     */
    private $lsr;
    /*
     * @var LoggerInterface
     */
    private $logger;

    /**
     * HidePaymentMethods constructor.
     * @param BasketHelper $basketHelper
     * @param Data $data
     * @param LoggerInterface $logger
     * @param LSR $lsr
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResourceModel
     */
    public function __construct(
        BasketHelper $basketHelper,
        Data $data,
        LoggerInterface $logger,
        LSR $lsr,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResourceModel

    ) {
        $this->basketHelper = $basketHelper;
        $this->quoteResourceModel = $quoteResourceModel;
        $this->lsr = $lsr;
        $this->data = $data;
        $this->logger = $logger;
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
            $this->logger->error($e->getMessage());
        }
    }
}
