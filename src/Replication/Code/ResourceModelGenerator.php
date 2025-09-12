<?php
// @codingStandardsIgnoreFile
declare(strict_types=1);

namespace Ls\Replication\Code;

use Exception;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Omni\Service\Soap\ReplicationOperation;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use ReflectionException;
use Laminas\Code\Generator\MethodGenerator;

/**
 * Generates the resource model class for a replication entity.
 */
class ResourceModelGenerator extends AbstractGenerator
{
    /** @var string */
    public static string $namespace = 'Ls\\Replication\\Model\\Central\\ResourceModel';

    /** @var ReplicationOperation */
    public ReplicationOperation $operation;

    /**
     * @param ReplicationOperation $operation
     * @throws Exception
     * @throws ReflectionException
     */
    public function __construct(ReplicationOperation $operation)
    {
        parent::__construct();
        $this->operation = $operation;
    }

    /**
     * Generate the resource model class content.
     *
     * @return string
     */
    public function generate(): string
    {
        $interfaceName = $this->operation->getInterfaceName();

        $constructorMethod = new MethodGenerator();
        $constructorMethod->setName('_construct');
        $indexColumn = $this->operation->getTableName() . '_id';
        $constructorMethod->setBody("\$this->_init('ls_replication_{$this->operation->getTableName()}', '$indexColumn');");
        $this->class->setNamespaceName(self::$namespace);
        $this->class->setName($this->getName());
        $this->class->addUse(AbstractDb::class);
        $this->class->setExtendedClass(AbstractDb::class);
        $this->class->addMethodFromGenerator($constructorMethod);


        $content = $this->file->generate();

        $content = str_replace(
            'extends Magento\\Framework\\Model\\ResourceModel\\Db\\AbstractDb',
            'extends AbstractDb',
            $content
        );
        $content = str_replace("implements \\$interfaceName", "implements $interfaceName", $content);
        $content = str_replace(
            ', \\Magento\\Framework\\DataObject\\IdentityInterface',
            ', IdentityInterface',
            $content
        );

        return $content;
    }

    /**
     * Get the name of the resource model class to generate.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->operation->getModelName();
    }
}
