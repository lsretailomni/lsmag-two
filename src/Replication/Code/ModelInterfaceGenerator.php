<?php
// @codingStandardsIgnoreFile

namespace Ls\Replication\Code;

use Exception;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Omni\Service\Soap\ReplicationOperation;
use ReflectionClass;
use ReflectionException;
use Zend\Code\Generator\PropertyGenerator;

/**
 * Class ModelInterfaceGenerator
 * @package Ls\Replication\Code
 */
class ModelInterfaceGenerator extends AbstractGenerator
{
    /** @var string */
    public static $namespace = "Ls\\Replication\\Api\\Data";

    /** @var  string */
    protected $entity_fqn;

    /** @var ReflectionClass */
    protected $reflected_entity;

    /** @var ReplicationOperation */
    protected $operation;

    /**
     * ModelInterfaceGenerator constructor.
     * @param ReplicationOperation $operation
     * @throws Exception
     * @throws ReflectionException
     */
    public function __construct(ReplicationOperation $operation)
    {
        parent::__construct();
        $this->class = new InterfaceGenerator();
        $this->file->setClass($this->class);
        $this->operation        = $operation;
        $this->entity_fqn       = $this->operation->getOmniEntityFqn();
        $this->reflected_entity = new ReflectionClass($this->entity_fqn);
    }

    /**
     * @return string
     */
    public function generate()
    {
        $this->class->setNamespaceName(self::$namespace);
        $this->class->setName($this->getName());

        $property_regex = '/\@property\s(:?\w+)\s\$(:?\w+)/';
        foreach ($this->reflected_entity->getProperties() as $property) {
            $property_name = $property->getName();
            if ($property_name[0] == '_') {
                continue;
            }
            preg_match($property_regex, $property->getDocComment(), $matches);
            if (empty($matches)) {
                continue;
            }
            $property_type = $matches[1];
            $pascal_name   = $property_name;
            $variable_name = $property_name;

            if ($property_name == 'Id') {
                $pascal_name   = 'NavId';
                $variable_name = 'nav_id';
            }
            $this->createProperty(
                null,
                $property_type,
                [PropertyGenerator::FLAG_PROTECTED],
                [
                    'pascal_name'   => $pascal_name,
                    'variable_name' => $variable_name,
                    'interface'     => true
                ]
            );
        }

        $this->createProperty(
            null,
            'string',
            [PropertyGenerator::FLAG_PROTECTED],
            ['pascal_name' => 'Scope', 'variable_name' => 'scope', 'interface' => true]
        );
        $this->createProperty(
            null,
            'int',
            [PropertyGenerator::FLAG_PROTECTED],
            ['pascal_name' => 'ScopeId', 'variable_name' => 'scope_id', 'interface' => true]
        );
        $this->createProperty(
            null,
            'boolean',
            [PropertyGenerator::FLAG_PROTECTED],
            ['pascal_name' => 'Processed', 'variable_name' => 'processed', 'interface' => true]
        );
        $this->createProperty(
            null,
            'boolean',
            [PropertyGenerator::FLAG_PROTECTED],
            ['pascal_name' => 'IsUpdated', 'variable_name' => 'is_updated', 'interface' => true]
        );
        $this->createProperty(
            null,
            'boolean',
            [PropertyGenerator::FLAG_PROTECTED],
            ['pascal_name' => 'IsFailed', 'variable_name' => 'is_failed', 'interface' => true]
        );
        $this->createProperty(
            null,
            'string',
            [PropertyGenerator::FLAG_PROTECTED],
            ['pascal_name' => 'CreatedAt', 'variable_name' => 'created_at', 'interface' => true]
        );
        $this->createProperty(
            null,
            'string',
            [PropertyGenerator::FLAG_PROTECTED],
            ['pascal_name' => 'UpdatedAt', 'variable_name' => 'updated_at', 'interface' => true]
        );
        $this->createProperty(
            null,
            'string',
            [PropertyGenerator::FLAG_PROTECTED],
            ['pascal_name' => 'Checksum', 'variable_name' => 'checksum', 'interface' => true]
        );
        $this->createProperty(
            null,
            'string',
            [PropertyGenerator::FLAG_PROTECTED],
            ['pascal_name' => 'ProcessedAt', 'variable_name' => 'processed_at', 'interface' => true]
        );
        $content = $this->file->generate();

        $content = preg_replace('/\s+{\s+}+/', ";", $content);

        return $content;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->operation->getInterfaceName();
    }
}
