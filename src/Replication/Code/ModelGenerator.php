<?php
// @codingStandardsIgnoreFile

namespace Ls\Replication\Code;

use Composer\Autoload\ClassLoader;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Omni\Service\Soap\ReplicationOperation;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use ReflectionClass;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\PropertyGenerator;

/**
 * Class ModelGenerator
 * @package Ls\Replication\Code
 */
class ModelGenerator extends AbstractGenerator
{
    /** @var string */
    static public $namespace = 'Ls\\Replication\\Model';

    /** @var ReplicationOperation */
    public $operation;

    /** @var ReflectionClass */
    public $reflected_entity;

    /** @var string */
    public $table_name;

    /**
     * ModelGenerator constructor.
     * @param ReplicationOperation $operation
     * @throws \Exception
     * @throws \ReflectionException
     */
    public function __construct(ReplicationOperation $operation)
    {

        parent::__construct();
        $this->operation = $operation;
        // @codingStandardsIgnoreStart
        $this->reflected_entity = new ReflectionClass($this->operation->getOmniEntityFqn());
        // @codingStandardsIgnoreLine
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->reflected_entity->getShortName();
    }

    /**
     * @return string
     */
    public function generate()
    {

        $interface_name = $this->operation->getInterfaceName();
        $entity_name = $this->operation->getEntityName();

        $contructor_method = new MethodGenerator();
        $contructor_method->setName('_construct');
        $contructor_method->setBody("\$this->_init( 'Ls\\Replication\\Model\\ResourceModel\\$entity_name' );");

        $identities_method = new MethodGenerator();
        $identities_method->setName('getIdentities');
        $identities_method->setBody('return [ self::CACHE_TAG . \'_\' . $this->getId() ];');

        $this->class->setNamespaceName(self::$namespace);
        $this->class->addUse(AbstractModel::class);
        $this->class->addUse(IdentityInterface::class);
        $this->class->addUse($this->operation->getInterfaceFqn());

        $this->class->setName($this->operation->getEntityName());
        $this->class->setExtendedClass(AbstractModel::class);
        $this->class->setImplementedInterfaces([ $this->operation->getInterfaceName(), IdentityInterface::class ]);

        $this->class->addConstant('CACHE_TAG', 'ls_replication_' . $this->operation->getTableName());
        $this->class->addProperty(
            '_cacheTag',
            'ls_replication_' . $this->operation->getTableName(),
            PropertyGenerator::FLAG_PROTECTED
        );
        $this->class->addProperty(
            '_eventPrefix',
            'ls_replication_' . $this->operation->getTableName(),
            PropertyGenerator::FLAG_PROTECTED
        );
        $this->class->addMethodFromGenerator($contructor_method);
        $this->class->addMethodFromGenerator($identities_method);

        $property_regex = '/\@property\s(:?\w+)\s\$(:?\w+)/';
        foreach ($this->reflected_entity->getProperties() as $property) {
            $property_name = $property->getName();
            if ($property_name[ 0 ] == '_') {
                continue;
            }
            preg_match($property_regex, $property->getDocComment(), $matches);
            if (empty($matches)) {
                continue;
            }
            $property_type = $matches[ 1 ];

            $pascal_name = $property_name;
            $variable_name = $property_name;

            if ($property_name == 'Id') {
                $pascal_name = 'NavId';
                $variable_name = 'nav_id';
            }
            $this->createProperty(
                null,
                $property_type,
                [ PropertyGenerator::FLAG_PROTECTED ],
                [ 'pascal_name' => $pascal_name, 'variable_name' => $variable_name,
                'model' => true ]
            );
        }

        $this->createProperty(
            null,
            'string',
            [ PropertyGenerator::FLAG_PROTECTED ],
            [ 'pascal_name' => 'Scope', 'variable_name' => 'scope', 'model' => true ]
        );
        $this->createProperty(
            null,
            'int',
            [ PropertyGenerator::FLAG_PROTECTED ],
            [ 'pascal_name' => 'ScopeId', 'variable_name' => 'scope_id', 'model' => true ]
        );
        $this->createProperty(
            null,
            'string',
            [ PropertyGenerator::FLAG_PROTECTED ],
            [ 'pascal_name' => 'Processed', 'variable_name' => 'processed', 'model' => true ]
        );
        $this->createProperty(
            null,
            'string',
            [ PropertyGenerator::FLAG_PROTECTED ],
            [ 'pascal_name' => 'IsUpdated', 'variable_name' => 'is_updated', 'model' => true ]
        );
        $content = $this->file->generate();
        $content = str_replace(
            'extends Magento\\Framework\\Model\\AbstractModel',
            'extends AbstractModel',
            $content
        );
        $content = str_replace("implements \\$interface_name", "implements $interface_name", $content);
        $content = str_replace(
            ', Magento\\Framework\\DataObject\\IdentityInterface',
            ', IdentityInterface',
            $content
        );
        return $content;
    }
}
