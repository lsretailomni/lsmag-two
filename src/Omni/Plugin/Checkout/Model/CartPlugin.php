<?php

namespace Ls\Omni\Plugin\Checkout\Model;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\StockHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interceptor for validating quantity
 */
class CartPlugin
{

    /** @var StockHelper */
    private $stockHelper;

    /**
     * @var LSR
     */
    private $lsr;

    /**
     * CartPlugin constructor.
     * @param StockHelper $stockHelper
     * @param LSR $LSR
     */
    public function __construct(
        StockHelper $stockHelper,
        LSR $LSR
    ) {
        $this->stockHelper     = $stockHelper;
        $this->lsr             = $LSR;
    }

    /**
     * For validating quantities
     *
     * @param $subject
     * @param $result
     * @param $data
     * @return $this
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterUpdateItems($subject, $result, $data)
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            foreach ($data as $itemId => $itemInfo) {
                $item = $subject->getQuote()->getItemById($itemId);
                if (!$item->getHasError()) {
                    if ($item) {
                        try {
                            $this->stockHelper->validateQty(
                                $itemInfo['qty'],
                                $item,
                                null,
                                false,
                                true
                            );
                        } catch (LocalizedException $e) {
                            throw new LocalizedException(__($e->getMessage()));

                        }
                    }
                }
            }
        }

        return $result;
    }
}
