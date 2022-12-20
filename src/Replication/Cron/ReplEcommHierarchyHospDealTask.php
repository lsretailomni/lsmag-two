<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Replication\Cron;

use Ls\Replication\Logger\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Config\Model\ResourceModel\Config;
use Ls\Core\Helper\Data as LsHelper;
use Ls\Replication\Helper\ReplicationHelper;
use Ls\Omni\Client\Ecommerce\Entity\ReplRequest;
use Ls\Omni\Client\Ecommerce\Operation\ReplEcommHierarchyHospDeal;
use Ls\Replication\Api\ReplHierarchyHospDealRepositoryInterface as ReplHierarchyHospDealRepository;
use Ls\Replication\Model\ReplHierarchyHospDealFactory;
use Ls\Replication\Api\Data\ReplHierarchyHospDealInterface;

class ReplEcommHierarchyHospDealTask extends AbstractReplicationTask
{

    public const JOB_CODE = 'replication_repl_hierarchy_hosp_deal';

    public const CONFIG_PATH = 'ls_mag/replication/repl_hierarchy_hosp_deal';

    public const CONFIG_PATH_STATUS = 'ls_mag/replication/status_repl_hierarchy_hosp_deal';

    public const CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_hierarchy_hosp_deal';

    public const CONFIG_PATH_MAX_KEY = 'ls_mag/replication/max_key_repl_hierarchy_hosp_deal';

    public const CONFIG_PATH_APP_ID = 'ls_mag/replication/app_id_repl_hierarchy_hosp_deal';

    /**
     * @property ReplHierarchyHospDealRepository $repository
     */
    protected $repository = null;

    /**
     * @property ReplHierarchyHospDealFactory $factory
     */
    protected $factory = null;

    /**
     * @property ReplHierarchyHospDealInterface $data_interface
     */
    protected $data_interface = null;

    /**
     * @param ReplHierarchyHospDealRepository $repository
     * @return $this
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;
        return $this;
    }

    /**
     * @return ReplHierarchyHospDealRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param ReplHierarchyHospDealFactory $factory
     * @return $this
     */
    public function setFactory($factory)
    {
        $this->factory = $factory;
        return $this;
    }

    /**
     * @return ReplHierarchyHospDealFactory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @param ReplHierarchyHospDealInterface $data_interface
     * @return $this
     */
    public function setDataInterface($data_interface)
    {
        $this->data_interface = $data_interface;
        return $this;
    }

    /**
     * @return ReplHierarchyHospDealInterface
     */
    public function getDataInterface()
    {
        return $this->data_interface;
    }

    public function __construct(ScopeConfigInterface $scope_config, Config $resource_config, Logger $logger, LsHelper $helper, ReplicationHelper $repHelper, ReplHierarchyHospDealFactory $factory, ReplHierarchyHospDealRepository $repository, ReplHierarchyHospDealInterface $data_interface)
    {
        parent::__construct($scope_config, $resource_config, $logger, $helper, $repHelper);
        $this->repository = $repository;
        $this->factory = $factory;
        $this->data_interface = $data_interface;
    }

    public function makeRequest($lastKey, $fullReplication = false, $batchSize = 100, $storeId = '', $maxKey = '', $baseUrl = '', $appId = '')
    {
        $request = new ReplEcommHierarchyHospDeal($baseUrl);
        $request->getOperationInput()
                 ->setReplRequest( ( new ReplRequest() )->setBatchSize($batchSize)
                                                        ->setFullReplication($fullReplication)
                                                        ->setLastKey($lastKey)
                                                        ->setMaxKey($maxKey)
                                                        ->setStoreId($storeId)
                                                        ->setAppId($appId));
        return $request;
    }

    public function getConfigPath()
    {
        return self::CONFIG_PATH;
    }

    public function getConfigPathStatus()
    {
        return self::CONFIG_PATH_STATUS;
    }

    public function getConfigPathLastExecute()
    {
        return self::CONFIG_PATH_LAST_EXECUTE;
    }

    public function getConfigPathMaxKey()
    {
        return self::CONFIG_PATH_MAX_KEY;
    }

    public function getConfigPathAppId()
    {
        return self::CONFIG_PATH_APP_ID;
    }

    public function getMainEntity()
    {
        return $this->data_interface;
    }


}

