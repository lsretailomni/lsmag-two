<?php
declare(strict_types=1);

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
     * @param StoreManagerInterface $storeManager
     * @param Factory $factory
     * @param LSR $lsr
     */
    public function __construct(
        public StoreManagerInterface $storeManager,
        public Factory $factory,
        public LSR $lsr
    ) {
    }

    /**
     * For setting order tax amount to zero in case of separate line
     *
     * @param \Klarna\Kp\Model\Api\Request\Builder $subject
     * @param int $amount
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
                \Klarna\Core\Helper\KlarnaConfig::class
            );
            if ($this->klarnaConfig->isSeparateTaxLine($this->storeManager->getStore())) {
                return 0;
            }
        }
    }
}
