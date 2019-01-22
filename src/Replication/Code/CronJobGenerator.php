<?php
// @codingStandardsIgnoreFile

namespace Ls\Replication\Code;

use Ls\Core\Code\AbstractGenerator;
use Ls\Core\Helper\Data as LsHelper;
use Ls\Omni\Client\Ecommerce\Entity\ReplRequest;
use Ls\Omni\Service\Soap\ReplicationOperation;
use Ls\Replication\Cron\AbstractReplicationTask;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Magento\Config\Model\ResourceModel\Config;
use Ls\Replication\Helper\ReplicationHelper;

/**
 * Class CronJobGenerator
 * @package Ls\Replication\Code
 */
class CronJobGenerator extends AbstractGenerator
{

    /** @var ReplicationOperation */
    public $operation;

    /**
     * CronJobGenerator constructor.
     *
     * @param ReplicationOperation $operation
     */
    public function __construct(ReplicationOperation $operation)
    {
        parent::__construct();
        $this->operation = $operation;
    }

    /**
     * @return string
     */
    public function generate()
    {
        $this->class->setName($this->operation->getJobName());
        $this->class->setNamespaceName($this->operation->getJobNamespace());
        $this->class->setExtendedClass(AbstractReplicationTask::class);

        $this->class->addUse(LoggerInterface::class);
        $this->class->addUse(ScopeConfigInterface::class);
        $this->class->addUse(Config::class);
        $this->class->addUse(LsHelper::class, 'LsHelper');
        $this->class->addUse(ReplicationHelper::class);
        $this->class->addUse(ReplRequest::class);
        $this->class->addUse($this->operation->getOperationFqn());
        $this->class->addUse($this->operation->getRepositoryInterfaceFqn(), $this->operation->getRepositoryName());
        $this->class->addUse($this->operation->getFactoryFqn());
        $this->class->addUse($this->operation->getInterfaceFqn());

        $this->class->addConstant('JOB_CODE', $this->operation->getJobId());
        $this->class->addConstant('CONFIG_PATH', "ls_mag/replication/{$this->operation->getTableName()}");
        $this->class->addConstant('CONFIG_PATH_STATUS', "ls_mag/replication/status_{$this->operation->getTableName()}");

        $this->createProperty('repository', $this->operation->getRepositoryName());
        $this->createProperty('factory', $this->operation->getFactoryName());
        $this->createProperty('dataInterface', $this->operation->getInterfaceName());

        $repository_name = $this->operation->getRepositoryName();
        $factory_name = $this->operation->getFactoryName();
        $data_interface = $this->operation->getInterfaceName();

        $this->class->addMethodFromGenerator($this->getConstructor());
        $this->class->addMethodFromGenerator($this->getMakeRequest());
        $this->class->addMethodFromGenerator($this->getConfigPath());
        $this->class->addMethodFromGenerator($this->getConfigPathStatus());
        $this->class->addMethodFromGenerator($this->getMainEntity());

        $content = $this->file->generate();

        $content = str_replace(
            'extends Ls\\Replication\\Cron\\AbstractReplicationTask',
            'extends AbstractReplicationTask',
            $content
        );

        // removing slashes from the ScopeConfigInterface -- Same for all
        $content = str_replace('\ScopeConfigInterface $scope_config', 'ScopeConfigInterface $scope_config', $content);

        // removing the slashes from \Config -- Same for All
        $content = str_replace('\Config $resource_config', 'Config $resource_config', $content);

        // removing the slashes from \LoggerInterface -- Same for All
        $content = str_replace('\LoggerInterface $logger', 'LoggerInterface $logger', $content);

        // removing the slashes from \LsHelper -- Same for All
        $content = str_replace('\LsHelper $helper', 'LsHelper $helper', $content);

        // removing the slashes from \ReplicationHelper -- Same for All
        $content = str_replace('\ReplicationHelper $repHelper', 'ReplicationHelper $repHelper', $content);

        // removing slashes from \$classnameFactory --- Dynamic different
        $content = str_replace("\\{$factory_name} \$factory", "{$factory_name} \$factory", $content);

        // removing slashes from \$classnameRepository --- Dynamic different
        $content = str_replace("\\{$repository_name} \$repository", "{$repository_name} \$repository", $content);

        // removing slashes from \$apiDataInterface --- Dynamic different
        $content = str_replace("\\{$data_interface} \$data_interface", "{$data_interface} \$data_interface", $content);

        // removing the slashes from \Config -- Same for All
        return $content;
    }

    /**
     * @return MethodGenerator
     */
    private function getConstructor()
    {
        $constructor = new MethodGenerator();
        $constructor->setName('__construct')
            ->setVisibility(MethodGenerator::FLAG_PUBLIC);
        $constructor->setParameters([
            new ParameterGenerator('scope_config', 'ScopeConfigInterface'),
            new ParameterGenerator('resource_config', 'Config'),
            new ParameterGenerator('logger', 'LoggerInterface'),
            new ParameterGenerator('helper', 'LsHelper'),
            new ParameterGenerator('repHelper', 'ReplicationHelper'),
            new ParameterGenerator('factory', $this->operation->getFactoryName()),
            new ParameterGenerator('repository', $this->operation->getRepositoryName()),
            new ParameterGenerator('data_interface', $this->operation->getInterfaceName())
        ]);
        $constructor->setBody(<<<CODE
parent::__construct(\$scope_config, \$resource_config, \$logger, \$helper, \$repHelper);
\$this->repository = \$repository;
\$this->factory = \$factory;
\$this->data_interface = \$data_interface;
CODE
        );

        return $constructor;
    }

    /**
     * @return MethodGenerator
     */
    private function getMakeRequest()
    {
        $make_request = new MethodGenerator();
        $make_request->setName('makeRequest')
            ->setVisibility(MethodGenerator::FLAG_PROTECTED);
        $make_request->setParameters([new ParameterGenerator('last_key')]);
        //making full replication dynamic instead of making it true all the time.
        // @see https://solutions.lsretail.com/jira/browse/OMNI-4508
        $make_request->setParameters([new ParameterGenerator('full_replication', null, false)]);
        // making batchsize dynamic and setting the default value to 100
        $make_request->setParameters([new ParameterGenerator('batchsize', null, 100)]);
        // setting storeid for those which requorire
        $make_request->setParameters([new ParameterGenerator('storeId', null, '')]);
        $make_request->setBody(<<<CODE
\$request = new {$this->operation->getName()}();
\$request->getOperationInput()
         ->setReplRequest( ( new ReplRequest() )->setBatchSize(\$batchsize)
                                                ->setFullReplication(\$full_replication)
                                                ->setLastKey(\$last_key)
                                                ->setStoreId(\$storeId));
return \$request;
CODE
        );

        return $make_request;
    }

    private function getConfigPath()
    {
        $config_path = new MethodGenerator();
        $config_path->setName('getConfigPath')
            ->setVisibility(MethodGenerator::FLAG_PROTECTED);
        $config_path->setBody(<<<CODE
return self::CONFIG_PATH;
CODE
        );

        return $config_path;
    }

    private function getConfigPathStatus()
    {
        $config_path = new MethodGenerator();
        $config_path->setName('getConfigPathStatus')
            ->setVisibility(MethodGenerator::FLAG_PROTECTED);
        $config_path->setBody(<<<CODE
return self::CONFIG_PATH_STATUS;
CODE
        );

        return $config_path;
    }

    private function getMainEntity()
    {
        $config_path = new MethodGenerator();
        $config_path->setName('getMainEntity')
            ->setVisibility(MethodGenerator::FLAG_PROTECTED);
        $main_entity = $this->operation->getEntityName();
        $config_path->setBody(<<<CODE
return \$this->data_interface;
CODE
        );

        return $config_path;
    }
}
