<?php
// @codingStandardsIgnoreFile
declare(strict_types=1);

namespace Ls\Replication\Code;

use Exception;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Omni\Service\Soap\ReplicationOperation;
use Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use ReflectionException;
use Laminas\Code\Generator\MethodGenerator;

/**
 * Generates the resource model class for a replication entity.
 */
class ResourceModelGenerator extends AbstractGenerator
{
    /** @var string */
    public static string $namespace = 'Ls\\Replication\\Model\\ResourceModel';

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
        $replicationEntityMapping = ReplicationHelper::REPLICATION_ENTITY_MAPPING;

        $interfaceName = $this->operation->getInterfaceName();

        $constructorMethod = new MethodGenerator();
        $constructorMethod->setName('_construct');
        $indexColumn = $this->operation->getTableName() . '_id';
        $constructorMethod->setBody("\$this->_init('ls_replication_{$this->operation->getTableName()}', '$indexColumn');");
        $this->class->setNamespaceName(self::$namespace);
        $this->class->setName($this->getName());

        if (isset($replicationEntityMapping[$this->getName()])) {
            $mappedEntity = $replicationEntityMapping[$this->getName()];
            $this->class->setExtendedClass(self::$namespace . '\\' . $mappedEntity);
        } else {
            $this->class->addUse(AbstractDb::class);
            $this->class->setExtendedClass(AbstractDb::class);
            $this->class->addMethodFromGenerator($constructorMethod);
        }

        // ✅ Add _beforeSave() method
        $beforeSaveMethod = new MethodGenerator();
        $beforeSaveMethod->setName('_beforeSave');
        $beforeSaveMethod->setVisibility(MethodGenerator::VISIBILITY_PROTECTED);
        $beforeSaveMethod->setParameter('object', \Magento\Framework\Model\AbstractModel::class);
        $beforeSaveMethod->setDocBlock(
            <<<EOT
Perform actions before object save

param \Magento\Framework\Model\AbstractModel|\Magento\Framework\DataObject \$object
@return \$this

@SuppressWarnings(PHPMD.UnusedFormalParameter)
EOT
        );
        $beforeSaveMethod->setBody(
            <<<PHP
\$mappings = \Ls\Replication\Helper\ReplicationHelper::DB_TABLES_MAPPING;
foreach (\$mappings as \$mapping) {
    if (\Ls\Replication\Helper\ReplicationHelper::TABLE_NAME_PREFIX . \$mapping['table_name'] == \$this->getMainTable()) {
        \$columnsMapping = \$mapping['columns_mapping'];
        foreach (\$columnsMapping as \$columnName => \$columnMapping) {
            if (\$object->hasData(\$columnName)) {
                \$object->setData(
                    is_array(\$columnMapping) ? \$columnMapping['name'] : \$columnMapping,
                    \$object->getData(\$columnName)
                );
            }
        }
        break;
    }
}
return \$this;
PHP
        );
        $this->class->addMethodFromGenerator($beforeSaveMethod);


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
