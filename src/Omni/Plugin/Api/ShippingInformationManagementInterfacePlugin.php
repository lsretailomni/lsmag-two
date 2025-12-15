<?php

namespace Ls\Omni\Plugin\Api;

use Ls\Core\Model\LSR;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * Interceptor to intercept ShippingInformationManagementInterface methods
 */
class ShippingInformationManagementInterfacePlugin
{
    /**
     * @var BasketHelper
     */
    private $basketHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param BasketHelper $basketHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        BasketHelper $basketHelper,
        LoggerInterface $logger,
    ) {
        $this->basketHelper = $basketHelper;
        $this->logger       = $logger;
    }

    /**
     * After plugin to sync basket with Central for both logged in/guest user
     *
     * @param $subject
     * @param $result
     * @param $cartId
     * @param ShippingInformationInterface $addressInformation
     * @return mixed
     * @throws InvalidEnumException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterSaveAddressInformation(
        $subject,
        $result,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        if (!is_numeric($cartId)) {
            return $result;
        }
        
        $basketData = $this->basketHelper->syncBasketWithCentral($cartId);
        if (!$this->basketHelper->verifyBasketySync($basketData)) {
            $lsr    = $this->basketHelper->getLsrModel();
            $errMsg = $lsr->getStoreConfig(LSR::LS_ERROR_MESSAGE_ON_BASKET_FAIL);
            $this->logger->critical($errMsg);
            throw new InputException(
                __($errMsg)
            );
        }
        
        return $result;
    }
}
