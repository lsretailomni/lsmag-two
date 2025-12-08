<?php
declare(strict_types=1);

namespace Ls\Replication\Code;

use Exception;
use Laminas\Code\Generator\PropertyGenerator;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Core\Model\Data as LsHelper;
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
     * @param ReplicationOperation $operation
     * @throws Exception
     */
    public function __construct(public ReplicationOperation $operation)
    {
        parent::__construct();
    }

    /**
     * Generate the cron job class content.
     *
     * @return string
     */
    public function generate(): string
    {
        $replicationEntityMapping = ReplicationHelper::CRON_JOBS_MAPPING;
        $modelName = $this->operation->getModelName();
        $mappingExists = false;
        $mappedModelName = $modelName;
        if (isset($replicationEntityMapping[$this->operation->getName(true)])) {
            $mappingExists = true;
            $mappedModelName = $replicationEntityMapping[$this->operation->getName(true)];            
        }
        $this->class->setName($this->operation->getJobName());
        $this->class->setNamespaceName($this->operation->getJobNamespace());
        $this->class->setExtendedClass(
            $mappingExists ?
                $this->getMappedCronName($mappedModelName) :
                AbstractReplicationTask::class
        );

        $tableName = $this->operation->getIdenticalTableCronJob($this->operation->getName());
        $jobCode = $tableName ? 'replication_' . $tableName : $this->operation->getJobId();
        $tableName = $tableName ?: ($mappingExists ?
            strtolower($this->caseHelper->toSnakeCase('Repl'. $mappedModelName)) : $this->operation->getTableName()
        );

        if (!$mappingExists) {
            $this->class->addUse(LsHelper::class, 'LsHelper');
            $this->class->addUse($this->operation->getRepositoryInterfaceFqn(), $this->operation->getRepositoryName());
            $this->class->addUse($this->operation->getFactoryFqn());
            $this->class->addUse($this->operation->getInterfaceFqn());

            $this->class->addConstant('JOB_CODE', $jobCode);
            $this->class->addConstant('CONFIG_PATH', "ls_mag/replication/{$tableName}");
            $this->class->addConstant('CONFIG_PATH_STATUS', "ls_mag/replication/status_{$tableName}");
            $this->class->addConstant('CONFIG_PATH_LAST_EXECUTE', "ls_mag/replication/last_execute_{$tableName}");
        } else {
            $dbTablesMapping = ReplicationHelper::DB_TABLES_MAPPING;
            if (str_starts_with($jobCode, 'replication_')) {
                $tName = substr($jobCode, strlen('replication_'));

                if (isset($dbTablesMapping[$tName])) {
                    $tableName = $dbTablesMapping[$tName]['table_name'];
                }
            }
        }

        $this->class->addConstant('CONFIG_PATH_LAST_ENTRY_NO', "ls_mag/replication/last_entry_no_{$tableName}");
        $this->class->addConstant('MODEL_CLASS', $this->getMainEntityFqn($modelName));

        if (!$mappingExists) {
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
        }

        $repositoryName = $this->operation->getRepositoryName();
        $factoryName = $this->operation->getFactoryName();
        $dataInterface = $this->operation->getInterfaceName();

        if (!$mappingExists) {
            $this->class->addMethodFromGenerator($this->getConstructor());
            $this->class->addMethodFromGenerator($this->getMainEntity());
            $this->class->addMethodFromGenerator($this->getConfigPath());
            $this->class->addMethodFromGenerator($this->getConfigPathStatus());
            $this->class->addMethodFromGenerator($this->getConfigPathLastExecute());
        }
        $this->class->addMethodFromGenerator($this->getModelName());
        $this->class->addMethodFromGenerator($this->getMakeRequest());
        $this->class->addMethodFromGenerator($this->getConfigPathLastEntryNo());

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
            ": \\{$factoryName}" => ": {$factoryName}",
            "\\{$this->getMappedCronName($mappedModelName)}" => "{$this->getMappedCronName($mappedModelName)}"
        ];

        return str_replace(array_keys($replaceMap), array_values($replaceMap), $content);
    }

    /**
     * Generate constructor method.
     *
     * @return MethodGenerator
     */
    public function getConstructor(): MethodGenerator
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
    public function getMakeRequest(): MethodGenerator
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
    public function getConfigPath(): MethodGenerator
    {
        return (new MethodGenerator())
            ->setName('getConfigPath')
            ->setVisibility(MethodGenerator::FLAG_PROTECTED)
            ->setBody('return self::CONFIG_PATH;')
            ->setReturnType('string');
    }

    /**
     * Get status config path constant.
     *
     * @return MethodGenerator
     */
    public function getConfigPathStatus(): MethodGenerator
    {
        return (new MethodGenerator())
            ->setName('getConfigPathStatus')
            ->setVisibility(MethodGenerator::FLAG_PROTECTED)
            ->setBody('return self::CONFIG_PATH_STATUS;')
            ->setReturnType('string');
    }

    /**
     * Get last execute config path constant.
     *
     * @return MethodGenerator
     */
    public function getConfigPathLastExecute(): MethodGenerator
    {
        return (new MethodGenerator())
            ->setName('getConfigPathLastExecute')
            ->setVisibility(MethodGenerator::FLAG_PROTECTED)
            ->setBody('return self::CONFIG_PATH_LAST_EXECUTE;')
            ->setReturnType('string');
    }

    /**
     * Get last entry no config path constant.
     *
     * @return MethodGenerator
     */
    public function getConfigPathLastEntryNo(): MethodGenerator
    {
        return (new MethodGenerator())
            ->setName('getConfigPathLastEntryNo')
            ->setVisibility(MethodGenerator::FLAG_PROTECTED)
            ->setBody('return self::CONFIG_PATH_LAST_ENTRY_NO;')
            ->setReturnType('string');
    }

    /**
     * Get last entry no config path constant.
     *
     * @return MethodGenerator
     */
    public function getModelName(): MethodGenerator
    {
        return (new MethodGenerator())
            ->setName('getModelName')
            ->setVisibility(MethodGenerator::FLAG_PROTECTED)
            ->setBody('return self::MODEL_CLASS;')
            ->setReturnType('string');
    }

    /**
     * Get main entity reference.
     *
     * @return MethodGenerator
     */
    public function getMainEntity(): MethodGenerator
    {
        return (new MethodGenerator())
            ->setName('getMainEntity')
            ->setVisibility(MethodGenerator::FLAG_PROTECTED)
            ->setBody('return $this->dataInterface;')
            ->setReturnType($this->operation->getInterfaceName());
    }

    /**
     * Get cron job class name
     *
     * @param $mappedModel
     * @return string
     */
    public function getMappedCronName($mappedModel): string
    {
        return 'Repl'. 'Ecomm'. $mappedModel . 'Task';
    }

    /**
     * Get fully qualified name of model
     *
     * @param $mappedModel
     * @return string
     */
    public function getMainEntityFqn($mappedModel): string
    {
        $replicationEntityMapping = ReplicationHelper::ENTITY_MAPPING;

        if (isset($replicationEntityMapping[$mappedModel])) {
            $mappedModel = $replicationEntityMapping[$mappedModel];
        }

        return AbstractGenerator::fqn(ReplicationOperation::BASE_MODEL_NAMESPACE, $mappedModel);
    }
}
