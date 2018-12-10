<?php
namespace Ls\Replication\Ui\DataProvider;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Xml\Parser;

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

    /**
     * @var \Magento\Framework\Module\Dir\Reader
     */
    protected $moduleDirReader;

    /**
     * @var \Magento\Framework\Xml\Parser
     */
    private $parser;


    /**
     * CronsProvider constructor.
     * @param $name
     * @param $primaryFieldName
     * @param $requestFieldName
     * @param Http $request
     * @param Reader $moduleDirReader
     * @param Parser $parser
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Http $request,
        Reader $moduleDirReader,
        Parser $parser,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->request = $request;
        $this->moduleDirReader = $moduleDirReader;
        $this->parser = $parser;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $cronsGroupListing = $this->readCronFile();
        $condition = "";
        $items = [];
        $counter = 1;
        $cronsGroupListing = array_reverse($cronsGroupListing);
        foreach ($cronsGroupListing as $cronlist) {
            if ($cronlist['_attribute']['id'] == "replication") {
                $condition = __("Flat to Magento");
                foreach ($cronlist['_value']['job'] as $joblist) {
                    $items[] = [
                        'id' => $counter,
                        'label' => $joblist['_attribute']['name'],
                        'value' => $joblist['_attribute']['instance'],
                        'condition' => $condition
                    ];
                    $counter++;
                }
            } elseif ($cronlist['_attribute']['id'] == "flat_replication") {
                    $condition = __("Omni to Flat");
                foreach ($cronlist['_value']['job'] as $joblist) {
                    $items[] = [
                        'id' => $counter,
                        'label' => $joblist['_attribute']['name'],
                        'value' => $joblist['_attribute']['instance'],
                        'condition' => $condition
                    ];
                    $counter++;
                }
            } else {
                    $condition = __("");
                foreach ($cronlist['_value']['job'] as $joblist) {
                    $items[] = [
                        'id' => $counter,
                        'label' => $joblist['_attribute']['name'],
                        'value' => $joblist['_attribute']['instance'],
                        'condition' => $condition
                    ];
                    $counter++;
                }
            }
        }

        $pagesize = intval($this->request->getParam('paging')['pageSize']);
        $pageCurrent = intval($this->request->getParam('paging')['current']);
        $pageoffset = ($pageCurrent - 1) * $pagesize;

        return [
            'totalRecords' => count($items),
            'items' => array_slice($items, $pageoffset, $pageoffset + $pagesize)
        ];
    }

    public function readCronFile()
    {
        try {
            $filePath = $this->moduleDirReader->getModuleDir('etc', 'Ls_Replication') . '/crontab.xml';
            $parsedArray = $this->parser->load($filePath)->xmlToArray();
            return $parsedArray['config']['_value']['group'];
        } catch (\Exception $e) {
        }
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
