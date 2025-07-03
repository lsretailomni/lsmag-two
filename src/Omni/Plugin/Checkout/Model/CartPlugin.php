<?php
declare(strict_types=1);

namespace Ls\Omni\Plugin\Checkout\Model;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\StockHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interceptor for validating quantity
 */
class CartPlugin
{
    /**
     * @param StockHelper $stockHelper
     * @param LSR $lsr
     */
    public function __construct(
        public StockHelper $stockHelper,
        public LSR $lsr
    ) {
    }

    /**
     * For validating quantities
     *
     * @param $subject
     * @param $result
     * @param $data
     * @return $this
     * @throws LocalizedException
     * @throws NoSuchEntityException|GuzzleException
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
