<?php

namespace Ls\Replication\Ui\DataProvider;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Xml\Parser;
use Ls\Core\Model\LSR;

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
     * @var \Magento\Framework\Module\Dir\Reader
     */
    protected $moduleDirReader;

    /**
     * @var \Magento\Framework\Xml\Parser
     */
    private $parser;

    /** @var LSR */
    protected $_lsr;

    /**
     * CronsProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Http $request
     * @param Reader $moduleDirReader
     * @param Parser $parser
     * @param LSR $LSR
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
        LSR $LSR,
        array $meta = [],
        array $data = []
    )
    {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->request = $request;
        $this->moduleDirReader = $moduleDirReader;
        $this->parser = $parser;
        $this->_lsr = $LSR;
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
        $this->_lsr->flushConfig();
        foreach ($cronsGroupListing as $cronlist) {
            $path = '';
            if ($cronlist['_attribute']['id'] == "replication") {
                $condition = __("Flat to Magento");
            } elseif ($cronlist['_attribute']['id'] == "flat_replication") {
                $condition = __("Omni to Flat");
                $path = $this->_lsr::CRON_STATUS_PATH_PREFIX;
            } else {
                $condition = __("");
            }
            foreach ($cronlist['_value']['job'] as $joblist) {
                $fullReplicationStatus = 0;
                $cronName = $joblist['_attribute']['name'];
                if ($path != '') {
                    $pathNew = $path . $cronName;
                    $fullReplicationStatus = $this->_lsr->getStoreConfig($pathNew);
                }
                if ($cronName == 'repl_attributes') {
                    $cronAttributeCheck = $this->_lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_ATTRIBUTE);
                    $cronAttributeVariantCheck = $this->_lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT);
                    if ($cronAttributeCheck && $cronAttributeCheck)
                        $fullReplicationStatus = 1;
                }
                if ($cronName == 'repl_category') {
                    $fullReplicationStatus = $this->_lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_CATEGORY);
                }
                if ($cronName == 'repl_products') {
                    $fullReplicationStatus = $this->_lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_PRODUCT);
                }
                $items[] = [
                    'id' => $counter,
                    'fullreplicationstatus' => ($fullReplicationStatus == 1) ? '<div class="flag-green custom-grid-flag">Complete</div>' : '<div class="flag-yellow custom-grid-flag">Pending</div>',
                    'label' => $cronName,
                    'value' => $joblist['_attribute']['instance'],
                    'condition' => $condition
                ];
                $counter++;
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
