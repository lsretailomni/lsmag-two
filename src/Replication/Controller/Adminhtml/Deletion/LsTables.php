<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use \Ls\Core\Model\LSR;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class LsTables for truncating all flat tables
 */
class LsTables extends AbstractReset
{
    public const LS_TRANSLATION_TABLES = [
        'ls_replication_repl_data_translation',
        'ls_replication_repl_data_translation_lang_code'
    ];

    /** List of all the ls_ tables */
    public const LS_TABLES = [
        'ls_replication_loy_item',
        'ls_replication_repl_currency',
        'ls_replication_repl_currency_exch_rate',
        'ls_replication_repl_customer',
        'ls_replication_repl_hierarchy',
        'ls_replication_repl_image',
        'ls_replication_repl_item_category',
        'ls_replication_repl_product_group',
        'ls_replication_repl_shipping_agent',
        'ls_replication_repl_store',
        'ls_replication_repl_store_tender_type',
        'ls_replication_repl_unit_of_measure',
        'ls_replication_repl_vendor'
    ];

    public const TABLE_CONFIGS = [
        'ls_mag/replication/',
        'ls_mag/replication/last_execute_',
        'ls_mag/replication/status_',
        'ls_mag/replication/max_key_',
        'ls_mag/replication/app_id_'
    ];

    /**
     * Truncate ls_ Tables
     *
     * @return ResponseInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $jobName             = $this->_request->getParam('jobname');
        $scopeId             = $this->_request->getParam('store');
        $scope               = $this->_request->getParam('scope');
        $coreConfigTableName = $this->replicationHelper->getGivenTableName('core_config_data');
        $this->replicationHelper->getConnection()->startSetup();

        if ($jobName != '' && $scopeId != '') {
            $this->resetSpecificCronData($jobName, $scopeId, $coreConfigTableName);
            $message      = __('%1 table truncated successfully.', $jobName);
            $redirectPath = 'ls_repl/cron/grid/';
        } else {
            $this->resetAllCronsData($scopeId, $coreConfigTableName);
            $message      = __('All ls_ tables truncated successfully.');
            $redirectPath = 'adminhtml/system_config/edit/section/ls_mag';
        }

        if ($scope == 'website') {
            $arguments = ['website' => $scopeId, 'scope' => 'website'];
        } else {
            $arguments = ['store' => $scopeId, 'scope' => 'store'];
        }
        $this->replicationHelper->getConnection()->endSetup();
        $this->messageManager->addSuccessMessage($message);

        return $this->_redirect($redirectPath, $arguments);
    }

    /**
     * Reset specific cron data
     *
     * @param $jobName
     * @param $scopeId
     * @param $coreConfigTableName
     * @return void
     */
    public function resetSpecificCronData($jobName, $scopeId, $coreConfigTableName)
    {
        $replicationTableName = 'ls_replication_' . $jobName;
        if ($jobName == LSR::SC_ITEM_HTML_JOB_CODE) {
            $replicationTableName = 'ls_replication_repl_data_translation';
            $this->replicationHelper->deleteGivenTableDataGivenConditions(
                $replicationTableName,
                [
                    'TranslationId = ?' => LSR::SC_TRANSLATION_ID_ITEM_HTML,
                    'scope_id = ?'      => $scopeId
                ]
            );
        } else {
            $replicationTableName = $this->replicationHelper->getGivenTableName($replicationTableName);
            $this->replicationHelper->deleteGivenTableDataGivenConditions(
                $replicationTableName,
                ['scope_id = ?' => $scopeId]
            );
        }

        foreach (self::TABLE_CONFIGS as $config) {
            $this->replicationHelper->deleteGivenTableDataGivenConditions(
                $coreConfigTableName,
                [
                    'path = ?' => $config . $jobName,
                    'scope_id = ?' => $scopeId
                ]
            );
        }
    }

    /**
     * Reset all crons data
     *
     * @param $scopeId
     * @param $coreConfigTableName
     * @return void
     * @throws NoSuchEntityException
     */
    public function resetAllCronsData($scopeId, $coreConfigTableName)
    {
        $mergedTables = array_merge(
            self::LS_DISCOUNT_RELATED_TABLES,
            self::LS_TAX_RELATED_TABLES,
            self::LS_ATTRIBUTE_RELATED_TABLES,
            self::LS_CATEGORY_RELATED_TABLES,
            self::LS_ITEM_RELATED_TABLES,
            self::LS_TABLES,
            self::LS_TRANSLATION_TABLES
        );
        foreach ($mergedTables as $lsTable) {
            $tableName = $this->replicationHelper->getGivenTableName($lsTable);

            if ($scopeId) {
                $websiteId = $this->replicationHelper->getWebsiteIdGivenStoreId($scopeId);

                if (!in_array($tableName, self::LS_TRANSLATION_TABLES)) {
                    $this->replicationHelper->deleteGivenTableDataGivenConditions(
                        $tableName,
                        ['scope_id = ?' => $websiteId]
                    );
                } else {
                    $this->replicationHelper->deleteGivenTableDataGivenConditions(
                        $tableName,
                        ['scope_id = ?' => $scopeId]
                    );
                }
            } else {
                $this->replicationHelper->truncateGivenTable($lsTable);
            }
        }

        if ($scopeId) {
            $this->replicationHelper->deleteGivenTableDataGivenConditions(
                $coreConfigTableName,
                [
                    'path like ?' => 'ls_mag/replication/%',
                    'scope_id = ?' => $scopeId
                ]
            );
        } else {
            $this->replicationHelper->deleteGivenTableDataGivenConditions(
                $coreConfigTableName,
                ['path like ?' => 'ls_mag/replication/%']
            );
        }
    }
}
