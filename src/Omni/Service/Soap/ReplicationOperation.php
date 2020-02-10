<?php

namespace Ls\Omni\Service\Soap;

use \Ls\Core\Code\AbstractGenerator;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\ObjectManagerInterface;
use ReflectionClass;
use ReflectionProperty;

/**
 * Note  : There are lots of things wrong in this file, all needs to be done according to the Magento coding standards.
 *
 * Class ReplicationOperation
 * @package Ls\Omni\Service\Soap
 * @
 */
class ReplicationOperation extends Operation
{
    const BASE_API_NAMESPACE = 'Ls\Replication\Api';
    const BASE_MODEL_NAMESPACE = 'Ls\Replication\Model';
    const BASE_CRON_NAMESPACE = 'Ls\Replication\Cron';
    const BASE_OMNI_NAMESPACE = 'Ls\Omni\Client\Ecommerce\Entity';
    const BASE_OPERATION_NAMESPACE = 'Ls\Omni\Client\Ecommerce\Operation';

    const KNOWN_RESULT_PROPERTIES = ['LastKey', 'MaxKey', 'RecordsRemaining'];

    /** @var string */
    public $entity_name;
    /** @var string */
    public $base_path;

    /**
     * @param string $name
     * @param Element $request
     * @param Element $response
     */
    public function __construct(
        $name,
        Element $request,
        Element $response
    ) {

        parent::__construct($name, $request, $response);
        $this->entity_name = $this->discoverEntity($response);
        $this->base_path   = $this->discoverBasePath();
    }

    /**
     * @param Element $response
     *
     * @return string
     */
    private function discoverEntity(Element $response)
    {

        $response_fqn = AbstractGenerator::fqn(self::BASE_OMNI_NAMESPACE, $response->getName());
        // @codingStandardsIgnoreLine
        $response_reflection = new ReflectionClass($response_fqn);
        $result_docbblock    = $response_reflection->getMethod('getResult')->getDocComment();

        preg_match('/@return\s(:?[\w]+)/', $result_docbblock, $matches);
        $result_fqn = AbstractGenerator::fqn(self::BASE_OMNI_NAMESPACE, $matches[1]);
        // @codingStandardsIgnoreLine
        $result_reflection = new ReflectionClass($result_fqn);

        $array_of = null;
        foreach ($result_reflection->getProperties() as $array_of) {
            // FILTER OUT THE MAIN ARRAY_OF ENTITY
            if (array_search($array_of->getName(), self::KNOWN_RESULT_PROPERTIES) === false) {
                break;
            }
        }
        $array_of_docblock = $array_of->getDocComment();
        preg_match('/@property\s(:?[\w]+)\s(:?\$[\w]+)/', $array_of_docblock, $matches);
        $array_of_fqn = AbstractGenerator::fqn(self::BASE_OMNI_NAMESPACE, $matches[1]);
        // @codingStandardsIgnoreLine
        $array_of_reflection = new ReflectionClass($array_of_fqn);

        // DRILL INTO THE MAIN ENTIY
        $array_of_properties = $array_of_reflection->getProperties();
        /** @var ReflectionProperty $main_entity */
        $main_entity          = array_pop($array_of_properties);
        $main_entity_docblock = $main_entity->getDocComment();
        preg_match('/@property\s(:?[\w]+)\[\]\s(:?\$[\w]+)/', $main_entity_docblock, $matches);
        $main_entity = $matches[1];

        if ($main_entity == 'Item') {
            return 'NavItem';
        }

        return $main_entity;
    }

    /**
     * @return ObjectManagerInterface
     */

    private function getObjectManager()
    {
        return ObjectManager::getInstance();
    }

    /**
     * @return Reader
     */
    private function getDirReader()
    {
        // @codingStandardsIgnoreLine
        return $this->getObjectManager()->get('\Magento\Framework\Module\Dir\Reader');
    }

    /**
     * @return string
     */
    private function discoverBasePath()
    {

        return $this->getDirReader()->getModuleDir('', 'Ls_Replication');
    }

    /**
     * @return string
     */
    public function getScreamingSnakeName()
    {
        return str_replace('REPL_ECOMM_', '', $this->case_helper->toScreamingSnakeCase($this->name));
    }

    /**
     * @return string
     */
    public function getEntityFieldId()
    {
        return $this->entity_name . 'Id';
    }

    /**
     * @return string
     */
    public function getTableColumnId()
    {
        $idx = $this->getTableName() . '_id';
        return $idx;
    }

    /**
     * @return string
     */
    public function getTableName()
    {

        return strtolower($this->case_helper->toSnakeCase($this->entity_name));
    }

    /**
     * @return string
     */
    public function getOmniEntityFqn()
    {
        $entity_name = $this->getEntityName();
        if ($entity_name == 'NavItem') {
            $entity_name = 'Item';
        }

        return AbstractGenerator::fqn(self::BASE_OMNI_NAMESPACE, $entity_name);
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entity_name;
    }

    /**
     * @return string
     */
    public function getMainEntityFqn()
    {
        return AbstractGenerator::fqn(self::BASE_MODEL_NAMESPACE, $this->getEntityName());
    }

    /**
     * @param bool $absolute
     *
     * @return string
     */
    public function getMainEntityPath($absolute = false)
    {
        return $this->getPath(AbstractGenerator::path('Model', $this->getEntityName() . '.php'), $absolute);
    }

    private function getPath($path, $absolute = false)
    {
        if ($absolute) {
            $path = AbstractGenerator::path($this->base_path, $path);
        }

        return $path;
    }

    public function getOperationFqn()
    {
        return AbstractGenerator::fqn(self::BASE_OPERATION_NAMESPACE, $this->getName());
    }

    /**
     * @return string
     */
    public function getFactoryFqn()
    {
        return AbstractGenerator::fqn(self::BASE_MODEL_NAMESPACE, $this->getFactoryName());
    }

    /**
     * @return string
     */
    public function getFactoryName()
    {
        return $this->entity_name . 'Factory';
    }

    /**
     * @return string
     */
    public function getInterfaceFqn()
    {
        return AbstractGenerator::fqn(self::BASE_API_NAMESPACE, 'Data', $this->getInterfaceName());
    }

    /**
     * @return string
     */
    public function getInterfaceName()
    {
        return $this->entity_name . 'Interface';
    }

    /**
     * @param bool $absolute
     *
     * @return string
     */
    public function getInterfacePath($absolute = false)
    {
        return $this->getPath(
            AbstractGenerator::path('Api', 'Data', $this->getInterfaceName() . '.php'),
            $absolute
        );
    }

    /**
     * @param bool $absolute
     *
     * @return string
     */
    public function getSchemaUpdatePath($absolute = false)
    {
        return $this->getPath(
            AbstractGenerator::path('Setup', 'UpgradeSchema', $this->getInterfaceName() . '.php'),
            $absolute
        );
    }

    /**
     * @return string
     */
    public function getRepositoryInterfaceFqn()
    {
        return AbstractGenerator::fqn(self::BASE_API_NAMESPACE, $this->getRepositoryInterfaceName());
    }

    /**
     * @return string
     */
    public function getRepositoryInterfaceName()
    {
        return $this->entity_name . 'RepositoryInterface';
    }

    /**
     * @param bool $absolute
     *
     * @return string
     */
    public function getRepositoryInterfacePath($absolute = false)
    {
        return $this->getPath(
            AbstractGenerator::path('Api', $this->getRepositoryInterfaceName() . '.php'),
            $absolute
        );
    }

    /**
     * @return string
     */
    public function getRepositoryInterfaceFactoryFqn()
    {
        return AbstractGenerator::fqn(self::BASE_API_NAMESPACE, $this->getRepositoryInterfaceFactoryName());
    }

    /**
     * @return string
     */
    public function getRepositoryInterfaceFactoryName()
    {
        return $this->entity_name . 'RepositoryInterfaceFactory';
    }

    /**
     * @return string
     */
    public function getResourceCollectionFactoryFqn()
    {
        return $this->getResourceCollectionFqn() . 'Factory';
    }

    /**
     * @return string
     */
    public function getResourceCollectionFqn()
    {
        return AbstractGenerator::fqn($this->getResourceCollectionNamespace(), 'Collection');
    }

    /**
     * @return string
     */
    public function getResourceCollectionNamespace()
    {
        return AbstractGenerator::fqn(self::BASE_MODEL_NAMESPACE, 'ResourceModel', $this->entity_name);
    }

    /**
     * @param bool $absolute
     *
     * @return string
     */
    public function getResourceCollectionPath($absolute = false)
    {
        $relative_path = AbstractGenerator::path('Model', 'ResourceModel', $this->entity_name, 'Collection.php');

        return $this->getPath($relative_path, $absolute);
    }

    /**
     * @return string
     */
    public function getResourceModelFqn()
    {
        return AbstractGenerator::fqn(self::BASE_MODEL_NAMESPACE, 'ResourceModel', $this->getEntityName());
    }

    /**
     * @param bool $absolute
     *
     * @return string
     */
    public function getResourceModelPath($absolute = false)
    {
        $relative_path = AbstractGenerator::path('Model', 'ResourceModel', $this->getEntityName() . '.php');

        return $this->getPath($relative_path, $absolute);
    }

    /**
     * @return string
     */
    public function getRepositoryFqn()
    {
        return AbstractGenerator::fqn(self::BASE_MODEL_NAMESPACE, $this->getRepositoryName());
    }

    /**
     * @return string
     */
    public function getRepositoryName()
    {
        return $this->getEntityName() . 'Repository';
    }

    /**
     * @param bool $absolute
     *
     * @return string
     */
    public function getRepositoryPath($absolute = false)
    {
        $relative_path = AbstractGenerator::path('Model', $this->getRepositoryName() . '.php');

        return $this->getPath($relative_path, $absolute);
    }

    /**
     * @return string
     */
    public function getJobId()
    {
        return join('_', ['replication', $this->getTableName()]);
    }

    /**
     * @return string
     */
    public function getJobFqn()
    {
        return AbstractGenerator::fqn(self::BASE_CRON_NAMESPACE, $this->getJobName());
    }

    /**
     * @return string
     */
    public function getJobName()
    {
        return $this->getName() . "Task";
    }

    /**
     * @return string
     */
    public function getJobNamespace()
    {
        return self::BASE_CRON_NAMESPACE;
    }

    /**
     * @param bool $absolute
     *
     * @return string
     */
    public function getJobPath($absolute = false)
    {
        $relative_path = AbstractGenerator::path('Cron', $this->getJobName() . '.php');

        return $this->getPath($relative_path, $absolute);
    }

    /**
     * @return string
     */
    public function getSearchInterfaceName()
    {
        return $this->getEntityName() . 'SearchResultsInterface';
    }

    /**
     * @return string
     */
    public function getSearchInterfaceFqn()
    {
        return AbstractGenerator::fqn(self::BASE_API_NAMESPACE, 'Data', $this->getSearchInterfaceName());
    }


    /**
     * @param bool $absolute
     *
     * @return string
     */
    public function getSearchInterfacePath($absolute = false)
    {
        $relative_path = AbstractGenerator::path('Api', 'Data', $this->getSearchInterfaceName() . '.php');

        return $this->getPath($relative_path, $absolute);
    }

    /**
     * @return string
     */
    public function getSearchName()
    {
        return $this->getEntityName() . 'SearchResults';
    }

    /**
     * @return string
     */
    public function getSearchFqn()
    {
        return AbstractGenerator::fqn(self::BASE_MODEL_NAMESPACE, $this->getSearchName());
    }

    /**
     * @param bool $absolute
     *
     * @return string
     */
    public function getSearchPath($absolute = false)
    {
        $relative_path = AbstractGenerator::path('Model', $this->getSearchName() . '.php');

        return $this->getPath($relative_path, $absolute);
    }

    /**
     * @return string
     */
    public function getSearchFactory()
    {
        //echo $this->getSearchName()."\n";
        return $this->getSearchName() . 'Factory';
    }

    /**
     * @return string
     */
    public function getSearchFactoryFqn()
    {
        return AbstractGenerator::fqn(self::BASE_MODEL_NAMESPACE, $this->getSearchFactory());
    }
}
