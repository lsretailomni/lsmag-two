<?php

namespace Ls\Customer\Controller\Order;

use \Ls\Omni\Exception\InvalidEnumException;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

/**
 * Controller being used for customer order invoice
 */
class Invoice extends AbstractOrderController implements HttpGetActionInterface
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
        $this->orderHelper->registerGivenValueInRegistry('current_detail', 'invoice');
        $this->setInvoiceId();
        $this->setPrintInvoiceOption();

        return $this->resultPageFactory->create();
    }

    /**
     * Check if order has invoices
     */
    public function setInvoiceId()
    {
        $order = $this->orderHelper->getGivenValueFromRegistry('current_mag_order');

        if (!empty($order) && $order->hasInvoices()) {
            $this->orderHelper->registerGivenValueInRegistry('current_invoice_id', true);
        }
    }

    /**
     *  Print Invoice Option
     */
    public function setPrintInvoiceOption()
    {
        $order = $this->orderHelper->getGivenValueFromRegistry('current_mag_order');
        if (!empty($order)) {
            if (!empty($order->getInvoiceCollection())) {
                $this->orderHelper->registerGivenValueInRegistry('current_invoice_option', true);
            } else {
                $this->orderHelper->registerGivenValueInRegistry('current_invoice_option', false);
            }
        }
    }
}
