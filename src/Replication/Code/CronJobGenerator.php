<?php
declare(strict_types=1);

namespace Ls\Replication\Code;

use Exception;
use Laminas\Code\Generator\PropertyGenerator;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Core\Model\Data as LsHelper;
use \Ls\Omni\Client\Ecommerce\Entity\ReplRequest;
use \Ls\Omni\Service\Soap\ReplicationOperation;
use \Ls\Replication\Cron\AbstractReplicationTask;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;

/**
 * Generator for replication cron job class.
 */
class CronJobGenerator extends AbstractGenerator
{
    /**
     * Replication operation instance.
     *
     * @var ReplicationOperation
     */
    public ReplicationOperation $operation;

    /**
     * CronJobGenerator constructor.
     *
     * @param ReplicationOperation $operation
     * @throws Exception
     */
    public function __construct(ReplicationOperation $operation)
    {
        parent::__construct();
        $this->operation = $operation;
    }

    /**
     * Generate the cron job class content.
     *
     * @return string
     */
    public function generate(): string
    {
        $this->class->setName($this->operation->getJobName());
        $this->class->setNamespaceName($this->operation->getJobNamespace());
        $this->class->setExtendedClass(AbstractReplicationTask::class);

        $this->class->addUse(Logger::class);
        $this->class->addUse(ScopeConfigInterface::class);
        $this->class->addUse(Config::class);
        $this->class->addUse(LsHelper::class, 'LsHelper');
        $this->class->addUse(ReplicationHelper::class);
        $this->class->addUse(ReplRequest::class);
        $this->class->addUse($this->operation->getOperationFqn());
        $this->class->addUse($this->operation->getRepositoryInterfaceFqn(), $this->operation->getRepositoryName());
        $this->class->addUse($this->operation->getFactoryFqn());
        $this->class->addUse($this->operation->getInterfaceFqn());

        $tableName = $this->operation->getIdenticalTableCronJob($this->operation->getName());
        $jobCode = $tableName ? 'replication_' . $tableName : $this->operation->getJobId();
        $tableName = $tableName ?: $this->operation->getTableName();

        $this->class->addConstant('JOB_CODE', $jobCode);
        $this->class->addConstant('CONFIG_PATH', "ls_mag/replication/{$tableName}");
        $this->class->addConstant('CONFIG_PATH_STATUS', "ls_mag/replication/status_{$tableName}");
        $this->class->addConstant('CONFIG_PATH_LAST_EXECUTE', "ls_mag/replication/last_execute_{$tableName}");
        $this->class->addConstant('CONFIG_PATH_MAX_KEY', "ls_mag/replication/max_key_{$tableName}");
        $this->class->addConstant('CONFIG_PATH_APP_ID', "ls_mag/replication/app_id_{$tableName}");

        $this->createProperty(
            'repository',
            $this->operation->getRepositoryName(),
            [PropertyGenerator::FLAG_PROTECTED],
            [],
            true
        );
        $this->createProperty(
            'factory',
            $this->operation->getFactoryName(),
            [PropertyGenerator::FLAG_PROTECTED],
            [],
            true
        );
        $this->createProperty(
            'dataInterface',
            $this->operation->getInterfaceName(),
            [PropertyGenerator::FLAG_PROTECTED],
            [],
            true
        );

        $repositoryName = $this->operation->getRepositoryName();
        $factoryName = $this->operation->getFactoryName();
        $dataInterface = $this->operation->getInterfaceName();

        $this->class->addMethodFromGenerator($this->getConstructor());
        $this->class->addMethodFromGenerator($this->getMakeRequest());
        $this->class->addMethodFromGenerator($this->getConfigPath());
        $this->class->addMethodFromGenerator($this->getConfigPathStatus());
        $this->class->addMethodFromGenerator($this->getConfigPathLastExecute());
        $this->class->addMethodFromGenerator($this->getConfigPathMaxKey());
        $this->class->addMethodFromGenerator($this->getConfigPathAppId());
        $this->class->addMethodFromGenerator($this->getMainEntity());

        $content = $this->file->generate();

        // Cleanup generated file content
        $content = str_replace(
            'extends Ls\\Replication\\Cron\\AbstractReplicationTask',
            'extends AbstractReplicationTask',
            $content
        );

        // Cleanup slashes from common type hints
        $replaceMap = [
            '\ScopeConfigInterface $scope_config' => 'ScopeConfigInterface $scope_config',
            '\Config $resource_config' => 'Config $resource_config',
            '\LsHelper $helper' => 'LsHelper $helper',
            "\\{$factoryName} \$factory" => "{$factoryName} \$factory",
            "\\{$repositoryName} \$repository" => "{$repositoryName} \$repository",
            "\\{$dataInterface} \$dataInterface" => "{$dataInterface} \$dataInterface",
            ": \\{$dataInterface}" => ": {$dataInterface}",
            ": \\{$repositoryName}" => ": {$repositoryName}",
            ": \\{$factoryName}" => ": {$factoryName}"
        ];

        $content = str_replace(array_keys($replaceMap), array_values($replaceMap), $content);

        return $content;
    }

    /**
     * Generate constructor method.
     *
     * @return MethodGenerator
     */
    private function getConstructor(): MethodGenerator
    {
        $constructor = new MethodGenerator();
        $constructor->setName('__construct')
            ->setVisibility(MethodGenerator::FLAG_PUBLIC);

        $constructor->setParameters([
            new ParameterGenerator('scopeConfig', ScopeConfigInterface::class),
            new ParameterGenerator('resourceConfig', Config::class),
            new ParameterGenerator('logger', Logger::class),
            new ParameterGenerator('helper', 'LsHelper'),
            new ParameterGenerator('repHelper', ReplicationHelper::class),
            new ParameterGenerator('factory', $this->operation->getFactoryName()),
            new ParameterGenerator('repository', $this->operation->getRepositoryName()),
            new ParameterGenerator('dataInterface', $this->operation->getInterfaceName())
        ]);

        $constructor->setBody(<<<'CODE'
parent::__construct($scopeConfig, $resourceConfig, $logger, $helper, $repHelper);
$this->repository = $repository;
$this->factory = $factory;
$this->dataInterface = $dataInterface;
CODE
        );

        return $constructor;
    }

    /**
     * Generate makeRequest method for building the replication request.
     *
     * @return MethodGenerator
     */
    private function getMakeRequest(): MethodGenerator
    {
        $makeRequest = new MethodGenerator();
        $makeRequest->setName('makeRequest')
            ->setVisibility(MethodGenerator::FLAG_PROTECTED);
        $makeRequest->setParameters([
            new ParameterGenerator('baseUrl', 'string', ''),
            new ParameterGenerator('connectionParams', 'array', []),
            new ParameterGenerator('companyName', 'string', ''),
            new ParameterGenerator('fullRepl', 'bool', false),
            new ParameterGenerator('batchSize', 'int', 100),
            new ParameterGenerator('storeNo', 'string', ''),
            new ParameterGenerator('lastEntryNo', 'int', 0),
            new ParameterGenerator('lastKey', 'string', '')
        ]);

        $makeRequest->setBody(<<<CODE
\$request = new \\{$this->operation->getOperationFqn()}(\$baseUrl, \$connectionParams, \$companyName);
\$request->setOperationInput([
'storeNo' => \$storeNo,
'batchSize' => \$batchSize,
'fullRepl' => \$fullRepl,
'lastEntryNo' => \$lastEntryNo,
'lastKey' => \$lastKey
]);
return \$request;
CODE
        );

        return $makeRequest;
    }

    /**
     * Get config path constant.
     *
     * @return MethodGenerator
     */
    private function getConfigPath(): MethodGenerator
    {
        return (new MethodGenerator())
            ->setName('getConfigPath')
            ->setVisibility(MethodGenerator::FLAG_PROTECTED)
            ->setBody('return self::CONFIG_PATH;');
    }

    /**
     * Get status config path constant.
     *
     * @return MethodGenerator
     */
    private function getConfigPathStatus(): MethodGenerator
    {
        return (new MethodGenerator())
            ->setName('getConfigPathStatus')
            ->setVisibility(MethodGenerator::FLAG_PROTECTED)
            ->setBody('return self::CONFIG_PATH_STATUS;');
    }

    /**
     * Get last execute config path constant.
     *
     * @return MethodGenerator
     */
    private function getConfigPathLastExecute(): MethodGenerator
    {
        return (new MethodGenerator())
            ->setName('getConfigPathLastExecute')
            ->setVisibility(MethodGenerator::FLAG_PROTECTED)
            ->setBody('return self::CONFIG_PATH_LAST_EXECUTE;');
    }

    /**
     * Get max key config path constant.
     *
     * @return MethodGenerator
     */
    private function getConfigPathMaxKey(): MethodGenerator
    {
        return (new MethodGenerator())
            ->setName('getConfigPathMaxKey')
            ->setVisibility(MethodGenerator::FLAG_PROTECTED)
            ->setBody('return self::CONFIG_PATH_MAX_KEY;');
    }

    /**
     * Get app ID config path constant.
     *
     * @return MethodGenerator
     */
    private function getConfigPathAppId(): MethodGenerator
    {
        return (new MethodGenerator())
            ->setName('getConfigPathAppId')
            ->setVisibility(MethodGenerator::FLAG_PROTECTED)
            ->setBody('return self::CONFIG_PATH_APP_ID;');
    }

    /**
     * Get main entity reference.
     *
     * @return MethodGenerator
     */
    private function getMainEntity(): MethodGenerator
    {
        return (new MethodGenerator())
            ->setName('getMainEntity')
            ->setVisibility(MethodGenerator::FLAG_PROTECTED)
            ->setBody('return $this->dataInterface;');
    }
}
