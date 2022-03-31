<?php

namespace Ls\Omni\Plugin\Checkout\Model;

use \Ls\Core\Model\LSR;
use Klarna\Core\Api\BuilderInterface;
use Klarna\Core\Helper\KlarnaConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Calculation;
use Klarna\Core\Helper\DataConverter;
use Klarna\Core\Model\Checkout\Orderline\Items;

/**
 * For fixing klarna items total in the api
 */
class KlarnaItemsPlugin
{
    /**
     * @var DataConverter
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
     * @var KlarnaConfig
     */
    private $klarnaConfig;

    /**
     * @var LSR
     */
    private $lsr;

    /**
     * @param DataConverter $dataConverter
     * @param StoreManagerInterface $storeManager
     * @param Calculation $taxCalculation
     * @param KlarnaConfig $klarnaConfig
     * @param LSR $lsr
     */
    public function __construct(
        DataConverter $dataConverter,
        StoreManagerInterface $storeManager,
        Calculation $taxCalculation,
        KlarnaConfig $klarnaConfig,
        LSR $lsr
    ) {
        $this->dataConverter  = $dataConverter;
        $this->storeManager   = $storeManager;
        $this->taxCalculation = $taxCalculation;
        $this->klarnaConfig   = $klarnaConfig;
        $this->lsr            = $lsr;
    }

    /**
     * For modifying item amount in request
     *
     * @param Items $subject
     * @param callable $proceed
     * @param BuilderInterface $checkout
     * @return void
     * @throws NoSuchEntityException
     */
    public function aroundFetch(
        Items $subject,
        callable $proceed,
        BuilderInterface $checkout
    ) {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $object = $checkout->getObject();
            $items  = $itemsArray = [];

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
                            $item['unit_price'] = $this->dataConverter->toApiFloat($items[$item['reference']]['unit_price']);
                        } else {
                            $item['unit_price'] = $this->dataConverter->toApiFloat($items[$item['reference']]['row_total']);
                        }
                        $item['total_amount'] = $this->dataConverter->toApiFloat($items[$item['reference']] ['row_total']);
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
     * get tax percentage
     *
     * @param $product
     * @param $country
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
