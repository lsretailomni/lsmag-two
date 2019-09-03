<?php

namespace Ls\Replication\Ui\DataProvider;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Xml\Parser;
use Magento\Store\Model\System\Store as StoreManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\Reporting;
use \Ls\Core\Model\LSR;

/**
 * Class ProductDataProvider
 */
class CronsProvider extends DataProvider implements DataProviderInterface
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

    /**
     * @var \Magento\Store\Model\System\Store
     */
    public $storeManager;

    /** @var LSR */
    public $lsr;

    /**
     * @var \Magento\Framework\Api\FilterBuilder;
     */
    public $filterBuilder;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Reporting $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param array $meta
     * @param array $data
     * @param array $additionalFilterPool
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Reporting $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        Reader $moduleDirReader,
        Parser $parser,
        FilterBuilder $filterBuilder,
        StoreManager $storeManager,
        LSR $lsr,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
        $this->moduleDirReader = $moduleDirReader;
        $this->parser = $parser;
        $this->filterBuilder = $filterBuilder;
        $this->storeManager = $storeManager;
        $this->lsr = $lsr;
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
        $storeId = $this->request->getParam('store');
        if (empty($storeId)) {
            $storeId =1;
        }
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
                    $fullReplicationStatus = $this->lsr->getStoreConfig($pathNew, $storeId);
                }
                if ($cronName == 'repl_attributes') {
                    $cronAttributeCheck = $this->lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_ATTRIBUTE, $storeId);
                    $cronAttributeVariantCheck = $this->lsr->getStoreConfig(
                        LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT,
                        $storeId
                    );
                    if ($cronAttributeCheck && $cronAttributeCheck) {
                        $fullReplicationStatus = 1;
                    }
                }
                if ($cronName == 'repl_category') {
                    $fullReplicationStatus = $this->lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_CATEGORY, $storeId);
                }
                if ($cronName == 'repl_products') {
                    $fullReplicationStatus = $this->lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_PRODUCT, $storeId);
                }
                if ($cronName == 'repl_discount_create') {
                    $fullReplicationStatus = $this->lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_DISCOUNT, $storeId);
                }
                $lastExecute = $this->lsr->getStoreConfig('ls_mag/replication/last_execute_' . $cronName, $storeId);
                $statusStr = ($fullReplicationStatus == 1) ?
                    '<div class="flag-green custom-grid-flag">Complete</div>' :
                    '<div class="flag-yellow custom-grid-flag">Pending</div>';
                if (strpos($cronName, '_reset') !== false) {
                    $statusStr = '';
                }
                $items[] = [
                    'id' => $counter,
                    'store' => $this->storeManager->getStoreName($storeId),
                    'storeId' => $storeId,
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

    public function prepareUpdateUrl()
    {
        if (!isset($this->data['config']['filter_url_params'])) {
            return;
        }
        foreach ($this->data['config']['filter_url_params'] as $paramName => $paramValue) {
            if ('*' == $paramValue) {
                $paramValue = $this->request->getParam($paramName);
            }
            if ($paramValue) {
                $this->data['config']['update_url'] = sprintf(
                    '%s%s/%s',
                    $this->data['config']['update_url'],
                    $paramName,
                    $paramValue
                );
                $this->addFilter(
                    $this->filterBuilder->setField($paramName)->setValue($paramValue)->setConditionType('eq')->create()
                );
            }
        }
    }
}
