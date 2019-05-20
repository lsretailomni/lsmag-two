<?php

namespace Ls\Omni\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;

/**
 * Class HidePaymentMethods
 * @package Evermore\Payment\Observer
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
     * HidePaymentMethods constructor.
     * @param BasketHelper $basketHelper
     * @param Data $data
     * @param LoggerInterface $logger
     */
    public function __construct(
        BasketHelper $basketHelper,
        Data $data,
        LoggerInterface $logger,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResourceModel
    )
    {
        $this->basketHelper = $basketHelper;
        $this->quoteResourceModel = $quoteResourceModel;
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
            if (!empty($basketData)) {
                $orderTotal = $this->data->getOrderBalance(
                    $quote->getLsGiftCardAmountUsed(),
                    $quote->getLsPointsSpent(),
                    $basketData
                );
                $orderTotal = $orderTotal + $shippingAmount;
                if ($orderTotal <= 0) {
                    $method_instance = $observer->getEvent()->getMethodInstance()->getCode();
                    $result = $observer->getEvent()->getResult();
                    if ($method_instance == 'free') {
                        $quote->setBaseGrandTotal(0);
                        $quote->setGrandTotal(0);
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