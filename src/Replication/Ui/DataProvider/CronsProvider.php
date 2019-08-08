<?php

namespace Ls\Replication\Ui\DataProvider;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Xml\Parser;
use \Ls\Core\Model\LSR;

/**
 * Class ProductDataProvider
 */
class CronsProvider extends AbstractDataProvider implements DataProviderInterface
{

    /**
     * @var \Magento\Ui\DataProvider\AddFieldToCollectionInterface[]
     */
    public $addFieldStrategies;

    /**
     * @var \Magento\Ui\DataProvider\AddFilterToCollectionInterface[]
     */
    public $addFilterStrategies;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    public $request;
    /**
     * @var \Magento\Framework\Module\Dir\Reader
     */
    public $moduleDirReader;

    /**
     * @var \Magento\Framework\Xml\Parser
     */
    private $parser;

    /** @var LSR */
    public $lsr;

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
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->request = $request;
        $this->moduleDirReader = $moduleDirReader;
        $this->parser = $parser;
        $this->lsr = $LSR;
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
        $this->lsr->flushConfig();
        foreach ($cronsGroupListing as $cronlist) {
            $path = '';
            if ($cronlist['_attribute']['id'] == "replication") {
                $condition = __("Flat to Magento");
            } elseif ($cronlist['_attribute']['id'] == "flat_replication") {
                $condition = __("Omni to Flat");
                $path = $this->lsr::CRON_STATUS_PATH_PREFIX;
            } else {
                $condition = "";
            }
            foreach ($cronlist['_value']['job'] as $joblist) {
                $fullReplicationStatus = 0;
                $cronName = $joblist['_attribute']['name'];
                if ($path != '') {
                    $pathNew = $path . $cronName;
                    $fullReplicationStatus = $this->lsr->getStoreConfig($pathNew);
                }
                if ($cronName == 'repl_attributes') {
                    $cronAttributeCheck = $this->lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_ATTRIBUTE);
                    $cronAttributeVariantCheck = $this->lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT);
                    if ($cronAttributeCheck && $cronAttributeCheck) {
                        $fullReplicationStatus = 1;
                    }
                }
                if ($cronName == 'repl_category') {
                    $fullReplicationStatus = $this->lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_CATEGORY);
                }
                if ($cronName == 'repl_products') {
                    $fullReplicationStatus = $this->lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_PRODUCT);
                }
                if ($cronName == 'repl_discount_create') {
                    $fullReplicationStatus = $this->lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_DISCOUNT);
                }
                $lastExecute = $this->lsr->getStoreConfig('ls_mag/replication/last_execute_' . $cronName);
                $statusStr = ($fullReplicationStatus == 1) ?
                    '<div class="flag-green custom-grid-flag">Complete</div>' :
                    '<div class="flag-yellow custom-grid-flag">Pending</div>';
                if (strpos($cronName, '_reset') !== false) {
                    $statusStr = '';
                }
                $items[] = [
                    'id' => $counter,
                    'fullreplicationstatus' => $statusStr,
                    'label' => $cronName,
                    'lastexecuted' => $lastExecute,
                    'value' => $joblist['_attribute']['instance'],
                    'condition' => $condition
                ];
                $counter++;
            }
        }
        // @codingStandardsIgnoreStart
        $pagesize = (int)$this->request->getParam('paging')['pageSize'];
        $pageCurrent = (int)$this->request->getParam('paging')['current'];
        // @codingStandardsIgnoreEnd
        $pageoffset = ($pageCurrent - 1) * $pagesize;

        return [
            'totalRecords' => count($items),
            'items' => array_slice($items, $pageoffset, $pageoffset + $pagesize)
        ];
    }

    /**
     * @return mixed
     */
    public function readCronFile()
    {
        try {
            $filePath = $this->moduleDirReader->getModuleDir('etc', 'Ls_Replication') . '/crontab.xml';
            $parsedArray = $this->parser->load($filePath)->xmlToArray();
            return $parsedArray['config']['_value']['group'];
        } catch (\Exception $e) {
        }
    }

    /**
     * @param int $offset
     * @param int $size
     */
    public function setLimit($offset, $size)
    {
    }

    /**
     * @param string $field
     * @param string $direction
     */
    public function addOrder($field, $direction)
    {
    }

    /**
     * @param \Magento\Framework\Api\Filter $filter
     * @return mixed|void
     */
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
