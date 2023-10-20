<?php

namespace Ls\Omni\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\GiftCard;
use \Ls\Omni\Client\Ecommerce\Operation;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;

/**
 * Class GiftCardHelper for gift card support
 */
class GiftCardHelper extends AbstractHelper
{

    const SERVICE_TYPE = 'ecommerce';

    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    /**
     * @var Filesystem
     */
    public $filesystem;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * GiftCardHelper constructor.
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param Filesystem $filesystem
     * @param LSR $Lsr
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        Filesystem $filesystem,
        LSR $Lsr
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->filesystem      = $filesystem;
        $this->lsr             = $Lsr;
        parent::__construct(
            $context
        );
    }

    /**
     * For getting gift card balance
     *
     * @param $giftCardNo
     * @param $giftCardPin
     * @return float|GiftCard|null
     */
    public function getGiftCardBalance($giftCardNo, $giftCardPin = null)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\GiftCardGetBalance();
        $entity  = new Entity\GiftCardGetBalance();
        $entity->setCardNo($giftCardNo);
        if ($giftCardPin) {
            $entity->setPin($giftCardPin);
        }
        // @codingStandardsIgnoreEnd
        try {
            $responseData = $request->execute($entity);
            $response     = $responseData ? $responseData->getResult() : $response;
            if (!empty($response)) {
                $currency = $response->getCurrencyCode();
                if (!empty($currency)) {
                    $response = ($currency == $this->lsr->getStoreCurrencyCode()) ? $response : null;
                }
            }
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        $this->checkoutSession->setGiftCard($response);
        return $response;
    }

    /**
     * Validate gift card amount is valid with order total
     *
     * @param $grandTotal
     * @param $giftCardAmount
     * @param $giftCardBalanceAmount
     * @return bool
     */
    public function isGiftCardAmountValid($grandTotal, $giftCardAmount, $giftCardBalanceAmount)
    {
        if ($giftCardAmount <= $grandTotal && $giftCardAmount <= $giftCardBalanceAmount) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if gift card is enabled
     *
     * @param $area
     * @return string
     * @throws NoSuchEntityException
     */
    public function isGiftCardEnabled($area)
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            if ($area == 'cart') {
                return ($this->lsr->getStoreConfig(
                        LSR::LS_ENABLE_GIFTCARD_ELEMENTS,
                        $this->lsr->getCurrentStoreId()
                    ) && $this->lsr->getStoreConfig(
                        LSR::LS_GIFTCARD_SHOW_ON_CART,
                        $this->lsr->getCurrentStoreId()
                    )
                );
            }
            return ($this->lsr->getStoreConfig(
                    LSR::LS_ENABLE_GIFTCARD_ELEMENTS,
                    $this->lsr->getCurrentStoreId()
                ) && $this->lsr->getStoreConfig(
                    LSR::LS_GIFTCARD_SHOW_ON_CHECKOUT,
                    $this->lsr->getCurrentStoreId()
                )
            );
        } else {
            return false;
        }
    }

    /**
     * Check pin code field in enable or not in gift card
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function isPinCodeFieldEnable()
    {
        return $this->lsr->getStoreConfig(LSR::LS_GIFTCARD_SHOW_PIN_CODE_FIELD, $this->lsr->getCurrentStoreId());
    }
}
