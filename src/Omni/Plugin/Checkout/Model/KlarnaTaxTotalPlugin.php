<?php

namespace Ls\Omni\Plugin\Checkout\Model;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Model\Factory;
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
     * @var object
     */
    private $klarnaConfig;

    /**
     * @var LSR
     */
    private $lsr;

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Factory $factory
     * @param LSR $lsr
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Factory $factory,
        LSR $lsr
    ) {
        $this->storeManager = $storeManager;
        $this->factory      = $factory;
        $this->lsr          = $lsr;
    }

    /**
     * For setting order tax amount to zero in case of separate line
     *
     * @param $subject
     * @param $amount
     * @return int|void
     * @throws NoSuchEntityException
     */
    public function beforeSetOrderTaxAmount(
        $subject,
        $amount
    ) {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $this->klarnaConfig = $this->factory->create(
                'Klarna_Core',
                'Klarna\Core\Helper\KlarnaConfig'
            );
            if ($this->klarnaConfig->isSeparateTaxLine($this->storeManager->getStore())) {
                return 0;
            }
        }
    }
}
