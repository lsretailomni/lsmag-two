<?php

namespace Ls\Replication\Cron;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\Data;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

class ReplDataTranslationLanguageCodeTask
{
    public const JOB_CODE = 'repl_data_translation_lang_code';

    /**
     * @var StoreInterface
     */
    public $store;

    /**
     * @param Logger $logger
     * @param ReplicationHelper $replicationHelper
     * @param LSR $lsr
     * @param Data $dataHelper
     * @param Json $jsonEncoder
     */
    public function __construct(
        public Logger $logger,
        public ReplicationHelper $replicationHelper,
        public LSR $lsr,
        public Data $dataHelper,
        public Json $jsonEncoder
    ) {
    }

    /**
     * Entry point when running manually
     *
     * @param $storeData
     * @return int[]
     * @throws GuzzleException
     * @throws NoSuchEntityException
     */
    public function executeManually($storeData = null)
    {
        $this->execute($storeData);
        return [0];
    }

    /**
     * Entry point when running using cron
     *
     * @param $storeData
     * @return void
     * @throws NoSuchEntityException
     * @throws GuzzleException
     */
    public function execute($storeData = null)
    {
        if (!$this->lsr->isSSM()) {
            if (!empty($storeData) && $storeData instanceof StoreInterface) {
                $stores = [$storeData];
            } else {
                $stores = $this->lsr->getAllStores();
            }
        } else {
            $stores = [$this->lsr->getAdminStore()];
        }

        if (!empty($stores)) {
            foreach ($stores as $store) {
                $this->lsr->setStoreId($store->getId());
                $this->store = $store;
                if ($this->lsr->isLSR($this->store->getId())) {
                    $this->replicationHelper->updateConfigValue(
                        $this->replicationHelper->getDateTime(),
                        'ls_mag/replication/last_execute_'. self::JOB_CODE,
                        $store->getId(),
                        ScopeInterface::SCOPE_STORES
                    );
                    $languageCode = $this->dataHelper->fetchGivenTableData(
                        'LSC Data Translation Language'
                    );
                    $newLanguageCodes = [];
                    foreach ($languageCode as $langCode) {
                        $newLanguageCodes[] = $langCode['Language Code'];
                    }

                    $availableLanguageCodes = $this->getAvailableLanguageCode($store->getId());
                    sort($availableLanguageCodes);
                    sort($newLanguageCodes);

                    if ($availableLanguageCodes !== $newLanguageCodes) {
                        $serializedStr = $this->jsonEncoder->serialize($languageCode);
                        $this->replicationHelper->updateConfigValue(
                            $serializedStr,
                            LSR::SC_STORE_REPLICATED_DATA_TRANSLATION_LANG_CODE,
                            $store->getId(),
                            ScopeInterface::SCOPE_STORES
                        );

                        $this->replicationHelper->flushByTypeCode('config');
                    }

                    $this->replicationHelper->updateConfigValue(
                        1,
                        LSR::CRON_STATUS_PATH_PREFIX. self::JOB_CODE,
                        $store->getId(),
                        ScopeInterface::SCOPE_STORES
                    );
                }
                $this->lsr->setStoreId(null);
            }
        }
    }

    public function getAvailableLanguageCode($storeId)
    {
        $serializedStr = $this->lsr->getStoreConfig(
            LSR::SC_STORE_REPLICATED_DATA_TRANSLATION_LANG_CODE,
            $storeId
        );
        $languageCodes = [];
        if (!empty($serializedStr)) {
            $records = $this->jsonEncoder->unserialize($serializedStr);

            foreach ($records as $record) {
                $languageCodes[] = $record['Language Code'];
            }
        }

        return $languageCodes;
    }
}
