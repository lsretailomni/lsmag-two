<?php

namespace Ls\Customer\Controller\Order;

use \Ls\Omni\Exception\InvalidEnumException;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;

/**
 * Controller being used for customer order invoice print
 */
class PrintInvoice extends AbstractOrderController implements HttpGetActionInterface
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
        $this->setInvoiceId();
        $this->orderHelper->registerGivenValueInRegistry('current_invoice_option', false);

        return $this->resultPageFactory->create();
    }

    /**
     * Check if order has invoices
     */
    public function setInvoiceId()
    {
        $order = $this->orderHelper->getGivenValueFromRegistry('current_mag_order');

        if ($order->hasInvoices()) {
            $this->orderHelper->registerGivenValueInRegistry('current_invoice_id', true);
        }
    }
}
