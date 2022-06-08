<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use \Ls\Core\Model\LSR;
use Magento\Framework\App\ResponseInterface;

/**
 * Class LsTables for truncating all flat tables
 */
class LsTables extends AbstractReset
{
    /** @var array List of all the ls_ tables */
    public const LS_TABLES = [
        'ls_replication_loy_item',
        'ls_replication_repl_attribute',
        'ls_replication_repl_attribute_option_value',
        'ls_replication_repl_country_code',
        'ls_replication_repl_currency',
        'ls_replication_repl_currency_exch_rate',
        'ls_replication_repl_customer',
        'ls_replication_repl_data_translation',
        'ls_replication_repl_data_translation_lang_code',
        'ls_replication_repl_discount',
        'ls_replication_repl_discount_validation',
        'ls_replication_repl_extended_variant_value',
        'ls_replication_repl_hierarchy',
        'ls_replication_repl_hierarchy_node',
        'ls_replication_repl_image',
        'ls_replication_repl_item_category',
        'ls_replication_repl_product_group',
        'ls_replication_repl_shipping_agent',
        'ls_replication_repl_store',
        'ls_replication_repl_store_tender_type',
        'ls_replication_repl_tax_setup',
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
     */
    public function execute()
    {
        $jobName             = $this->_request->getParam('jobname');
        $scopeId             = $this->_request->getParam('store');
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
        $arguments = ['store' => $scopeId];

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
     */
    public function resetAllCronsData($scopeId, $coreConfigTableName)
    {
        foreach (array_merge(self::LS_ITEM_RELATED_TABLES, self::LS_TABLES) as $lsTable) {
            $tableName = $this->replicationHelper->getGivenTableName($lsTable);

            if ($scopeId) {
                $this->replicationHelper->deleteGivenTableDataGivenConditions($tableName, ['scope_id = ?' => $scopeId]);
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
