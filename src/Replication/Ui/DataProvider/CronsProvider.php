<?php

namespace Ls\Replication\Ui\DataProvider;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Reporting;
use Magento\Framework\Xml\Parser;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\System\Store as StoreManager;
use Magento\Ui\DataProvider\AddFieldToCollectionInterface;
use Magento\Ui\DataProvider\AddFilterToCollectionInterface;
use Psr\Log\LoggerInterface;

/**
 * Data Provider for cron listing
 */
class CronsProvider extends DataProvider implements DataProviderInterface
{
    /**
     * @var AddFieldToCollectionInterface[]
     */
    public $addFieldStrategies;

    /**
     * @var AddFilterToCollectionInterface[]
     */
    public $addFilterStrategies;

    /**
     * @var Http
     */
    public $request;
    /**
     * @var Reader
     */
    public $moduleDirReader;

    /**
     * @var StoreManager
     */
    public $storeManager;

    /** @var LSR */
    public $lsr;

    /**
     * @var Parser
     */
    public $parser;

    /** @var ReplicationHelper */
    public $rep_helper;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Reporting $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param Reader $moduleDirReader
     * @param Parser $parser
     * @param FilterBuilder $filterBuilder
     * @param StoreManager $storeManager
     * @param LSR $lsr
     * @param ReplicationHelper $repHelper
     * @param LoggerInterface $logger
     * @param array $meta
     * @param array $data
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
        ReplicationHelper $repHelper,
        LoggerInterface $logger,
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
        $this->request         = $request;
        $this->moduleDirReader = $moduleDirReader;
        $this->parser          = $parser;
        $this->lsr             = $lsr;
        $this->filterBuilder   = $filterBuilder;
        $this->rep_helper      = $repHelper;
        $this->storeManager    = $storeManager;
        $this->logger          = $logger;
    }

    /**
     * @inheritDoc
     *
     * @return array
     * @throws Exception
     */
    public function getData()
    {
        $cronsGroupListing = $this->readCronFile();
        $items             = [];
        $counter           = 1;
        $scope             = $this->request->getParam('website') ? 'website' : 'store';
        $storeId           = $scope == 'website' ?
            $this->request->getParam('website') : $this->request->getParam('store');
        $pagingParam       = $this->request->getParam('paging');

        if (empty($storeId)) {
            $storeId = $this->getDefaultStoreId();
        }

        foreach ($cronsGroupListing as $cronlist) {
            $path = '';

            if (array_key_exists('_value', $cronlist['_value']['job'])) {
                $cronlist['_value']['job'] = [$cronlist['_value']['job']];
            }

            if ($cronlist['_attribute']['id'] == "replication" || $cronlist['_attribute']['id'] == "sync_operations") {
                $condition = __('Flat to Magento');
            } elseif ($cronlist['_attribute']['id'] == "flat_replication") {
                $condition = __('Omni to Flat');
                $path      = LSR::CRON_STATUS_PATH_PREFIX;
            } else {
                $condition = '';
            }

            foreach ($cronlist['_value']['job'] as $joblist) {
                $fullReplicationStatus = '0';
                $cronName              = $joblist['_attribute']['name'];
                $isTranslationRelatedCron =
                    $cronName == 'repl_data_translation' ||
                    $cronName == 'repl_html_translation' ||
                    $cronName == 'repl_data_translation_lang_code' ||
                    $cronName == 'repl_data_translation_to_magento';

                if ($scope == 'store') {
                    if (($cronlist['_attribute']['id'] == 'flat_replication' ||
                            $cronlist['_attribute']['id'] == 'reset') &&
                        !$isTranslationRelatedCron
                    ) {
                        continue;
                    }
                } else {
                    if (($cronlist['_attribute']['id'] != 'flat_replication' &&
                        $cronlist['_attribute']['id'] != 'reset') ||
                        $isTranslationRelatedCron
                    ) {
                        continue;
                    }
                }

                if ($path != '') {
                    $pathNew               = $path . $cronName;
                    $fullReplicationStatus = $this->lsr->getConfigValueFromDb(
                        $pathNew,
                        $scope == 'website' ? ScopeInterface::SCOPE_WEBSITES : ScopeInterface::SCOPE_STORES,
                        $storeId
                    );
                }

                /**
                 * We need this so that we can add plugin into the hospitality modules for the new crons.
                 */
                $fullReplicationStatus = $this->getStatusByCronCode($cronName, $storeId, $fullReplicationStatus);
                $lastExecute           = $this->rep_helper->convertDateTimeIntoCurrentTimeZone(
                    $this->lsr->getConfigValueFromDb(
                        'ls_mag/replication/last_execute_' . $cronName,
                        $scope == 'website' ? ScopeInterface::SCOPE_WEBSITES : ScopeInterface::SCOPE_STORES,
                        $storeId
                    ),
                    'd M, Y h:i:s A'
                );
                $statusStr             = ($fullReplicationStatus == 1) ?
                    '<div class="flag-green custom-grid-flag">' . __("Complete") . '</div>' :
                    '<div class="flag-yellow custom-grid-flag">' . __("Pending") . '</div>';

                if (strpos($cronName, '_reset') !== false || $cronName == "sync_version" ||
                    $cronName == "sync_orders" || $cronName == "sync_customers") {
                    $condition = $statusStr = '';
                }
                $items[] = [
                    'id'                    => $counter,
                    'store'                 => $scope == 'website' ?
                        $this->storeManager->getWebsiteName($storeId) : $this->storeManager->getStoreName($storeId),
                    'storeId'               => $storeId,
                    'fullreplicationstatus' => $statusStr,
                    'label'                 => $cronName,
                    'lastexecuted'          => $lastExecute,
                    'value'                 => $joblist['_attribute']['instance'],
                    'condition'             => $condition,
                    'scope'                 => $scope
                ];
                $counter++;
            }
        }

        $pageSize    = $pagingParam && (int)$pagingParam['pageSize'] ? (int)$pagingParam['pageSize'] : 60;
        $pageCurrent = $pagingParam && (int)$pagingParam['current'] ? (int)$pagingParam['current'] : 1;
        $pageOffset  = ($pageCurrent - 1) * $pageSize;

        return [
            'totalRecords' => count($items),
            'items'        => array_slice($items, $pageOffset, $pageOffset + $pageSize)
        ];
    }

    /**
     * This is being used in Hospitality module, so do not change the structure of it.
     *
     * @return mixed
     */
    public function readCronFile()
    {
        try {
            $filePath    = $this->moduleDirReader->getModuleDir('etc', 'Ls_Replication') . '/crontab.xml';
            $parsedArray = $this->parser->load($filePath)->xmlToArray();
        } catch (Exception $e) {
            $this->logger->debug($e);
        }

        return $parsedArray['config']['_value']['group'];
    }

    /**
     * Prepare Update Url
     *
     * @return void
     */
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

    /**
     * This is being used in Hospitality module, so do not change the structure of it.
     *
     * @param string $cronName
     * @param string $storeId
     * @param string $fullReplicationStatus
     * @return bool|int|string
     */
    public function getStatusByCronCode($cronName, $storeId, $fullReplicationStatus)
    {
        if ($cronName == 'repl_data_translation_to_magento') {
            $fullReplicationStatus = $this->lsr->getConfigValueFromDb(
                LSR::SC_SUCCESS_CRON_DATA_TRANSLATION_TO_MAGENTO,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
            return $fullReplicationStatus;
        }

        if ($cronName == 'repl_attributes') {
            $cronAttributeCheck        = $this->lsr->getConfigValueFromDb(
                LSR::SC_SUCCESS_CRON_ATTRIBUTE,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
            $cronAttributeVariantCheck = $this->lsr->getConfigValueFromDb(
                LSR::SC_SUCCESS_CRON_ATTRIBUTE_VARIANT,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
            if ($cronAttributeCheck && $cronAttributeVariantCheck) {
                $fullReplicationStatus = 1;
            }
            return $fullReplicationStatus;
        }

        if ($cronName == 'repl_category') {
            $fullReplicationStatus = $this->lsr->getConfigValueFromDb(
                LSR::SC_SUCCESS_CRON_CATEGORY,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
            return $fullReplicationStatus;
        }

        if ($cronName == 'repl_products') {
            $fullReplicationStatus = $this->lsr->getConfigValueFromDb(
                LSR::SC_SUCCESS_CRON_PRODUCT,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
            return $fullReplicationStatus;
        }

        if ($cronName == 'repl_discount_create') {
            $fullReplicationStatus = $this->lsr->getConfigValueFromDb(
                LSR::SC_SUCCESS_CRON_DISCOUNT,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
            return $fullReplicationStatus;
        }

        if ($cronName == 'repl_price_sync') {
            $fullReplicationStatus = $this->lsr->getConfigValueFromDb(
                LSR::SC_SUCCESS_CRON_PRODUCT_PRICE,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
            return $fullReplicationStatus;
        }

        if ($cronName == 'repl_inventory_sync') {
            $fullReplicationStatus = $this->lsr->getConfigValueFromDb(
                LSR::SC_SUCCESS_CRON_PRODUCT_INVENTORY,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
            return $fullReplicationStatus;
        }

        if ($cronName == 'repl_item_updates_sync') {
            $fullReplicationStatus = $this->lsr->getConfigValueFromDb(
                LSR::SC_SUCCESS_CRON_ITEM_UPDATES,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
            return $fullReplicationStatus;
        }

        if ($cronName == 'repl_item_images_sync') {
            $fullReplicationStatus = $this->lsr->getConfigValueFromDb(
                LSR::SC_SUCCESS_CRON_ITEM_IMAGES,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
            return $fullReplicationStatus;
        }

        if ($cronName == 'repl_attributes_value_sync') {
            $fullReplicationStatus = $this->lsr->getConfigValueFromDb(
                LSR::SC_SUCCESS_CRON_ATTRIBUTES_VALUE,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
            return $fullReplicationStatus;
        }

        if ($cronName == 'repl_vendor_attributes_sync') {
            $fullReplicationStatus = $this->lsr->getConfigValueFromDb(
                LSR::SC_SUCCESS_CRON_VENDOR_ATTRIBUTE,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
            return $fullReplicationStatus;
        }

        if ($cronName == 'repl_tax_rules') {
            $fullReplicationStatus = $this->lsr->getConfigValueFromDb(
                LSR::SC_SUCCESS_CRON_TAX_RULES,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
            return $fullReplicationStatus;
        }

        return $fullReplicationStatus;
    }

    /**
     * Get Default store Id
     *
     * @return string
     */
    public function getDefaultStoreId()
    {
        $storeId = '';

        foreach ($this->storeManager->getStoreCollection() as $store) {
            $storeId = $store->getId();
            break;
        }

        return $storeId;
    }
}
