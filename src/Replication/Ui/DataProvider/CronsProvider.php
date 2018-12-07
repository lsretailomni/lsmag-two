<?php
namespace Ls\Replication\Ui\DataProvider;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Api\SearchResultsInterface;

/**
 * Class ProductDataProvider
 */
class CronsProvider extends AbstractDataProvider implements DataProviderInterface
{

    /**
     * @var \Magento\Ui\DataProvider\AddFieldToCollectionInterface[]
     */
    protected $addFieldStrategies;

    /**
     * @var \Magento\Ui\DataProvider\AddFilterToCollectionInterface[]
     */
    protected $addFilterStrategies;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * Construct
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Http $request,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->request = $request;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $items = [
            ['id' => "1", 'label' => 'Item', 'value' => 'Ls\Replication\Cron\ReplEcommItemsTask', 'condition' => 'Omni to Flat'],
            ['id' => "2", 'label' => 'Barcode', 'value' => 'Ls\Replication\Cron\ReplEcommBarcodesTask', 'condition' => 'Omni to Flat'],
            ['id' => "3", 'label' => 'Extended Variant Value', 'value' => 'Ls\Replication\Cron\ReplEcommExtendedVariantsTask', 'condition' => 'Omni to Flat'],
            ['id' => "4", 'label' => 'Image Link', 'value' => 'Ls\Replication\Cron\ReplEcommImageLinksTask', 'condition' => 'Omni to Flat'],
            ['id' => "5", 'label' => 'Item Varian Registration', 'value' => 'Ls\Replication\Cron\ReplEcommItemVariantRegistrationsTask', 'condition' => 'Omni to Flat'],
            ['id' => "6", 'label' => 'Hierarchy', 'value' => 'Ls\Replication\Cron\ReplEcommHierarchyTask', 'condition' => 'Omni to Flat'],
            ['id' => "7", 'label' => 'Hierarchy Node', 'value' => 'Ls\Replication\Cron\ReplEcommHierarchyNodeTask', 'condition' => 'Omni to Flat'],
            ['id' => "8", 'label' => 'Hierarchy Leaf', 'value' => 'Ls\Replication\Cron\ReplEcommHierarchyLeafTask', 'condition' => 'Omni to Flat'],
            ['id' => "9", 'label' => 'Attribute', 'value' => 'Ls\Replication\Cron\ReplEcommAttributeTask', 'condition' => 'Omni to Flat'],
            ['id' => "10", 'label' => 'Attribute Value', 'value' => 'Ls\Replication\Cron\ReplEcommAttributeValueTask', 'condition' => 'Omni to Flat'],
            ['id' => "11", 'label' => 'Attribute Option Value', 'value' => 'Ls\Replication\Cron\ReplEcommAttributeOptionValueTask', 'condition' => 'Omni to Flat'],
            ['id' => "12", 'label' => 'Discount', 'value' => 'Ls\Replication\Cron\ReplEcommDiscountsTask', 'condition' => 'Omni to Flat'],
            ['id' => "13", 'label' => 'Item Category', 'value' => 'Ls\Replication\Cron\ReplEcommItemCategoriesTask', 'condition' => 'Omni to Flat'],
            ['id' => "14", 'label' => 'Product Group', 'value' => 'Ls\Replication\Cron\ReplEcommProductGroupsTask', 'condition' => 'Omni to Flat'],
            ['id' => "15", 'label' => 'Stores', 'value' => 'Ls\Replication\Cron\ReplEcommStoresTask', 'condition' => 'Omni to Flat'],
            ['id' => "16", 'label' => 'Attributes', 'value' => 'Ls\Replication\Cron\AttributesCreateTask', 'condition' => 'Flat to Magento'],
            ['id' => "17", 'label' => 'Category', 'value' => 'Ls\Replication\Cron\CategoryCreateTask', 'condition' => 'Flat to Magento'],
            ['id' => "18", 'label' => 'Products', 'value' => 'Ls\Replication\Cron\ProductCreateTask', 'condition' => 'Flat to Magento'],
            ['id' => "19", 'label' => 'Barcode Update', 'value' => 'Ls\Replication\Cron\BarcodeUpdateTask', 'condition' => 'Flat to Magento'],
            ['id' => "20", 'label' => 'Discount Create', 'value' => 'Ls\Replication\Cron\DiscountCreateTask', 'condition' => 'Flat to Magento'],
        ];

        $pagesize = intval($this->request->getParam('paging')['pageSize']);
        $pageCurrent = intval($this->request->getParam('paging')['current']);
        $pageoffset = ($pageCurrent - 1) * $pagesize;

        return [
            'totalRecords' => count($items),
            'items' => array_slice($items, $pageoffset, $pageoffset + $pagesize)
        ];
    }


    public function setLimit($offset, $size)
    {
    }

    public function addOrder($field, $direction)
    {
    }

    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
    }

    /**
     * @param SearchResultInterface $searchResult
     * @return array
     */
    public function searchResultToOutput(SearchResultInterface $searchResult)
    {
    }

    public function getItems()
    {
    }
}
