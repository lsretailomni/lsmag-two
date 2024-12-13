<?php

namespace Ls\Customer\Controller\Order;

use \Ls\Omni\Exception\InvalidEnumException;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;

/**
 * Controller being used for customer order print refunds
 */
class PrintRefunds extends AbstractOrderController implements HttpGetActionInterface
{
    /**
     * @inheritDoc
     *
     * @return Page|ResultInterface|void
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $result = $this->registerValuesInRegistry();

        if ($result) {
            return $result;
        }
        $this->orderHelper->registerGivenValueInRegistry('current_invoice_option', false);
        $this->orderHelper->registerGivenValueInRegistry('current_shipment_option', false);
        $this->orderHelper->registerGivenValueInRegistry('hide_shipping_links', true);

        return $this->resultPageFactory->create();
    }

    /**
     * @inheritDoc
     *
     * @param $orderId
     * @param $type
     * @return mixed
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function fetchAndSetCurrentOrderInRegistry($orderId, $type)
    {
        $transactions = parent::fetchAndSetCurrentOrderInRegistry($orderId, $type);
        $response = [];

        if (!is_array($transactions)) {
            $transactions = [$transactions];
        }

        foreach ($transactions as $transaction) {
            $returnTransactions = $this->orderHelper->getReturnDetailsAgainstId($transaction->getId());

            if (!empty($returnTransactions)) {
                // @codingStandardsIgnoreStart
                $response = array_merge($response, $returnTransactions);
                // @codingStandardsIgnoreEnd
            }
        }

        if (empty($response)) {
            $response = $transactions;
        }

        if ($response) {
            $this->orderHelper->registerGivenValueInRegistry('current_order', $response);
        }

        return $response;
    }
}
