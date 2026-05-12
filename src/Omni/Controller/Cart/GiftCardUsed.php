<?php
declare(strict_types=1);

namespace Ls\Omni\Controller\Cart;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\GiftCardHelper;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class GiftCardUsed extends \Magento\Checkout\Controller\Cart
{
    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param CheckoutSession $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param Cart $cart
     * @param CartRepositoryInterface $quoteRepository
     * @param GiftCardHelper $giftCardHelper
     * @param BasketHelper $basketHelper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param Data $data
     * @param LSR $lsr
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        CheckoutSession $checkoutSession,
        StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        Cart $cart,
        public CartRepositoryInterface $quoteRepository,
        public GiftCardHelper $giftCardHelper,
        public BasketHelper $basketHelper,
        public \Magento\Framework\Pricing\Helper\Data $priceHelper,
        public Data $data,
        public LSR $lsr
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
     * Add and remove gift card from cart page
     *
     * @return Redirect
     * @throws GuzzleException
     */
    public function execute()
    {
        $giftCardNo = $this->getRequest()->getParam('giftcardno');
        $giftCardPin = $this->getRequest()->getParam('giftcardpin');
        $giftCardBalanceAmount = 0;
        $giftCardAmount = $this->getRequest()->getParam('removegiftcard') == 1
            ? 0
            : trim($this->getRequest()->getParam('giftcardamount'));

        $giftCardAmount = (float)$giftCardAmount;
        try {
            if (!is_numeric($giftCardAmount) || $giftCardAmount < 0) {
                $this->messageManager->addErrorMessage(
                    __(
                        'The gift card Amount "%1" is not valid.',
                        $this->priceHelper->currency($giftCardAmount, true, false)
                    )
                );
                return $this->_goBack();
            }
            if ($giftCardNo != null) {
                $giftCardResponse = $this->giftCardHelper->getGiftCardBalance($giftCardNo, $giftCardPin);
                if (is_object($giftCardResponse)) {
                    $convertedGiftCardBalanceArr = $this->giftCardHelper->getConvertedGiftCardBalance(
                        $giftCardResponse
                    );
                    $giftCardBalanceAmount = $convertedGiftCardBalanceArr['gift_card_balance_amount'];
                    $quotePointRate = $convertedGiftCardBalanceArr['quote_point_rate'];
                    $giftCardCurrencyCode = $convertedGiftCardBalanceArr['gift_card_currency'];
                } else {
                    $giftCardBalanceAmount = $giftCardResponse;
                }
            }

            if (empty($giftCardResponse)) {
                $this->messageManager->addErrorMessage(__('The gift card is not valid.'));
                return $this->_goBack();
            }

            if ($this->giftCardHelper->isGiftCardExpired($giftCardResponse) && $giftCardAmount) {
                $this->messageManager->addErrorMessage(
                    __('Unfortunately, we can\'t apply this gift card since its already expired.')
                );
                return $this->_goBack();
            }

            $cartQuote = $this->cart->getQuote();
            $itemsCount = $cartQuote->getItemsCount();
            $orderBalance = $this->data->getOrderBalance(
                0,
                $cartQuote->getLsPointsSpent(),
                $this->basketHelper->getBasketSessionValue()
            );

            $isGiftCardAmountValid = $this->giftCardHelper->isGiftCardAmountValid(
                $orderBalance,
                $giftCardAmount,
                $giftCardBalanceAmount
            );

            if ($isGiftCardAmountValid === false) {
                $this->messageManager->addErrorMessage(
                    __(
                        'The applied amount %3' .
                        ' is greater than gift card balance amount (%1) or it is greater than order balance (%2).',
                        $this->priceHelper->currency(
                            $giftCardBalanceAmount,
                            true,
                            false
                        ),
                        $this->priceHelper->currency(
                            $orderBalance,
                            true,
                            false
                        ),
                        $this->priceHelper->currency(
                            $giftCardAmount,
                            true,
                            false
                        )
                    )
                );
                return $this->_goBack();
            }
            if ($itemsCount && !empty($giftCardResponse) && $isGiftCardAmountValid) {
                $cartQuote->getShippingAddress()->setCollectShippingRates(true);
                $cartQuote->setLsGiftCardAmountUsed($giftCardAmount)
                    ->setLsGiftCardNo($giftCardNo)
                    ->setLsGiftCardPin($giftCardPin)
                    ->setLsGiftCardCnyFactor($quotePointRate)
                    ->setLsGiftCardCnyCode($giftCardCurrencyCode)
                    ->collectTotals();
                $this->quoteRepository->save($cartQuote);
            }
            if ($giftCardAmount) {
                if ($itemsCount) {
                    if (!empty($giftCardResponse) && $isGiftCardAmountValid) {
                        $this->_checkoutSession->getQuote()->setLsGiftCardAmountUsed($giftCardAmount)->save();
                        $this->_checkoutSession->getQuote()->setLsGiftCardNo($giftCardNo)->save();
                        $this->messageManager->addSuccessMessage(
                            __(
                                'You have used "%1" amount from gift card.',
                                $this->priceHelper->currency($giftCardAmount, true, false)
                            )
                        );
                    } else {
                        $this->messageManager->addErrorMessage(
                            __(
                                'The gift card amount "%1" is not valid.',
                                $this->getBaseCurrencyCode() . $giftCardAmount
                            )
                        );
                    }
                } else {
                    $this->messageManager->addErrorMessage(
                        __(
                            "Gift Card cannot be applied."
                        )
                    );
                }
            } else {
                if ($giftCardAmount == 0) {
                    $this->_checkoutSession->getQuote()
                        ->setLsGiftCardNo(null)
                        ->setLsGiftCardPin(null)
                        ->setLsGiftCardCnyFactor(null)
                        ->setLsGiftCardCnyCode(null)
                        ->setLsGiftCardAmountUsed($giftCardAmount)
                        ->save();
                }
                $this->messageManager->addSuccessMessage(__('You have successfully cancelled the gift card.'));
            }
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__('Gift Card cannot be applied.'));
        }

        return $this->_goBack();
    }

    /**
     * Get base currency code
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getBaseCurrencyCode()
    {
        return $this->_checkoutSession->getQuote()->getBaseCurrencyCode();
    }
}
