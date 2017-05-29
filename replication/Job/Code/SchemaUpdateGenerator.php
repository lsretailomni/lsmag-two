<?php
namespace Ls\Replication\Job\Code;


use CaseHelper\CaseHelperFactory;
use CaseHelper\CaseHelperInterface;
use Composer\Autoload\ClassLoader;
use Ls\Omni\Service\Metadata;
use Ls\Omni\Service\Service;
use Ls\Omni\Service\ServiceType;
use Ls\Omni\Service\Soap\Client;
use Ls\Replication\Setup\UpgradeSchema;
use Ls\Replication\Setup\UpgradeSchema\UpgradeSchemaBlockInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\GeneratorInterface;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Reflection\ClassReflection;

class SchemaUpdateGenerator implements GeneratorInterface
{
    /** @var string */
    private $entity_fqn;
    /** @var ClassReflection */
    private $reflected_entity;
    /** @var ClassReflection */
    private $reflected_upgrade;
    /** @var FileGenerator */
    private $file;
    /** @var ClassGenerator */
    private $class;
    /** @var Metadata */
    private $metadata;
    /** @var string */
    private $version;
    /** @var CaseHelperInterface */
    private $case_helper;


    public function __construct ( $entity_fqn, $version ) {
        $ecommerce = ServiceType::ECOMMERCE();
        $client = new Client( Service::getUrl( $ecommerce ), $ecommerce );

        $this->entity_fqn = $entity_fqn;
        $this->version = $version;

        $this->reflected_entity = new ClassReflection( $entity_fqn );
        $this->reflected_upgrade = new ClassReflection( UpgradeSchema::class );
        $this->metadata = $client->getMetadata();
        $this->case_helper = CaseHelperFactory::make( CaseHelperFactory::INPUT_TYPE_PASCAL_CASE );

        $this->file = new FileGenerator();
        $this->class = new ClassGenerator();
        $this->file->setClass( $this->class );
    }

    /**
     * @return string
     */
    public function getTableName () {
        $entity_name = strtolower( $this->case_helper->toSnakeCase( $this->reflected_entity->getShortName() ) );
        $table_name = "lsr_replication_$entity_name";

        return $table_name;
    }

    /**
     * @return string
     */
    public function generate () {

        $entity_name = $this->case_helper->toPascalCase( $this->reflected_entity->getShortName() );

        $upgrade_method = new MethodGenerator();
        $upgrade_method->setName( "upgrade" );
        $upgrade_method->setParameters( [ new ParameterGenerator( 'setup', SchemaSetupInterface::class, NULL ),
                                          new ParameterGenerator( 'context', ModuleContextInterface::class, NULL ) ] );
        $upgrade_method->setBody( $this->getMethodBody() );

        $this->class->setNamespaceName( $this->reflected_upgrade->getNamespaceName() . '\\UpgradeSchema' );

        $this->class->addUse( SchemaSetupInterface::class );
        $this->class->addUse( ModuleContextInterface::class );
        $this->class->addUse( Table::class );

        $this->class->setName( "$entity_name" );
        $this->class->setImplementedInterfaces( [ UpgradeSchemaBlockInterface::class ] );

        $this->class->addMethodFromGenerator( $upgrade_method );

        $content = $this->file->generate();
        $content = str_replace( 'implements \\Magento\\Framework\\Setup\\UpgradeSchemaInterface',
                                'implements UpgradeSchemaInterface', $content );
        $content = str_replace( '\\Magento\\Framework\\Setup\\SchemaSetupInterface $setup',
                                'SchemaSetupInterface $setup', $content );
        $content = str_replace( '\\Magento\\Framework\\Setup\\ModuleContextInterface $context',
                                'ModuleContextInterface $context', $content );

//        $upgrade_schema = new UpgradeSchema();
//        $versions = $this->reflected_upgrade->getProperty( 'versions' );
//        $version_value = $versions->getValue( $upgrade_schema );
//        $version_value[] = "'{$this->version}'";
//        $versions->setValue( $version_value );

        return $content;
    }

    /**
     * @return string
     */
    public function getMethodBody () {

        $restrictions = $this->metadata->getRestrictions();
        $property_types = [ ];
        $simple_types = [ 'boolean', 'string', 'int', 'float' ];
        foreach ( $this->reflected_entity->getProperties() as $property ) {
            $docblock = $property->getDocBlock()->getContents();
            preg_match( '/property\s(:?\w+)\s\$(:?\w+)/m', $docblock, $matches );
            $type = $matches[ 1 ];
            $name = $matches[ 2 ];
            if ( array_search( $type, $simple_types ) === FALSE ) {
                if ( array_key_exists( $type, $restrictions ) ) {
                    $property_types[ $name ] = $type;
                }
            } else {
                $property_types[ $name ] = $type;
            }
        };

        $table_name = $this->getTableName();
        $method_body = <<<CODE
if ( ! \$setup->tableExists( '$table_name' ) ) {

\t\$table = new Table();
\t\$table->setName( '$table_name' ); 

\t\$table->addColumn( 'id', Table::TYPE_INTEGER, NULL, 
\t                    [ 'identity' => TRUE, 'unsigned' => TRUE, 'nullable' => FALSE, 'auto_increment' => TRUE ] );

CODE;
        foreach ( $property_types as $name => $type ) {
            ( array_search( $type, $simple_types ) === FALSE ) and ( $type = 'string' );
            if ( $type == 'int' ) {
                $field_type = 'Table::TYPE_INTEGER';
            } elseif ( $type == 'float' ) {
                $field_type = 'Table::TYPE_FLOAT';
            } elseif ( $type == 'boolean' ) {
                $field_type = 'Table::TYPE_BOOLEAN';
            } else {
                $lower_name = strtolower( $name );
                if ( strpos( $lower_name, 'base64' ) !== FALSE ) {
                    $field_type = 'Table::TYPE_TEXT';
                } else $field_type = 'Table::TYPE_BLOB';
            }
            $method_body .= "\t\$table->addColumn( '$name' , $field_type );\n";
        }

        $method_body .= <<<CODE
\t\$table->addColumn( 'scope', Table::TYPE_TEXT, 8);
\t\$table->addColumn( 'scope_id', Table::TYPE_INTEGER, 11);
\t\$table->addColumn( 'processed', Table::TYPE_BOOLEAN, null, [ 'default' => FALSE ] );

\t\$setup->getConnection()->createTable( \$table );
}
CODE;

        return $method_body;
    }

    /**
     * @return string
     */
    public function getPath () {
        /** @var ClassLoader $loader */
        $loader = $GLOBALS[ 'loader' ];

        $entity_name = ucfirst( $this->reflected_entity->getShortName() );
        $upgrade_path = $loader->findFile( UpgradeSchema::class );
        $upgrade_path = str_replace( 'UpgradeSchema', "UpgradeSchema/$entity_name", $upgrade_path );

        return $upgrade_path;
    }
}
