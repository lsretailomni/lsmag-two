<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Replication\Cron;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Config\Model\ResourceModel\Config;
use Ls\Core\Helper\Data as LsHelper;
use Ls\Replication\Helper\ReplicationHelper;
use Ls\Omni\Client\Ecommerce\Entity\ReplRequest;
use Ls\Omni\Client\Ecommerce\Operation\ReplEcommHierarchy;
use Ls\Replication\Api\ReplHierarchyRepositoryInterface as ReplHierarchyRepository;
use Ls\Replication\Model\ReplHierarchyFactory;
use Ls\Replication\Api\Data\ReplHierarchyInterface;

class ReplEcommHierarchyTask extends AbstractReplicationTask
{

    const JOB_CODE = 'replication_repl_hierarchy';

    const CONFIG_PATH = 'ls_mag/replication/repl_hierarchy';

    const CONFIG_PATH_STATUS = 'ls_mag/replication/status_repl_hierarchy';

    const CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_hierarchy';

    /**
     * @property ReplHierarchyRepository $repository
     */
    protected $repository = null;

    /**
     * @property ReplHierarchyFactory $factory
     */
    protected $factory = null;

    /**
     * @property ReplHierarchyInterface $data_interface
     */
    protected $data_interface = null;

    /**
     * @param ReplHierarchyRepository $repository
     * @return $this
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;
        return $this;
    }

    /**
     * @return ReplHierarchyRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param ReplHierarchyFactory $factory
     * @return $this
     */
    public function setFactory($factory)
    {
        $this->factory = $factory;
        return $this;
    }

    /**
     * @return ReplHierarchyFactory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @param ReplHierarchyInterface $data_interface
     * @return $this
     */
    public function setDataInterface($data_interface)
    {
        $this->data_interface = $data_interface;
        return $this;
    }

    /**
     * @return ReplHierarchyInterface
     */
    public function getDataInterface()
    {
        return $this->data_interface;
    }

    public function __construct(ScopeConfigInterface $scope_config, Config $resource_config, LoggerInterface $logger, LsHelper $helper, ReplicationHelper $repHelper, ReplHierarchyFactory $factory, ReplHierarchyRepository $repository, ReplHierarchyInterface $data_interface)
    {
        parent::__construct($scope_config, $resource_config, $logger, $helper, $repHelper);
        $this->repository = $repository;
        $this->factory = $factory;
        $this->data_interface = $data_interface;
    }

    public function makeRequest($last_key, $full_replication = false, $batchsize = 100, $storeId = '')
    {
        $request = new ReplEcommHierarchy();
        $request->getOperationInput()
                 ->setReplRequest( ( new ReplRequest() )->setBatchSize($batchsize)
                                                        ->setFullReplication($full_replication)
                                                        ->setLastKey($last_key)
                                                        ->setStoreId($storeId));
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

    public function getMainEntity()
    {
        return $this->data_interface;
    }


}

