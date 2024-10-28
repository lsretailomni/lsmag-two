<?php

namespace Ls\Omni\Plugin\Checkout\Model;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Model\Factory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Calculation;

/**
 * For fixing klarna items total in the api
 */
class KlarnaItemsPlugin
{
    /**
     * @var object
     */
    private $dataConverter;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Calculation
     */
    private $taxCalculation;

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
     * @param Calculation $taxCalculation
     * @param Factory $factory
     * @param LSR $lsr
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Calculation $taxCalculation,
        Factory $factory,
        LSR $lsr
    ) {
        $this->storeManager   = $storeManager;
        $this->taxCalculation = $taxCalculation;
        $this->factory        = $factory;
        $this->lsr            = $lsr;
    }

    /**
     * For modifying item amount in request
     *
     * @param \Klarna\Core\Model\Checkout\Orderline\Items $subject
     * @param callable $proceed
     * @param \Klarna\Core\Api\BuilderInterface $checkout
     * @return void
     * @throws NoSuchEntityException
     */
    public function aroundFetch(
        $subject,
        callable $proceed,
        $checkout
    ) {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $object             = $checkout->getObject();
            $items              = $itemsArray = [];
            $this->klarnaConfig = $this->factory->create(
                'Klarna_Core',
                \Klarna\Core\Helper\KlarnaConfig::class
            );

            $this->dataConverter = $this->factory->create(
                'Klarna_Core',
                \Klarna\Core\Helper\DataConverter::class
            );
            foreach ($object->getAllVisibleItems() as $singleItem) {
                if ($checkout->getRequest()) {
                    $data = $checkout->getRequest()->toArray();
                }
                if ($this->klarnaConfig->isSeparateTaxLine($singleItem->getStore())) {
                    $items[$singleItem->getSku()] ['unit_price'] = $singleItem->getRowTotal();
                } else {
                    $items[$singleItem->getSku()] ['unit_price'] = $singleItem->getPrice();
                }
                $items[$singleItem->getSku()] ['row_total'] = $singleItem->getRowTotal();
                if (!$this->klarnaConfig->isSeparateTaxLine($singleItem->getStore())) {
                    $product                                   = $singleItem->getProduct();
                    $items[$singleItem->getSku()] ['tax_rate'] = $this->getTaxPercentage(
                        $product,
                        $data['purchase_country']
                    );
                }
            }
            $checkout->setTaxUnitPrice(0);
            if ($checkout->getItems()) {
                foreach ($checkout->getItems() as $item) {
                    if (!empty($item['total_tax_amount'])) {
                        $item['tax_rate'] = $this->dataConverter->toApiFloat($items[$item['reference']]['tax_rate']);
                    }
                    if (array_key_exists('reference', $item)) {
                        if ($this->klarnaConfig->isSeparateTaxLine($singleItem->getStore())) {
                            $item['unit_price'] = $this->dataConverter->toApiFloat(
                                $items[$item['reference']]['unit_price']
                            );
                        } else {
                            $item['unit_price'] = $this->dataConverter->toApiFloat(
                                $items[$item['reference']]['row_total']
                            );
                        }
                        $item['total_amount'] = $this->dataConverter->toApiFloat(
                            $items[$item['reference']]['row_total']
                        );
                    }
                    $itemsArray[] = $item;
                }
                if (!empty($itemsArray)) {
                    $checkout->setItems($itemsArray);
                }
            }
        }
        return $proceed($checkout);
    }

    /**
     * Get tax percentage
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string $country
     * @return float|int
     * @throws NoSuchEntityException
     */
    public function getTaxPercentage($product, $country)
    {
        $productTaxClassId = $product->getTaxClassId();
        $store             = $this->storeManager->getStore();
        $request           = $this->taxCalculation->getRateRequest(null, null, null, $store);
        $request->setCountryId($country);
        $request->setProductClassId($productTaxClassId);
        return $this->taxCalculation->getRate($request);
    }
}
