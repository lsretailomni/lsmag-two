<?php

namespace Ls\Omni\Plugin\AdminOrder;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\OneList;
use \Ls\Omni\Client\Ecommerce\Entity\Order;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\ItemHelper;
use Magento\Sales\Model\AdminOrder\Create;
use Psr\Log\LoggerInterface;

/**
 * Interceptor to create oneList while creating order from admin
 */
class CreatePlugin
{
    /**
     * @var BasketHelper
     */
    private $basketHelper;

    /**
     * @var ItemHelper
     */
    private $itemHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var LSR
     */
    private $lsr;

    /**
     * @var Data
     */
    private $data;

    /**
     * @param BasketHelper $basketHelper
     * @param ItemHelper $itemHelper
     * @param LoggerInterface $logger
     * @param LSR $LSR
     * @param Data $data
     */
    public function __construct(
        BasketHelper $basketHelper,
        ItemHelper $itemHelper,
        LoggerInterface $logger,
        LSR $LSR,
        Data $data
    ) {
        $this->basketHelper = $basketHelper;
        $this->itemHelper   = $itemHelper;
        $this->logger       = $logger;
        $this->lsr          = $LSR;
        $this->data         = $data;
    }

    /**
     * After plugin to create oneList after quote is saved
     *
     * @param Create $subject
     * @param $result
     * @return mixed
     */
    public function afterSaveQuote(
        Create $subject,
        $result
    ) {
        if (!$subject->getQuote()->getId() || empty($subject->getQuote()->getAllVisibleItems())) {
            return $result;
        }

        $quote = $subject->getQuote();
        try {
            if ($this->lsr->isLSR($quote->getStoreId())) {
                $couponCode = $quote->getCouponCode();
                // This will create one list if not created and will return onelist if its already created.
                /** @var OneList|null $oneList */
                $oneList = $this->basketHelper->getOneListFromCustomerSession();

                if (!$oneList || !$oneList->getCardId() || $quote->getLsOneListId() != $oneList->getId()) {
                    $oneList = $this->basketHelper->getOneListAdmin(
                        $quote->getCustomerEmail(),
                        $quote->getStore()->getWebsiteId(),
                        false
                    );
                    $quote->setLsOneListId($oneList->getId());
                    $this->basketHelper->quoteRepository->save($quote);
                }

                $oneList = $this->basketHelper->setOneListQuote($quote, $oneList);
                if (!empty($couponCode)) {
                    $status = $this->basketHelper->setCouponCode($couponCode);
                    if (!is_object($status)) {
                        $quote->setCouponCode('');
                    }
                }
                if (count($quote->getAllItems()) == 0) {
                    $quote->setLsGiftCardAmountUsed(0);
                    $quote->setLsGiftCardNo(null);
                    $quote->setLsPointsSpent(0);
                    $quote->setLsPointsEarn(0);
                    $quote->setGrandTotal(0);
                    $quote->setBaseGrandTotal(0);
                    $this->basketHelper->quoteRepository->save($quote);
                }
                /** @var Order $basketData */
                $basketData = $this->basketHelper->update($oneList);
                $this->itemHelper->setDiscountedPricesForItems($quote, $basketData, 2);
                if (!empty($basketData)) {
                    $quote->setLsPointsEarn($basketData->getPointsRewarded())->save();
                }
                if ($quote->getLsGiftCardAmountUsed() > 0 ||
                    $quote->getLsPointsSpent() > 0) {
                    $this->data->orderBalanceCheck(
                        $quote->getLsGiftCardNo(),
                        $quote->getLsGiftCardAmountUsed(),
                        $quote->getLsPointsSpent(),
                        $basketData
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }
}
