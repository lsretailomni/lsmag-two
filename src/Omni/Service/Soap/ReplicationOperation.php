<?php
declare(strict_types=1);

namespace Ls\Omni\Service\Soap;

use Ls\Core\Code\AbstractGenerator;
use Ls\Core\Model\LSR;
use Ls\Omni\Client\CentralEcommerce\ClassMap;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\ObjectManagerInterface;

/**
 * Handles code generation paths and metadata discovery for LS replication operations.
 */
class ReplicationOperation extends Operation
{
    public const BASE_API_NAMESPACE        = 'Ls\\Replication\\Api\\Central';
    public const BASE_MODEL_NAMESPACE      = 'Ls\\Replication\\Model\\Central';
    public const BASE_CRON_NAMESPACE       = 'Ls\\Replication\\Cron';
    public const BASE_OMNI_NAMESPACE       = 'Ls\\Omni\\Client\\CentralEcommerce\\Entity';
    public const BASE_OPERATION_NAMESPACE  = 'Ls\\Omni\\Client\\CentralEcommerce\\Operation';
    public const KNOWN_RESULT_PROPERTIES   = ['LastKey', 'MaxKey', 'RecordsRemaining'];

    /** @var string */
    public string $entityName;

    /** @var string */
    public string $basePath;

    /**
     * @param string $name
     * @param Element $request
     * @param Element $response
     * @throws \Exception
     */
    public function __construct(
        string $name,
        Element $request,
        Element $response
    ) {
        parent::__construct($name, $request, $response);
        $this->entityName = ClassMap::getClassMap()[$name] ?? $name;
        $this->basePath   = $this->discoverBasePath();
    }

    /**
     * Get Magento object manager
     *
     * @return ObjectManagerInterface
     */
    private function getObjectManager(): ObjectManagerInterface
    {
        return ObjectManager::getInstance();
    }

    /**
     * Get directory reader for Magento module paths
     *
     * @return Reader
     */
    private function getDirReader(): Reader
    {
        return $this->getObjectManager()->get(Reader::class);
    }

    /**
     * Discover base module path
     *
     * @return string
     */
    private function discoverBasePath(): string
    {
        return $this->getDirReader()->getModuleDir('', 'Ls_Replication');
    }

    /**
     * Get screaming snake case name for job
     *
     * @return string
     */
    public function getScreamingSnakeName(): string
    {
        return str_replace('REPL_ECOMM_', '', $this->caseHelper->toScreamingSnakeCase($this->name));
    }

    /**
     * Get entity ID field
     *
     * @return string
     */
    public function getEntityFieldId(): string
    {
        return $this->entityName . 'Id';
    }

    /**
     * Get database column ID
     *
     * @return string
     */
    public function getTableColumnId(): string
    {
        return $this->getTableName() . '_id';
    }

    /**
     * Get database table name
     *
     * @return string
     */
    public function getTableName(): string
    {
        return strtolower($this->caseHelper->toSnakeCase($this->getModelName()));
    }

    /**
     * Get fully qualified name of Omni entity
     *
     * @return string
     */
    public function getOmniEntityFqn(): string
    {
        return ($this->getEntityName() === 'NavItem') ? 'Item' : $this->getEntityName();
    }

    /**
     * Get current entity name
     *
     * @return string
     */
    public function getEntityName(): string
    {
        return $this->entityName;
    }

    /**
     * Get generated model name
     *
     * @return string
     */
    public function getModelName(): string
    {
        return 'Repl' . $this->getName(true);
    }

    /**
     * Get fully qualified name of model
     *
     * @return string
     */
    public function getMainEntityFqn(): string
    {
        return AbstractGenerator::fqn(self::BASE_MODEL_NAMESPACE, $this->getModelName());
    }

    /**
     * Get model file path
     *
     * @param bool $absolute
     * @return string
     */
    public function getMainEntityPath(bool $absolute = false): string
    {
        return $this->getPath(AbstractGenerator::path('Model', 'Central', $this->getModelName() . '.php'), $absolute);
    }

    /**
     * Build full or relative path
     *
     * @param string $path
     * @param bool $absolute
     * @return string
     */
    private function getPath(string $path, bool $absolute = false): string
    {
        return $absolute ? AbstractGenerator::path($this->basePath, $path) : $path;
    }

    /**
     * Get fully qualified name of operation
     *
     * @return string
     */
    public function getOperationFqn(): string
    {
        return AbstractGenerator::fqn(
            self::BASE_OPERATION_NAMESPACE,
            str_replace(' ', '', $this->formatGivenValue($this->getName()))
        );
    }

    /**
     * Get fully qualified name of model factory
     *
     * @return string
     */
    public function getFactoryFqn(): string
    {
        return AbstractGenerator::fqn(self::BASE_MODEL_NAMESPACE, $this->getFactoryName());
    }

    /**
     * Get model factory class name
     *
     * @return string
     */
    public function getFactoryName(): string
    {
        return $this->getModelName() . 'Factory';
    }

    /**
     * Get interface FQN
     *
     * @return string
     */
    public function getInterfaceFqn(): string
    {
        return AbstractGenerator::fqn(self::BASE_API_NAMESPACE, 'Data', $this->getInterfaceName());
    }

    /**
     * Get interface class name
     *
     * @return string
     */
    public function getInterfaceName(): string
    {
        return $this->getModelName() . 'Interface';
    }

    /**
     * Get interface path
     *
     * @param bool $absolute
     * @return string
     */
    public function getInterfacePath(bool $absolute = false): string
    {
        return $this->getPath(AbstractGenerator::path('Api', 'Central', 'Data', $this->getInterfaceName() . '.php'), $absolute);
    }

    /**
     * Get schema update path
     *
     * @param bool $absolute
     * @return string
     */
    public function getSchemaUpdatePath(bool $absolute = false): string
    {
        return $this->getPath(
            AbstractGenerator::path(
                'Setup',
                'UpgradeSchema',
                $this->getInterfaceName() . '.php'
            ),
            $absolute
        );
    }

    /**
     * Get repository interface FQN
     *
     * @return string
     */
    public function getRepositoryInterfaceFqn(): string
    {
        return AbstractGenerator::fqn(self::BASE_API_NAMESPACE, $this->getRepositoryInterfaceName());
    }

    /**
     * Get repository interface name
     *
     * @return string
     */
    public function getRepositoryInterfaceName(): string
    {
        return $this->getModelName() . 'RepositoryInterface';
    }

    /**
     * Get repository interface file path
     *
     * @param bool $absolute
     * @return string
     */
    public function getRepositoryInterfacePath(bool $absolute = false): string
    {
        return $this->getPath(AbstractGenerator::path('Api', 'Central', $this->getRepositoryInterfaceName() . '.php'), $absolute);
    }

    /**
     * Get repository factory FQN
     *
     * @return string
     */
    public function getRepositoryInterfaceFactoryFqn(): string
    {
        return AbstractGenerator::fqn(self::BASE_API_NAMESPACE, $this->getRepositoryInterfaceFactoryName());
    }

    /**
     * Get repository factory name
     *
     * @return string
     */
    public function getRepositoryInterfaceFactoryName(): string
    {
        return $this->entityName . 'RepositoryInterfaceFactory';
    }

    /**
     * Get resource collection factory FQN
     *
     * @return string
     */
    public function getResourceCollectionFactoryFqn(): string
    {
        return $this->getResourceCollectionFqn() . 'Factory';
    }

    /**
     * Get resource collection FQN
     *
     * @return string
     */
    public function getResourceCollectionFqn(): string
    {
        return AbstractGenerator::fqn($this->getResourceCollectionNamespace(), 'Collection');
    }

    /**
     * Get resource collection namespace
     *
     * @return string
     */
    public function getResourceCollectionNamespace(): string
    {
        return AbstractGenerator::fqn(self::BASE_MODEL_NAMESPACE, 'ResourceModel', $this->getModelName());
    }

    /**
     * Get resource collection path
     *
     * @param bool $absolute
     * @return string
     */
    public function getResourceCollectionPath(bool $absolute = false): string
    {
        $path = AbstractGenerator::path('Model', 'Central', 'ResourceModel', $this->getModelName(), 'Collection.php');
        return $this->getPath($path, $absolute);
    }

    /**
     * Get resource model FQN
     *
     * @return string
     */
    public function getResourceModelFqn(): string
    {
        return AbstractGenerator::fqn(self::BASE_MODEL_NAMESPACE, 'ResourceModel', $this->getModelName());
    }

    /**
     * Get resource model file path
     *
     * @param bool $absolute
     * @return string
     */
    public function getResourceModelPath(bool $absolute = false): string
    {
        $path = AbstractGenerator::path('Model', 'Central', 'ResourceModel', $this->getModelName() . '.php');
        return $this->getPath($path, $absolute);
    }

    /**
     * Get repository class FQN
     *
     * @return string
     */
    public function getRepositoryFqn(): string
    {
        return AbstractGenerator::fqn(self::BASE_MODEL_NAMESPACE, $this->getRepositoryName());
    }

    /**
     * Get repository class name
     *
     * @return string
     */
    public function getRepositoryName(): string
    {
        return $this->getModelName() . 'Repository';
    }

    /**
     * Get test class name for repository
     *
     * @return string
     */
    public function getRepositoryTestName(): string
    {
        return $this->getModelName() . 'RepositoryTest';
    }

    /**
     * Get repository path
     *
     * @param bool $absolute
     * @return string
     */
    public function getRepositoryPath(bool $absolute = false): string
    {
        $path = AbstractGenerator::path('Model', 'Central', $this->getRepositoryName() . '.php');
        return $this->getPath($path, $absolute);
    }

    /**
     * Get repository test path
     *
     * @param bool $absolute
     * @return string
     */
    public function getRepositoryTestPath(bool $absolute = false): string
    {
        $path = AbstractGenerator::path('Test', 'Unit', 'Model', 'Central', $this->getRepositoryTestName() . '.php');
        return $this->getPath($path, $absolute);
    }

    /**
     * Get unique job ID
     *
     * @return string
     */
    public function getJobId(): string
    {
        return implode('_', ['replication', $this->getTableName()]);
    }

    /**
     * Get cron job class FQN
     *
     * @return string
     */
    public function getJobFqn(): string
    {
        return AbstractGenerator::fqn(self::BASE_CRON_NAMESPACE, $this->getJobName());
    }

    /**
     * Get cron job class name
     *
     * @return string
     */
    public function getJobName(): string
    {
        $name = $this->getName(true);

        if (!str_starts_with($name, 'Lsc')) {
            $name = 'Lsc'. $name;
        }

        return 'Repl'. $name . 'Task';
    }

    /**
     * Get cron job namespace
     *
     * @return string
     */
    public function getJobNamespace(): string
    {
        return self::BASE_CRON_NAMESPACE;
    }

    /**
     * Get cron job file path
     *
     * @param bool $absolute
     * @return string
     */
    public function getJobPath(bool $absolute = false): string
    {
        return $this->getPath(AbstractGenerator::path('Cron', $this->getJobName() . '.php'), $absolute);
    }

    /**
     * Get search results interface name
     *
     * @return string
     */
    public function getSearchInterfaceName(): string
    {
        return $this->getModelName() . 'SearchResultsInterface';
    }

    /**
     * Get search results interface FQN
     *
     * @return string
     */
    public function getSearchInterfaceFqn(): string
    {
        return AbstractGenerator::fqn(self::BASE_API_NAMESPACE, 'Data', $this->getSearchInterfaceName());
    }

    /**
     * Get search interface file path
     *
     * @param bool $absolute
     * @return string
     */
    public function getSearchInterfacePath(bool $absolute = false): string
    {
        $path = AbstractGenerator::path('Api', 'Central', 'Data', $this->getSearchInterfaceName() . '.php');
        return $this->getPath($path, $absolute);
    }

    /**
     * Get search result model name
     *
     * @return string
     */
    public function getSearchName(): string
    {
        return $this->getModelName() . 'SearchResults';
    }

    /**
     * Get search result model FQN
     *
     * @return string
     */
    public function getSearchFqn(): string
    {
        return AbstractGenerator::fqn(self::BASE_MODEL_NAMESPACE, $this->getSearchName());
    }

    /**
     * Get search result file path
     *
     * @param bool $absolute
     * @return string
     */
    public function getSearchPath(bool $absolute = false): string
    {
        $path = AbstractGenerator::path('Model', 'Central', $this->getSearchName() . '.php');
        return $this->getPath($path, $absolute);
    }

    /**
     * Get search factory class name
     *
     * @return string
     */
    public function getSearchFactory(): string
    {
        return $this->getSearchName() . 'Factory';
    }

    /**
     * Get search factory class FQN
     *
     * @return string
     */
    public function getSearchFactoryFqn(): string
    {
        return AbstractGenerator::fqn(self::BASE_MODEL_NAMESPACE, $this->getSearchFactory());
    }

    /**
     * Get identifier for alternate jobs that use the same table
     *
     * @param string $jobName
     * @return string|false
     */
    public function getIdenticalTableCronJob(string $jobName): string|false
    {
        $lsr = ObjectManager::getInstance()->get(LSR::class);
        $config = $lsr->getStoreConfig(LSR::SC_REPLICATION_IDENTICAL_TABLE_WEB_SERVICE_LIST);
        $jobList = explode(',', $config);

        return in_array($jobName, $jobList)
            ? strtolower(str_replace('Ecomm_', '', $this->caseHelper->toSnakeCase($jobName)))
            : false;
    }
}
