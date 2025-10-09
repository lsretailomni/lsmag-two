<?php
declare(strict_types=1);

namespace Ls\Omni\Plugin\AdminOrder;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\OneList;
use \Ls\Omni\Client\Ecommerce\Entity\Order;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Sales\Model\AdminOrder\Create;
use Psr\Log\LoggerInterface;

/**
 * Interceptor to create oneList while creating order from admin
 */
class CreatePlugin
{
    /**
     * @param BasketHelper $basketHelper
     * @param ItemHelper $itemHelper
     * @param LoggerInterface $logger
     * @param LSR $lsr
     * @param Data $data
     * @param OrderHelper $orderHelper
     * @param Quote $backendQuoteSession
     */
    public function __construct(
        public BasketHelper $basketHelper,
        public ItemHelper $itemHelper,
        public LoggerInterface $logger,
        public LSR $lsr,
        public Data $data,
        public OrderHelper $orderHelper,
        public Quote $backendQuoteSession
    ) {
    }

    /**
     * After plugin to create oneList after quote is saved
     *
     * @param Create $subject
     * @param $result
     * @return mixed
     * @throws GuzzleException|AlreadyExistsException
     */
    public function afterSaveQuote(
        Create $subject,
        $result
    ) {
        if (!$subject->getQuote()->getId() || empty($subject->getQuote()->getAllVisibleItems())) {
            return $result;
        }

        $quote = $subject->getQuote();
        $this->orderHelper->storeManager->setCurrentStore($quote->getStoreId());
        $this->basketHelper->setCorrectStoreIdInCheckoutSession($quote->getStoreId());
        if (!empty($quote)) {
            $this->basketHelper->getCheckoutSession()->setQuoteId($quote->getId());
            $quote->setIsActive(1);
            $this->itemHelper->quoteResourceModel->save($quote);
            $this->basketHelper->getCustomerSession()->setCustomerId($quote->getCustomer()->getId());
        }

        try {
            if ($this->lsr->isLSR($quote->getStoreId())) {
                $couponCode = $quote->getCouponCode();
                $webStore = $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_STORE, $quote->getStore()->getWebsiteId());
                $this->basketHelper->storeId = $webStore;
                /** @var OneList|null $oneList */
                $oneList = $this->basketHelper->getOneListAdmin(
                    $quote->getCustomerEmail(),
                    $quote->getStore()->getWebsiteId(),
                    false
                );

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
                    $quote->setLsGiftCardPin(null);
                    $quote->setLsPointsSpent(0);
                    $quote->setLsPointsEarn(0);
                    $quote->setGrandTotal(0);
                    $quote->setBaseGrandTotal(0);
                    $this->basketHelper->quoteRepository->save($quote);
                }
                /** @var Order $basketData */
                $basketData = $this->basketHelper->update($oneList);
                $quote = $this->basketHelper->getCurrentQuote();
                $this->itemHelper->setDiscountedPricesForItems($quote, $basketData, 2);
                $this->backendQuoteSession->_resetState();
                if (!empty($basketData) && method_exists($basketData, 'getPointsRewarded')) {
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
