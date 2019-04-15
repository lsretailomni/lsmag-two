<?php

namespace Ls\Omni\Helper;

use \Ls\Replication\Model\ReplBarcodeRepository;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Helper\Context;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use Magento\Framework\Api\SearchCriteriaBuilder;
use \Ls\Core\Model\LSR;

/**
 * Class ItemHelper
 * @package Ls\Omni\Helper
 */
class ItemHelper extends \Magento\Framework\App\Helper\AbstractHelper
{

    /** @var SearchCriteriaBuilder */
    public $searchCriteriaBuilder;

    /** @var ReplBarcodeRepository */
    public $barcodeRepository;

    /** @var ProductRepository */
    public $productRepository;

    /** @var array */
    private $hashCache = [];

    /**
     * ItemHelper constructor.
     * @param Context $context
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ReplBarcodeRepository $barcodeRepository
     * @param ProductRepository $productRepository
     */

    public function __construct(
        Context $context,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ReplBarcodeRepository $barcodeRepository,
        ProductRepository $productRepository
    )
    {
        parent::__construct($context);
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->barcodeRepository = $barcodeRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * @param $id
     * @param bool $lite
     * @return bool|Entity\LoyItem
     */
    public function get($id, $lite = false)
    {

        $result = false;
        // @codingStandardsIgnoreStart
        $entity = new Entity\ItemGetById();
        $entity->setItemId($id);
        $request = new Operation\ItemGetById();
        // @codingStandardsIgnoreEnd

        /** @var \Ls\Omni\Client\Ecommerce\Entity\ItemGetByIdResponse $response */
        $response = $request->execute($entity);

        if ($response && !($response->getItemGetByIdResult() == null)) {
            $item = $response->getItemGetByIdResult();
            $result = $item;
        }

        return $lite && $result
            ? $this->lite($result)
            : $result;
    }

    /**
     * @param Entity\LoyItem $item
     * @return $this
     */
    public function lite(Entity\LoyItem $item)
    {
        // @codingStandardsIgnoreStart
        return (new Entity\LoyItem)
            ->setId($item->getId())
            ->setPrice($item->getPrice())
            ->setAllowedToSell($item->getAllowedToSell());
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param Entity\LoyItem $item
     * @return Entity\UnitOfMeasure|Entity\UnitOfMeasure[]|null
     */
    public function uom(Entity\LoyItem $item)
    {
        // @codingStandardsIgnoreLine
        $uom = new Entity\UnitOfMeasure();
        $salesUomId = $item->getSalesUomId();

        $uoms = $item->getUnitOfMeasures()->getUnitOfMeasure();

        if (is_array($uoms)) {
            /** @var Entity\UnitOfMeasure $row */
            foreach ($uoms as $row) {
                if ($row->getId() == $salesUomId) {
                    $uom = $row;
                    break;
                }
            }
        } else {
            $uom = $uoms;
        }
        /** @var Entity\UnitOfMeasure $response */
        // @codingStandardsIgnoreLine
        $response = new Entity\UnitOfMeasure();
        $response->setId($uom->getId())
            ->setDecimals($uom->getDecimals())
            ->setDescription($uom->getDescription())
            ->setItemId($uom->getItemId())
            ->setPrice($uom->getPrice())
            ->setQtyPerUom($uom->getQtyPerUom())
            ->setShortDescription($uom->getShortDescription());

        return $response;
    }

    /**
     * @param Entity\LoyItem $item
     * @param null $variant_id
     * @return Entity\VariantRegistration|null
     */
    public function getItemVariant(Entity\LoyItem $item, $variant_id = null)
    {
        $variant = null;
        if (($variant_id == null)) {
            return $variant;
        }
        /** @var \Ls\Omni\Client\Ecommerce\Entity\VariantRegistration $row */
        foreach ($item->getVariantsRegistration()->getVariantRegistration() as $row) {
            if ($row->getId() == $variant_id) {
                $variant = $row;
                break;
            }
        }

        /**  Omni is not accepting the return object so trying to work this out in different way */

        /** @var Entity\VariantRegistration $response */
        // @codingStandardsIgnoreLine
        $response = new Entity\VariantRegistration();

        $response->setItemId($variant->getItemId())
            ->setId($variant->getId())
            ->setDimension1($variant->getDimension1())
            ->setDimension2($variant->getDimension2())
            ->setDimension3($variant->getDimension3())
            ->setDimension4($variant->getDimension4())
            ->setDimension5($variant->getDimension5())
            ->setDimension6($variant->getDimension6())
            ->setFrameworkCode($variant->getFrameworkCode())
            ->setImages($variant->getImages());

        return $response;
    }

    /**
     * @param $item
     * @param $orderData
     * @return array|null
     */
    // @codingStandardsIgnoreLine
    public function getOrderDiscountLinesForItem($item, $orderData, $type = 1)
    {
        try {
            $discountInfo = [];
            $customPrice = 0;
            if ($type == 2) {
                $itemSku = $item->getItemId();
                $itemSku = explode("-", $itemSku);
                if (count($itemSku) < 2) {
                    $itemSku[1] = $item->getVariantId();
                }
                $customPrice = $item->getDiscountAmount();
            } else {
                $itemSku = $item->getSku();
                $itemSku = explode("-", $itemSku);
                if (count($itemSku) < 2) {
                    $itemSku[1] = '';
                }
                $customPrice = $item->getCustomPrice();
            }
            $check = false;

            $basketData = $orderData;
            $discountText = LSR::LS_DISCOUNT_PRICE_PERCENTAGE_TEXT;

            foreach ($basketData->getOrderLines() as $basket) {
                if ($basket->getItemId() == $itemSku[0] && $basket->getVariantId() == $itemSku[1]) {
                    if ($customPrice > 0 && $customPrice != null) {
                        if (is_array($basketData->getOrderDiscountLines()->getOrderDiscountLine())) {
                            // @codingStandardsIgnoreLine
                            foreach ($basketData->getOrderDiscountLines()->getOrderDiscountLine() as $orderDiscountLine) {
                                if ($basket->getLineNumber() == $orderDiscountLine->getLineNumber()) {
                                    if (!in_array($orderDiscountLine->getDescription() . '<br />', $discountInfo)) {
                                        $discountInfo[] = $orderDiscountLine->getDescription() . '<br />';
                                    }
                                }
                            }
                        } else {
                            // @codingStandardsIgnoreLine
                            $discountInfo[] = $basketData->getOrderDiscountLines()->getOrderDiscountLine()->getDescription();
                        }

                        $check = true;
                    }
                }
            }
            if ($check == true) {
                return [implode($discountInfo), $discountText];
            } else {
                return null;
            }
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }
}
