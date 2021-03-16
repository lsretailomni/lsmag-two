<?php

namespace Ls\Omni\Model\Creditmemo\Total;

use \Ls\Omni\Helper\Data as Helper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

/**
 * Class for handling gift card and loyalty points in credit memo
 */
class GiftCardLoyaltyPoints extends AbstractTotal
{

    /**
     * @var Helpercd
     */
    private $helper;

    /**
     * GiftCardLoyaltyPoints constructor.
     * @param Helper $helper
     * @param array $data
     */
    public function __construct(
        Helper $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct(
            $data
        );
    }

    /**
     * Calculation for loyalty points and gift card amount in credt memo.
     * @param Creditmemo $creditMemo
     * @return $this|GiftCardLoyaltyPoints
     * @throws NoSuchEntityException
     */
    public function collect(Creditmemo $creditMemo)
    {
        $this->helper->calculateInvoiceCreditMemoTotal($creditMemo);
        return $this;
    }
}
