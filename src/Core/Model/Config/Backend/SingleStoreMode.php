<?php

namespace Ls\Core\Model\Config\Backend;

use Exception;
use \Ls\Core\Model\LSR;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;

/**
 * Class single store mode to change scope
 */
class SingleStoreMode extends Value
{
    /** @var LSR */
    public $lsr;

    /**
     * @var ResourceConnection
     */
    public $resourceConnection;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param LSR $lsr
     * @param ResourceConnection $resourceConnection
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        LSR $lsr,
        ResourceConnection $resourceConnection,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->lsr                = $lsr;
        $this->resourceConnection = $resourceConnection;

        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Path that need to be update when single store mode updated
     */
    public $tableCoreConfig = [
        LSR::SC_SERVICE_BASE_URL,
        LSR::SC_SERVICE_LS_KEY,
        LSR::SC_SERVICE_LS_CENTRAL_VERSION,
        LSR::SC_SERVICE_VERSION,
        LSR::SC_SERVICE_STORE,
        LSR::SC_REPLICATION_HIERARCHY_CODE,
        LSR::LSR_PAYMENT_TENDER_TYPE_MAPPING,
    ];

    /**
     * check single store mode value
     *
     * @return SingleStoreMode
     */
    public function afterSave()
    {
        if ($this->isValueChanged() && $this->lsr->getStoreManagerObject()->hasSingleStore()) {
            if ($this->getValue() == 1) {
                $scope_id       = 0;
                $scope          = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
                $oldScope       = ScopeInterface::SCOPE_STORES;
                $oldScopeConfig = ScopeInterface::SCOPE_WEBSITES;
                $websites       = $scope;
            } else {
                $stores         = $this->lsr->getAllStores();
                $store          = reset($stores);
                $scope_id       = $store->getId();
                $scope          = ScopeInterface::SCOPE_STORES;
                $websites       = ScopeInterface::SCOPE_WEBSITES;
                $oldScope       = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
                $oldScopeConfig = $oldScope;
            }

            $connection          = $this->resourceConnection->getConnection(
                ResourceConnection::DEFAULT_CONNECTION
            );
            $coreConfigTableName = $this->resourceConnection->getTableName('core_config_data');
            $connection->startSetup();
            foreach (LSR::LS_TABLES as $lsTables) {
                $tableName = $this->resourceConnection->getTableName($lsTables);

                try {
                    $data = [
                        'scope_id' => $scope_id,
                        'scope'    => $scope
                    ];
                    $connection->update($tableName, $data, ['scope = ?' => $oldScope]);
                } catch (Exception $e) {
                    $this->_logger->error($e->getMessage());
                }
            }
            foreach ($this->tableCoreConfig as $config) {
                $data = [
                    'scope_id' => $scope_id,
                    'scope'    => $websites
                ];
                $connection->update(
                    $coreConfigTableName,
                    $data,
                    [
                        'path = ?'  => $config,
                        'scope = ?' => $oldScopeConfig
                    ]
                );
            }

            $connection->endSetup();
        }

        return parent::afterSave();
    }
}
