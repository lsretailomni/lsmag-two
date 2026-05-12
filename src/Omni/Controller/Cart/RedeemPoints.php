<?php
declare(strict_types=1);

namespace Ls\Omni\Controller\Cart;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RedeemPoints extends \Magento\Checkout\Controller\Cart
{
    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param CheckoutSession $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param Cart $cart
     * @param CartRepositoryInterface $quoteRepository
     * @param LoyaltyHelper $loyaltyHelper
     * @param BasketHelper $basketHelper
     * @param Data $data
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        CheckoutSession $checkoutSession,
        StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        Cart $cart,
        public CartRepositoryInterface $quoteRepository,
        public LoyaltyHelper $loyaltyHelper,
        public BasketHelper $basketHelper,
        public Data $data
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
    }

    /**
     * Add or remove loyalty points from cart page
     *
     * @return Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @throws GuzzleException
     */
    public function execute()
    {
        $loyaltyPoints = $this->getRequest()->getParam('remove-points') == 1 ? 0
            : trim($this->getRequest()->getParam('loyalty_points'));

        if (!is_numeric($loyaltyPoints) || $loyaltyPoints < 0) {
            $this->messageManager->addErrorMessage(__('The loyalty points "%1" are not valid.', $loyaltyPoints));
            return $this->_goBack();
        }

        $loyaltyPoints = (float)$loyaltyPoints;
        try {
            $cartQuote = $this->cart->getQuote();
            $itemsCount = $cartQuote->getItemsCount();
            $isPointValid = $this->loyaltyHelper->isPointsAreValid($loyaltyPoints);
            $orderBalance = $this->data->getOrderBalance(
                $cartQuote->getLsGiftCardAmountUsed(),
                0,
                $this->basketHelper->getBasketSessionValue()
            );
            $isPointsLimitValid = $this->loyaltyHelper->isPointsLimitValid(
                $orderBalance,
                $loyaltyPoints
            );
            if ($itemsCount && $isPointValid && $isPointsLimitValid) {
                $cartQuote->getShippingAddress()->setCollectShippingRates(true);
                $cartQuote->setLsPointsSpent($loyaltyPoints)->collectTotals();
                $this->quoteRepository->save($cartQuote);
                $this->_checkoutSession->_resetState();
            }
            if ($loyaltyPoints) {
                if ($itemsCount) {
                    if ($isPointValid && $isPointsLimitValid) {
                        $this->_checkoutSession->getQuote()->setLsPointsSpent($loyaltyPoints)->save();
                        $this->messageManager->addSuccessMessage(
                            __(
                                'You have redeemed "%1" loyalty points.',
                                $loyaltyPoints
                            )
                        );
                    } else {
                        if (!$isPointsLimitValid) {
                            $this->messageManager->addErrorMessage(
                                __('The loyalty points "%1" are exceeding order total amount.', $loyaltyPoints)
                            );
                        } else {
                            $this->messageManager->addErrorMessage(
                                __(
                                    'The loyalty points "%1" are not valid.',
                                    $loyaltyPoints
                                )
                            );
                        }
                    }
                } else {
                    $this->messageManager->addErrorMessage(
                        __(
                            "The loyalty points can't be redeemed."
                        )
                    );
                }
            } else {
                $this->messageManager->addSuccessMessage(__('You have successfully canceled the points redemption.'));
            }
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__('We cannot redeem the points.'));
        }
        return $this->_goBack();
    }
}
