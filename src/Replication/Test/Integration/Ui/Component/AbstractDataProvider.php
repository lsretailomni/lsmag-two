<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Ui\Component;

use Magento\Framework\Api\Filter;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
abstract class AbstractDataProvider extends TestCase
{
    /** @var ObjectManagerInterface */
    public $objectManager;

    public $dataProvider;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager    = Bootstrap::getObjectManager();
        $this->dataProvider = $this->objectManager->create(
            DataProvider::class,
            $this->getProviderData()
        );
    }

    /**
     * Resolve grid collection class from the data source name.
     */
    private function resolveGridCollectionClass(string $dataSourceName): ?string
    {
        $map = [
            'ls_repl_grids_attribute_data_source' => \Ls\Replication\Model\ResourceModel\ReplAttribute\Grid\Collection::class, 
            'ls_repl_grids_attributevalue_data_source' => \Ls\Replication\Model\ResourceModel\ReplAttributeValue\Grid\Collection::class, 
            'ls_repl_grids_attributeoptionvalue_data_source' => \Ls\Replication\Model\ResourceModel\ReplAttributeOptionValue\Grid\Collection::class, 
            'ls_repl_grids_barcode_data_source' => \Ls\Replication\Model\ResourceModel\ReplBarcode\Grid\Collection::class, 
            'ls_repl_grids_country_code_data_source' => \Ls\Replication\Model\ResourceModel\ReplCountryCode\Grid\Collection::class, 
            'ls_repl_grids_data_translation_data_source' => \Ls\Replication\Model\ResourceModel\ReplDataTranslation\Grid\Collection::class, 
            'ls_repl_grids_discount_setup_data_source' => \Ls\Replication\Model\ResourceModel\ReplDiscountSetup\Grid\Collection::class, 
            'ls_repl_grids_discount_validation_data_source' => \Ls\Replication\Model\ResourceModel\ReplDiscountValidation\Grid\Collection::class, 
            'ls_repl_grids_extendedvariantvalue_data_source' => \Ls\Replication\Model\ResourceModel\ReplExtendedVariantValue\Grid\Collection::class, 
            'ls_repl_grids_hierarchy_data_source' => \Ls\Replication\Model\ResourceModel\ReplHierarchy\Grid\Collection::class, 
            'ls_repl_grids_hierarchyleaf_data_source' => \Ls\Replication\Model\ResourceModel\ReplHierarchyLeaf\Grid\Collection::class, 
            'ls_repl_grids_hierarchynode_data_source' => \Ls\Replication\Model\ResourceModel\ReplHierarchyNode\Grid\Collection::class, 
            'ls_repl_grids_imagelink_data_source' => \Ls\Replication\Model\ResourceModel\ReplImageLink\Grid\Collection::class, 
            'ls_repl_grids_inventory_status_data_source' => \Ls\Replication\Model\ResourceModel\ReplInvStatus\Grid\Collection::class, 
            'ls_repl_grids_item_data_source' => \Ls\Replication\Model\ResourceModel\ReplItem\Grid\Collection::class, 
            'ls_repl_grids_item_uom_data_source' => \Ls\Replication\Model\ResourceModel\ReplItemUnitOfMeasure\Grid\Collection::class, 
            'ls_repl_grids_itemvariant_data_source' => \Ls\Replication\Model\ResourceModel\ReplItemVariant\Grid\Collection::class, 
            'ls_repl_grids_itemvariantregistration_data_source' => \Ls\Replication\Model\ResourceModel\ReplItemVariantRegistration\Grid\Collection::class, 
            'ls_repl_grids_item_price_data_source' => \Ls\Replication\Model\ResourceModel\ReplPrice\Grid\Collection::class, 
            'ls_repl_grids_item_vendor_data_source' => \Ls\Replication\Model\ResourceModel\ReplLoyVendorItemMapping\Grid\Collection::class, 
            'ls_repl_grids_store_data_source' => \Ls\Replication\Model\ResourceModel\ReplStore\Grid\Collection::class, 
            'ls_repl_grids_tax_setup_data_source' => \Ls\Replication\Model\ResourceModel\ReplTaxSetup\Grid\Collection::class, 
            'ls_repl_grids_vendor_data_source' => \Ls\Replication\Model\ResourceModel\ReplVendor\Grid\Collection::class,
            'ls_repl_grids_uom_data_source' => \Ls\Replication\Model\ResourceModel\ReplUnitOfMeasure\Grid\Collection::class,
            'ls_repl_grids_tax_data_source' => \Ls\Replication\Model\ResourceModel\ReplTaxSetup\Grid\Collection::class,
            // Add others as needed
        ];

        return $map[$dataSourceName] ?? null;
    }

    public function assertDataExists()
    {
        $data = $this->dataProvider->getData();

        $this->assertGreaterThanOrEqual(1, $data['items']);
    }

    public function applyFilterToData($filterData)
    {
        $filter = $this->objectManager->create(
            Filter::class,
            ['data' => $filterData]
        );
        $this->dataProvider->addFilter($filter);
        return $this->dataProvider->getData();
    }

    /**
     * @return array
     */
    public static function getDataByIdProvider(): array
    {
        $reflection = new \ReflectionClass(static::class);
        $self = $reflection->newInstanceWithoutConstructor();
        return [
            [[
                 'condition_type' => 'eq',
                 'field' => $self->getSearchFieldName(),
                 'value' => $self->getSearchFieldValue()
             ]],
        ];
    }

    public function getProviderData()
    {
        return [
            'name'             => $this->getDataSourceName(),
            'primaryFieldName' => $this->getPrimaryFieldName(),
            'requestFieldName' => $this->getRequestFieldName(),
        ];
    }
    abstract public function getSearchFieldName();
    abstract public function getSearchFieldValue();
    abstract public function getDataSourceName();
    abstract public function getPrimaryFieldName();
    abstract public function getRequestFieldName();
}
