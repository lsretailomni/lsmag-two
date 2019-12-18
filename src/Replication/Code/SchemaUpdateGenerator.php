<?php
// @codingStandardsIgnoreFile

namespace Ls\Replication\Code;

use CaseHelper\CaseHelperFactory;
use CaseHelper\CaseHelperInterface;
use Exception;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Omni\Service\Metadata;
use \Ls\Omni\Service\Service;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client;
use \Ls\Omni\Service\Soap\ReplicationOperation;
use \Ls\Replication\Setup\UpgradeSchema;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use ReflectionException;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Reflection\ClassReflection;

/**
 * Class SchemaUpdateGenerator
 * @package Ls\Replication\Code
 */
class SchemaUpdateGenerator extends AbstractGenerator
{
    /** @var ClassReflection */
    protected $reflected_entity;

    /** @var ClassReflection */
    protected $reflected_upgrade;

    /** @var Metadata */
    protected $metadata;

    /** @var ReplicationOperation */
    protected $operation;

    /** @var CaseHelperInterface */
    protected $case_helper;

    /**
     * SchemaUpdateGenerator constructor.
     * @param ReplicationOperation $operation
     * @throws Exception
     * @throws ReflectionException
     */

    public function __construct(ReplicationOperation $operation)
    {
        parent::__construct();
        $this->operation         = $operation;
        $ecommerce               = ServiceType::ECOMMERCE();
        $client                  = new Client(Service::getUrl($ecommerce), $ecommerce);
        $this->reflected_entity  = new ClassReflection($this->operation->getOmniEntityFqn());
        $this->reflected_upgrade = new ClassReflection(UpgradeSchema::class);
        $this->metadata          = $client->getMetadata();
        $this->case_helper       = CaseHelperFactory::make(CaseHelperFactory::INPUT_TYPE_PASCAL_CASE);
    }

    /**
     * @return string
     */
    public function generate()
    {
        $entity_name    = $this->case_helper->toPascalCase($this->reflected_entity->getShortName());
        $upgrade_method = new MethodGenerator();
        $upgrade_method->setName("upgrade");
        $upgrade_method->setParameters([
            new ParameterGenerator('setup', SchemaSetupInterface::class, null),
            new ParameterGenerator('context', ModuleContextInterface::class, null)
        ]);
        $upgrade_method->setBody($this->getMethodBody());
        $this->class->setNamespaceName($this->reflected_upgrade->getNamespaceName() . '\\UpgradeSchema');
        $this->class->addUse(SchemaSetupInterface::class);
        $this->class->addUse(ModuleContextInterface::class);
        $this->class->addUse(Table::class);
        $this->class->setName("$entity_name");
        $this->class->addMethodFromGenerator($upgrade_method);
        $content = $this->file->generate();
        $content = str_replace(
            'implements Ls\\Replication\\Setup\\UpgradeSchema\\UpgradeSchemaBlockInterface',
            'implements UpgradeSchemaBlockInterface',
            $content
        );
        $content = str_replace(
            'extends Ls\\Replication\\Setup\\UpgradeSchema\\AbstractUpgradeSchema',
            'extends AbstractUpgradeSchema',
            $content
        );
        $content = str_replace(
            '\Magento\\Framework\\Setup\\SchemaSetupInterface $setup',
            'SchemaSetupInterface $setup',
            $content
        );
        $content = str_replace(
            '\Magento\\Framework\\Setup\\ModuleContextInterface $context',
            'ModuleContextInterface $context',
            $content
        );
        return $content;
    }

    /**
     * @return string
     */
    public function getMethodBody()
    {
        $restrictions   = $this->metadata->getRestrictions();
        $property_types = [];
        $simple_types   = ['boolean', 'string', 'int', 'float'];
        foreach ($this->reflected_entity->getProperties() as $property) {
            $docblock = $property->getDocBlock()->getContents();
            preg_match('/property\s(:?\w+)\s\$(:?\w+)/m', $docblock, $matches);
            $type = $matches[1];
            $name = $matches[2];
            if (array_search($type, $simple_types) === false) {
                if (array_key_exists($type, $restrictions)) {
                    $property_types[$name] = $type;
                }
            } else {
                $property_types[$name] = $type;
            }
        };

        $table_name     = $this->getTableName();
        $table_idx_name = $this->getTableFieldId();
        $method_body    = <<<CODE
\$table_name = \$setup->getTable( 'ls_replication_$table_name' ); 
if(!\$setup->tableExists(\$table_name)) {
\t\$table = \$setup->getConnection()->newTable( \$table_name );
\t\$table->addColumn('$table_idx_name', Table::TYPE_INTEGER, 11, [ 'identity' => TRUE, 'primary' => TRUE, 'unsigned' => TRUE, 'nullable' => FALSE, 'auto_increment'=> TRUE ]);
\t\$table->addColumn('scope', Table::TYPE_TEXT, 8);
\t\$table->addColumn('scope_id', Table::TYPE_INTEGER, 11);
\t\$table->addColumn('processed', Table::TYPE_BOOLEAN, 1, [ 'default' => 0 ], 'Flag to check if data is already copied into Magento. 0 means needs to be copied into Magento tables & 1 means already copied');
\t\$table->addColumn('is_updated', Table::TYPE_BOOLEAN, 1, [ 'default' => 0 ], 'Flag to check if data is already updated from Omni into Magento. 0 means already updated & 1 means needs to be updated into Magento tables');
\t\$table->addColumn('is_failed', Table::TYPE_BOOLEAN, 1, [ 'default' => 0 ], 'Flag to check if data is already added from Flat into Magento successfully or not. 0 means already added successfully & 1 means failed to add successfully into Magento tables');

CODE;
        foreach ($property_types as $raw_name => $type) {
            $name    = $raw_name;
            $length  = null;
            $default = 'null';

            (array_search($type, $simple_types) === false) and ($type = 'string');
            if ($type == 'int') {
                $field_type = 'Table::TYPE_INTEGER';
                $length     = 11;
            } elseif ($type == 'float') {
                $field_type = 'Table::TYPE_DECIMAL';
                $length     = "'20,4'";
            } elseif ($type == 'boolean') {
                $field_type = 'Table::TYPE_BOOLEAN';
                $default    = 0;
                $length     = 1;
            } else {
                $lower_name = strtolower($name);
                if (strpos($lower_name, 'image64') === false) {
                    $field_type = 'Table::TYPE_TEXT';
                    $length     = "''";
                } else {
                    $field_type = 'Table::TYPE_BLOB';
                    $length     = "'25M'";
                }
            }
            if ($name == 'Id') {
                $name = 'nav_id';
            }
            $allColumnsArray[] = array(
                'name'       => $name,
                'field_type' => $field_type,
                'default'    => $default,
                'length'     => $length
            );
            $method_body       .= "\t\$table->addColumn('$name' , $field_type, $length);\n";
        }
        $allColumnsArray[] = array(
            'name'       => 'is_failed',
            'field_type' => 'Table::TYPE_BOOLEAN',
            'default'    => 0,
            'length'     => 1
        );
        $method_body       .= <<<CODE
\t\$table->addColumn('created_at', Table::TYPE_TIMESTAMP, null, [ 'nullable' => false, 'default' => Table::TIMESTAMP_INIT ], 'Created At');
\t\$table->addColumn('updated_at', Table::TYPE_TIMESTAMP, null, [ 'nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE ], 'Updated At');
\t\$setup->getConnection()->createTable( \$table );
} else {
\t\$connection = \$setup->getConnection();
CODE;
        foreach ($allColumnsArray as $column) {
            $comment     = ucfirst($column['name']);
            $method_body .= "\n\tif (\$connection->tableColumnExists(\$table_name, '" . $column['name'] . "' ) === false) {";
            $method_body .= "\n\t\t\$connection->addColumn(\$table_name, '" . $column['name'] . "', ['length' => " . $column['length'] . ",'default' => " . $column['default'] . ",'type' => " . $column['field_type'] . ", 'comment' => '$comment']);\n";
            $method_body .= "\t} else {";
            $method_body .= "\n\t\t\$connection->modifyColumn(\$table_name, '" . $column['name'] . "', ['length' => " . $column['length'] . ",'default' => " . $column['default'] . ",'type' => " . $column['field_type'] . ", 'comment' => '$comment']);\n";
            $method_body .= "\t}";
        }
        $method_body .= <<<CODE
\n}
CODE;
        return $method_body;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->operation->getTableName();
        return "ls_replication_$table_name";
    }

    /**
     * @return string
     */
    public function getTableFieldId()
    {
        return $this->operation->getTableColumnId();
    }

    public function getPath()
    {
        $objectManager = ObjectManager::getInstance();
        /** @var  Reader $dirReader */
        $dirReader    = $objectManager->get('\Magento\Framework\Module\Dir\Reader');
        $basepath     = $dirReader->getModuleDir('', 'Ls_Replication');
        $upgrade_path = $basepath . "/Setup/UpgradeSchema";
        $entity_name  = ucfirst($this->reflected_entity->getShortName());
        $upgrade_path = str_replace('UpgradeSchema', "UpgradeSchema/$entity_name", $upgrade_path);
        $upgrade_path .= '.php';
        return $upgrade_path;
    }
}
