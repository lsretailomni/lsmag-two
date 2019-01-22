<?php
// @codingStandardsIgnoreFile

namespace Ls\Replication\Code;

use Ls\Core\Code\AbstractGenerator;
use Ls\Omni\Service\Soap\ReplicationOperation;
use Ls\Replication\Model\ResourceModel\Anchor;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use ReflectionClass;
use Zend\Code\Generator\MethodGenerator;

/**
 * Class ResourceModelGenerator
 * @package Ls\Replication\Code
 */
class ResourceModelGenerator extends AbstractGenerator
{
    /** @var string */
    static public $namespace = 'Ls\\Replication\\Model\\ResourceModel';

    /** @var ReflectionClass */
    protected $reflected_entity;

    /** @var ReplicationOperation */
    protected $operation;

    /**
     * ResourceModelGenerator constructor.
     * @param ReplicationOperation $operation
     * @throws \Exception
     * @throws \ReflectionException
     */
    public function __construct(ReplicationOperation $operation)
    {
        parent::__construct();
        $this->operation = $operation;
        $this->reflected_entity = new ReflectionClass($this->operation->getMainEntityFqn());
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->operation->getEntityName();
    }

    /**
     * @return string
     */
    public function generate()
    {

        $interface_name = $this->operation->getInterfaceName();
        $contructor_method = new MethodGenerator();
        $contructor_method->setName('_construct');
        $idx_column = $this->operation->getTableName() . '_id';
        $contructor_method->setBody("\$this->_init( 'ls_replication_{$this->operation->getTableName()}', '$idx_column' );");

        $this->class->setNamespaceName(self::$namespace);
        $this->class->addUse(AbstractDb::class);

        $this->class->setName($this->getName());
        $this->class->setExtendedClass(AbstractDb::class);

        $this->class->addMethodFromGenerator($contructor_method);

        $content = $this->file->generate();
        $content = str_replace(
            'extends Magento\\Framework\\Model\\ResourceModel\\Db\\AbstractDb',
            'extends AbstractDb',
            $content
        );
        $content = str_replace("implements \\$interface_name", "implements $interface_name", $content);
        $content = str_replace(
            ', \\Magento\\Framework\\DataObject\\IdentityInterface',
            ', IdentityInterface',
            $content
        );

        return $content;
    }
}
