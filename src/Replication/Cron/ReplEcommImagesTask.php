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
use Ls\Omni\Client\Ecommerce\Operation\ReplEcommImages;
use Ls\Replication\Api\ReplImageRepositoryInterface as ReplImageRepository;
use Ls\Replication\Model\ReplImageFactory;
use Ls\Replication\Api\Data\ReplImageInterface;

class ReplEcommImagesTask extends AbstractReplicationTask
{

    const JOB_CODE = 'replication_repl_image';

    const CONFIG_PATH = 'ls_mag/replication/repl_image';

    const CONFIG_PATH_STATUS = 'ls_mag/replication/status_repl_image';

    /**
     * @property ReplImageRepository $repository
     */
    protected $repository = null;

    /**
     * @property ReplImageFactory $factory
     */
    protected $factory = null;

    /**
     * @property ReplImageInterface $data_interface
     */
    protected $data_interface = null;

    /**
     * @param ReplImageRepository $repository
     * @return $this
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;
        return $this;
    }

    /**
     * @return ReplImageRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param ReplImageFactory $factory
     * @return $this
     */
    public function setFactory($factory)
    {
        $this->factory = $factory;
        return $this;
    }

    /**
     * @return ReplImageFactory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @param ReplImageInterface $data_interface
     * @return $this
     */
    public function setDataInterface($data_interface)
    {
        $this->data_interface = $data_interface;
        return $this;
    }

    /**
     * @return ReplImageInterface
     */
    public function getDataInterface()
    {
        return $this->data_interface;
    }

    public function __construct(ScopeConfigInterface $scope_config, Config $resource_config, LoggerInterface $logger, LsHelper $helper, ReplicationHelper $repHelper, ReplImageFactory $factory, ReplImageRepository $repository, ReplImageInterface $data_interface)
    {
        parent::__construct($scope_config, $resource_config, $logger, $helper, $repHelper);
        $this->repository = $repository;
        $this->factory = $factory;
        $this->data_interface = $data_interface;
    }

    public function makeRequest($last_key, $full_replication = false, $batchsize = 100, $storeId = '')
    {
        $request = new ReplEcommImages();
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

    public function getMainEntity()
    {
        return $this->data_interface;
    }


}

