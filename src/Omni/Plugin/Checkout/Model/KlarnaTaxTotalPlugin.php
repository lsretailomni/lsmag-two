<?php

namespace Ls\Omni\Plugin\Checkout\Model;

use Klarna\Core\Exception;
use Klarna\Core\Helper\KlarnaConfig;
use Klarna\Kp\Model\Api\Request\Builder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * For fixing klarna tax total in the api
 */
class KlarnaTaxTotalPlugin
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var KlarnaConfig
     */
    private $klarnaConfig;

    /**
     * @param StoreManagerInterface $storeManager
     * @param KlarnaConfig $klarnaConfig
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        KlarnaConfig $klarnaConfig
    ) {
        $this->storeManager   = $storeManager;
        $this->klarnaConfig   = $klarnaConfig;
    }

    /**
     * For setting order tax amount to zero in case of separate line
     *
     * @param Builder $subject
     * @param $amount
     * @return int|void
     * @throws Exception
     * @throws NoSuchEntityException
     */
    public function beforeSetOrderTaxAmount(
        Builder $subject,
        $amount
    ) {
        if ($this->klarnaConfig->isSeparateTaxLine($this->storeManager->getStore())) {
            return 0;
        }
    }
}
