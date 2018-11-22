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
                 ['id' =>"1",'label'  => 'Item','value' => 'Ls\Replication\Cron\ReplEcommItemsTask'],
                 ['id' =>"2",'label'  => 'Barcode','value' => 'Ls\Replication\Cron\ReplEcommBarcodesTask'],
                 ['id' =>"3",'label'  => 'Extended Variant Value','value' => 'Ls\Replication\Cron\ReplEcommExtendedVariantsTask'],
                 ['id' =>"4",'label'  => 'Image Link','value' => 'Ls\Replication\Cron\ReplEcommImageLinksTask'],
                 ['id' =>"5",'label'  => 'Item Varian Registration','value' => 'Ls\Replication\Cron\ReplEcommItemVariantRegistrationsTask'],
                 ['id' =>"6",'label'  => 'Hierarchy','value' => 'Ls\Replication\Cron\ReplEcommHierarchyTask'],
                 ['id' =>"7",'label'  => 'Hierarchy Node','value' => 'Ls\Replication\Cron\ReplEcommHierarchyNodeTask'],
                 ['id' =>"8",'label'  => 'Hierarchy Leaf','value' => 'Ls\Replication\Cron\ReplEcommHierarchyLeafTask'],
                 ['id' =>"9",'label'  => 'Attribute','value' => 'Ls\Replication\Cron\ReplEcommAttributeTask'],
                 ['id' =>"10",'label' => 'Attribute Value','value' => 'Ls\Replication\Cron\ReplEcommAttributeValueTask'],
                 ['id' =>"11",'label' => 'Attribute Option Value','value' => 'Ls\Replication\Cron\ReplEcommAttributeOptionValueTask'],
                 ['id' =>"12",'label' => 'Discount','value' => 'Ls\Replication\Cron\ReplEcommDiscountsTask'],
                 ['id' =>"13",'label' => 'Item Category','value' => 'Ls\Replication\Cron\ReplEcommItemCategoriesTask'],
                 ['id' =>"14",'label' => 'Product Group','value' => 'Ls\Replication\Cron\ReplEcommProductGroupsTask'],
                 ['id' =>"15",'label' => 'Stores','value' => 'Ls\Replication\Cron\ReplEcommStoresTask'],
                 ['id' =>"16",'label' => 'Attributes','value' => 'Ls\Replication\Cron\AttributesCreateTask'],
                 ['id' =>"17",'label' => 'Category','value' => 'Ls\Replication\Cron\CategoryCreateTask'],
                 ['id' =>"18",'label' => 'Products','value' => 'Ls\Replication\Cron\ProductCreateTask'],
                 ['id' =>"19",'label' => 'Barcode Update','value' => 'Ls\Replication\Cron\BarcodeUpdateTask'],
                 ['id' =>"20",'label' => 'Discount Create','value' => 'Ls\Replication\Cron\DiscountCreateTask']
        ];

        $pagesize = intval($this->request->getParam('paging')['pageSize']);
        $pageCurrent = intval($this->request->getParam('paging')['current']);
        $pageoffset = ($pageCurrent - 1)*$pagesize;

        return [
            'totalRecords' => count($items),
            'items' => array_slice($items,$pageoffset , $pageoffset+$pagesize)
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
?>