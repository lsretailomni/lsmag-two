<?php
declare(strict_types=1);

namespace Ls\Omni\Helper;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\CentralEcommerce\Entity\COSourcingLocationListV4;
use \Ls\Omni\Client\CentralEcommerce\Entity\COSourcingLocationsV4;
use \Ls\Omni\Client\CentralEcommerce\Entity\CustomerOrderHeaderV4;
use \Ls\Omni\Client\CentralEcommerce\Entity\CustomerOrderLineV4;
use \Ls\Omni\Client\CentralEcommerce\Entity\RootCOSourcingLocationListV4In;
use \Ls\Omni\Client\CentralEcommerce\Operation\COSourcingLocationListV4Factory;
use \Ls\Omni\Model\VendorSourcing\LeadTimeParser;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides the vendor sourcing (order from vendor) options of a product
 */
class VendorSourcingHelper
{
    public const TYPE_SHIP = 'ship';

    public const TYPE_COLLECT = 'collect';

    /**
     * @param LSR $lsr
     * @param LoggerInterface $logger
     * @param LeadTimeParser $leadTimeParser
     * @param StockHelper $stockHelper
     * @param TimezoneInterface $timezone
     * @param ItemHelper $itemHelper
     * @param ProductRepositoryInterface $productRepository
     * @param COSourcingLocationListV4Factory $operationFactory
     */
    public function __construct(
        private readonly LSR $lsr,
        private readonly LoggerInterface $logger,
        private readonly LeadTimeParser $leadTimeParser,
        private readonly StockHelper $stockHelper,
        private readonly TimezoneInterface $timezone,
        private readonly ItemHelper $itemHelper,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly COSourcingLocationListV4Factory $operationFactory
    ) {
    }

    /**
     * Get the vendor sourcing options for the given product
     *
     * Each row: type (ship|collect), store_code, store_name, days, date, priority
     *
     * @param string $sku
     * @param int|null $simpleProductId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getOptions(string $sku, ?int $simpleProductId = null): array
    {
        if (!$this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            return [];
        }

        $parent = $this->productRepository->get($sku);
        $child = null;

        if ($simpleProductId !== null) {
            $usedIds = $parent->getTypeId() === Configurable::TYPE_CODE
                ? array_map('intval', $parent->getTypeInstance()->getUsedProductIds($parent))
                : [];

            if (!in_array($simpleProductId, $usedIds, true)) {
                return [];
            }
            $child = $this->productRepository->getById($simpleProductId);
        }

        if (!$this->isVendorSourced($parent, $child)) {
            return [];
        }

        $itemNo = $this->itemHelper->getLsCentralItemIdBySku($sku);
        $variantId = $child ? (string)$child->getData(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE) : '';

        try {
            $rows = $this->fetchSourcingRows($itemNo, $variantId);
        } catch (\Throwable $e) {
            $this->logger->error('Vendor sourcing options request failed: ' . $e->getMessage());

            return [];
        }

        return $this->buildOptions($rows);
    }

    /**
     * Check whether the product is vendor sourced, the selected child wins over the parent
     *
     * @param Product $parent
     * @param Product|null $child
     * @return bool
     */
    public function isVendorSourced(Product $parent, ?Product $child): bool
    {
        $value = $child ? $child->getData(LSR::LS_VENDOR_SOURCING_ATTRIBUTE_CODE) : null;

        if ($value === null || $value === '') {
            $value = $parent->getData(LSR::LS_VENDOR_SOURCING_ATTRIBUTE_CODE);
        }

        return (int)$value === 1;
    }

    /**
     * Call Central and return the normalised sourcing location rows
     *
     * @param string $itemNo
     * @param string $variantId
     * @return COSourcingLocationsV4[]
     */
    private function fetchSourcingRows(string $itemNo, string $variantId): array
    {
        // @codingStandardsIgnoreStart
        $operation = $this->operationFactory->create();

        $header = $operation->createInstance(
            CustomerOrderHeaderV4::class,
            [
                'data' => [
                    CustomerOrderHeaderV4::DOCUMENT_ID => '0',
                    CustomerOrderHeaderV4::CREATED_AT_STORE => $this->lsr->getActiveWebStore()
                ]
            ]
        );

        $linePayload = [
            CustomerOrderLineV4::DOCUMENT_ID => '0',
            CustomerOrderLineV4::LINE_NO => 10000,
            CustomerOrderLineV4::LINE_TYPE => '0',
            CustomerOrderLineV4::NUMBER => $itemNo,
            CustomerOrderLineV4::QUANTITY => 1.0
        ];

        if ($variantId !== '') {
            $linePayload[CustomerOrderLineV4::VARIANT_CODE] = $variantId;
        }

        $line = $operation->createInstance(CustomerOrderLineV4::class, ['data' => $linePayload]);

        $root = $operation->createInstance(
            RootCOSourcingLocationListV4In::class,
            [
                'data' => [
                    RootCOSourcingLocationListV4In::CUSTOMER_ORDER_HEADER_V4 => $header,
                    RootCOSourcingLocationListV4In::CUSTOMER_ORDER_LINE_V4 => $line
                ]
            ]
        );

        $operation->setOperationInput([
            COSourcingLocationListV4::COSOURCING_LOCATION_LIST_V4_XML => $root
        ]);
        $response = $operation->execute();
        // @codingStandardsIgnoreEnd

        if (!$response || $response->getResponsecode() !== '0000') {
            if ($response) {
                $this->logger->info('Vendor sourcing options: ' . $response->getErrortext());
            }

            return [];
        }

        $xml = $response->getCosourcinglocationlistv4xml();
        $rows = $xml ? $xml->getCosourcinglocationsv4() : null;

        if (is_array($rows)) {
            return $rows;
        }

        return $rows ? [$rows] : [];
    }

    /**
     * Turn the Central rows into sorted, de-duplicated display rows
     *
     * @param COSourcingLocationsV4[] $rows
     * @return array
     */
    private function buildOptions(array $rows): array
    {
        $options = [];
        $collectCodes = [];

        foreach ($rows as $row) {
            $types = $this->getRowTypes($row);

            if (empty($types)) {
                continue;
            }

            $days = $this->leadTimeParser->parse($row->getLeadtimecalculation());

            if ($days === null) {
                $this->logger->debug(
                    'Vendor sourcing: unsupported lead time "' . $row->getLeadtimecalculation() . '" skipped.'
                );
                continue;
            }

            foreach ($types as $type) {
                $code = $type === self::TYPE_COLLECT ? (string)$row->getSourcinglocation() : null;

                if ($code !== null) {
                    $collectCodes[$code] = $code;
                }

                $options[] = [
                    'type' => $type,
                    'store_code' => $code,
                    'store_name' => null,
                    'days' => $days,
                    'date' => $this->getDeliveryDate($days),
                    'priority' => $row->getPriority()
                ];
            }
        }

        if (empty($options)) {
            return [];
        }

        $names = $this->getStoreNames(array_values($collectCodes));

        foreach ($options as &$option) {
            if ($option['type'] === self::TYPE_COLLECT) {
                $option['store_name'] = $names[$option['store_code']] ?? $option['store_code'];
            }
        }
        unset($option);

        return $this->sortAndDeduplicate($options);
    }

    /**
     * Single place deciding which option types a row is eligible for
     *
     * @param COSourcingLocationsV4 $row
     * @return string[]
     */
    private function getRowTypes(COSourcingLocationsV4 $row): array
    {
        $types = [];

        if ($row->getWillshiporders() === true) {
            $types[] = self::TYPE_SHIP;
        }

        if ($row->getOrderscanbecollected() === true && (string)$row->getSourcinglocation() !== '') {
            $types[] = self::TYPE_COLLECT;
        }

        return $types;
    }

    /**
     * Get store names by store code from the replicated stores, one lookup for all codes
     *
     * @param string[] $codes
     * @return array
     */
    private function getStoreNames(array $codes): array
    {
        $names = [];

        if (empty($codes)) {
            return $names;
        }

        try {
            foreach ($this->stockHelper->getAllStoresFromReplTable($codes) as $store) {
                $names[$store->getNavId()] = $store->getName();
            }
        } catch (\Throwable $e) {
            $this->logger->debug('Vendor sourcing: store lookup failed: ' . $e->getMessage());
        }

        foreach ($codes as $code) {
            if (empty($names[$code])) {
                $this->logger->debug('Vendor sourcing: store ' . $code . ' not found, using the code.');
            }
        }

        return array_filter($names);
    }

    /**
     * Get the formatted delivery date for the given lead time
     *
     * @param int $days
     * @return string
     */
    private function getDeliveryDate(int $days): string
    {
        $date = $this->timezone->date()->modify('+' . $days . ' days');

        return $this->timezone->formatDate($date, \IntlDateFormatter::MEDIUM);
    }

    /**
     * Sort by days, priority (null last), ship before collect, store name and remove duplicates
     *
     * @param array $options
     * @return array
     */
    private function sortAndDeduplicate(array $options): array
    {
        $unique = [];

        foreach ($options as $option) {
            $key = $option['type'] . '|' . $option['store_code'] . '|' . $option['days'];

            if (!isset($unique[$key])) {
                $unique[$key] = $option;
            } elseif ($option['priority'] !== null &&
                ($unique[$key]['priority'] === null || $option['priority'] < $unique[$key]['priority'])
            ) {
                $unique[$key]['priority'] = $option['priority'];
            }
        }

        $options = array_values($unique);

        usort($options, static function (array $a, array $b): int {
            return [
                $a['days'],
                $a['priority'] === null ? PHP_INT_MAX : $a['priority'],
                $a['type'] === self::TYPE_SHIP ? 0 : 1,
                (string)$a['store_name']
            ] <=> [
                $b['days'],
                $b['priority'] === null ? PHP_INT_MAX : $b['priority'],
                $b['type'] === self::TYPE_SHIP ? 0 : 1,
                (string)$b['store_name']
            ];
        });

        return $options;
    }
}
