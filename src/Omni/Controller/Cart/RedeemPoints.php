<?php

namespace Ls\Omni\Controller\Cart;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RedeemPoints extends \Magento\Checkout\Controller\Cart
{
    /**
     * Sales quote repository
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Ls\Omni\Helper\LoyaltyHelper $loyaltyHelper
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Ls\Omni\Helper\LoyaltyHelper $loyaltyHelper
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
        $this->quoteRepository = $quoteRepository;
        $this->loyaltyHelper = $loyaltyHelper;
    }

    /**
     * Initialize coupon
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $loyaltyPoints = $this->getRequest()->getParam('remove-points') == 1
            ? 0
            : trim($this->getRequest()->getParam('loyalty_points'));

        if (!is_numeric($loyaltyPoints) || $loyaltyPoints < 0) {
            $this->messageManager->addError(
                            __(
                                'The loyalty points "%1" are not valid.',
                                $loyaltyPoints
                            )
                        );
            return $this->_goBack();
        }

        $loyaltyPoints = (int) $loyaltyPoints;
        try {
            $cartQuote = $this->cart->getQuote();
            $itemsCount = $cartQuote->getItemsCount();
            $isPointValid = $this->isPointsAreValid($loyaltyPoints);
            $isPointsLimitValid = $this->loyaltyHelper->isPointsLimitValid($cartQuote->getBaseGrandTotal(), $loyaltyPoints);
            if ($itemsCount && $isPointValid && $isPointsLimitValid) {
                $cartQuote->getShippingAddress()->setCollectShippingRates(true);
                $cartQuote->setLsPointsSpent($loyaltyPoints)->collectTotals();
                $this->quoteRepository->save($cartQuote);
            }
            if ($loyaltyPoints) {
                if ($itemsCount) {
                    if ($isPointValid && $isPointsLimitValid) {
                        $this->_checkoutSession->getQuote()->setLsPointsSpent($loyaltyPoints)->save();
                        $this->messageManager->addSuccess(
                            __(
                                'You have redeemed "%1" loyalty points.',
                                $loyaltyPoints
                            )
                        );
                    } else {
                        $this->messageManager->addError(
                            __(
                                'The loyalty points "%1" are not valid.',
                                $loyaltyPoints
                            )
                        );
                    }
                } else {
                    $this->messageManager->addError(
                        __(
                            "The loyalty points can't be redeemed."
                        )
                    );
                }
            } else {
                $this->messageManager->addSuccess(__('You have successfully canceled the points redemption.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addError(__('We cannot redeem the points.'));
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
        }
        return $this->_goBack();
    }

    /**
     * @param $loyaltyPoints
     * @return bool
     */
    public function isPointsAreValid($loyaltyPoints)
    {
        /* \Ls\Omni\Client\Ecommerce\Entity\MemberContact $memberProfile */
        $memberProfile = $this->loyaltyHelper->getMemberInfo();
        if ($memberProfile != null) {
            $points = $memberProfile->getAccount()->getPointBalance();
            if ($points >= $loyaltyPoints) {
                return true;
            }
        }else {
            return false;
        }
    }
}
