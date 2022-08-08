<?php

namespace Ls\Omni\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use Magento\Checkout\Model\Session\Proxy;
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
     * @var Proxy
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
     * @param Proxy $checkoutSession
     * @param Filesystem $filesystem
     * @param LSR $Lsr
     */
    public function __construct(
        Context $context,
        Proxy $checkoutSession,
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
     * @param $giftCardNo
     * @return float|Entity\GiftCard|null
     */
    public function getGiftCardBalance($giftCardNo)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\GiftCardGetBalance();
        $entity  = new Entity\GiftCardGetBalance();
        $entity->setCardNo($giftCardNo);
        // @codingStandardsIgnoreEnd
        try {
            $responseData = $request->execute($entity);
            $response     = $responseData ? $responseData->getResult() : $response;
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        $this->checkoutSession->setGiftCard($response);
        return $response;
    }

    /**
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
     * @param $area
     * @return string
     * @throws NoSuchEntityException
     */
    public function isGiftCardEnabled($area)
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            if ($area == 'cart') {
                return ( $this->lsr->getStoreConfig(
                    LSR::SC_ENABLE_GIFTCARD_ELEMENTS,
                    $this->lsr->getCurrentStoreId()
                ) && $this->lsr->getStoreConfig(
                    LSR::LS_GIFTCARD_SHOW_ON_CART,
                    $this->lsr->getCurrentStoreId()
                )
                );
            }
            return ( $this->lsr->getStoreConfig(
                LSR::SC_ENABLE_GIFTCARD_ELEMENTS,
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
}
