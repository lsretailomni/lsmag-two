<?php
declare(strict_types=1);

namespace Ls\Omni\Model\Invoice\Total;

use \Ls\Omni\Helper\Data as Helper;
use Magento\Sales\Model\Order\Invoice;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

/**
 * Class for handling gift card and loyalty points invoice
 */
class GiftCardLoyaltyPoints extends AbstractTotal
{
    /**
     * @param Helper $helper
     * @param array $data
     */
    public function __construct(
        public Helper $helper,
        array $data = []
    ) {
        parent::__construct(
            $data
        );
    }

    /**
     * Calculation for loyalty points and gift card amount in invoice.
     * @param Invoice $invoice
     * @return $this|AbstractTotal
     * @throws NoSuchEntityException
     */
    public function collect(Invoice $invoice)
    {
        $this->helper->calculateInvoiceCreditMemoTotal($invoice);
        return $this;
    }
}
