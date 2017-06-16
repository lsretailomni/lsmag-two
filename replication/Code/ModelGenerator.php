<?php
namespace Ls\Replication\Code;


use Composer\Autoload\ClassLoader;
use Ls\Replication\Model\Anchor;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use ReflectionClass;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\GeneratorInterface;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

class ModelGenerator implements GeneratorInterface
{
    /** @var string */
    static public $namespace = 'Ls\\Replication\\Model';
    /** @var  string */
    private $entity_fqn;
    /** @var FileGenerator */
    private $file;
    /** @var ClassGenerator */
    private $class;
    /** @var ReflectionClass */
    private $reflected_entity;
    /** @var string */
    private $table_name;

    /**
     * @param string $entity_fqn
     * @param string $table_name
     */
    public function __construct ( $entity_fqn, $table_name ) {
        $this->entity_fqn = $entity_fqn;
        $this->table_name = $table_name;
        $this->reflected_entity = new ReflectionClass( $entity_fqn );
        $this->file = new FileGenerator();
        $this->class = new ClassGenerator();
        $this->file->setClass( $this->class );
    }

    public function getPath () {
        /** @var ClassLoader $loader */
        $loader = $GLOBALS[ 'loader' ];
        $path = $loader->findFile( Anchor::class );
        $path = str_replace( 'Anchor', $this->getName(), $path );

        return $path;
    }

    /**
     * @return string
     */
    public function getName () {
        return $this->reflected_entity->getShortName();
    }

    /**
     * @return string
     */
    public function generate () {

        $interface_generator = new ModelInterfaceGenerator( $this->entity_fqn );
        $interface_name = $interface_generator->getName();
        $entity_name = $this->getName();

        $contructor_method = new MethodGenerator();
        $contructor_method->setName( '_construct' );
        $contructor_method->setBody( "\$this->_init( 'Ls\\Replication\\Model\\ResourceModel\\$entity_name' );" );
        $identities_method = new MethodGenerator();
        $identities_method->setName( 'getIdentities' );
        $identities_method->setBody( 'return [ self::CACHE_TAG . \'_\' . $this->getId() ];' );

        $this->class->setNamespaceName( self::$namespace );
        $this->class->addUse( AbstractModel::class );
        $this->class->addUse( IdentityInterface::class );
        $this->class->addUse( "{$interface_generator::$namespace}\\$interface_name" );

        $this->class->setName( $this->getName() );
        $this->class->setExtendedClass( AbstractModel::class );
        $this->class->setImplementedInterfaces( [ $interface_name, IdentityInterface::class ] );

        $this->class->addConstant( 'CACHE_TAG', $this->table_name );
        $this->class->addProperty( '_cacheTag', $this->table_name, PropertyGenerator::FLAG_PROTECTED );
        $this->class->addProperty( '_eventPrefix', $this->table_name, PropertyGenerator::FLAG_PROTECTED );
        $this->class->addMethodFromGenerator( $contructor_method );
        $this->class->addMethodFromGenerator( $identities_method );

        foreach ( $this->reflected_entity->getProperties() as $property ) {
            $this->createProperty( $property->getName() );
        }


        $content = $this->file->generate();
        $content = str_replace( 'extends Magento\\Framework\\Model\\AbstractModel',
                                'extends AbstractModel', $content );
        $content = str_replace( "implements \\$interface_name", "implements $interface_name", $content );
        $content = str_replace( ', Magento\\Framework\\DataObject\\IdentityInterface',
                                ', IdentityInterface', $content );

        return $content;
    }

    private function createProperty ( $property_name ) {

        $set_method = new MethodGenerator();
        $set_method->setDocBlock( DocBlockGenerator::fromArray( [ 'tags' => [ new ParamTag( $property_name ),
                                                                              new ReturnTag( '$this' ) ] ] ) );
        $set_method->setName( 'set' . $property_name );
        $set_method->setParameters( [ new ParameterGenerator( $property_name ) ] );
        $set_method->setBody( "\$this->setData( '$property_name', \$$property_name );\n\$this->setDataChanges( TRUE );\nreturn \$this;" );

        $get_method = new MethodGenerator();
        $set_method->setDocBlock( DocBlockGenerator::fromArray( [ 'tags' => [ new ReturnTag( '$this' ) ] ] ) );
        $get_method->setName( 'get' . $property_name );
        $get_method->setBody( "return \$this->$property_name;" );

        $this->class->addProperty( $property_name, NULL, PropertyGenerator::FLAG_PROTECTED );
        $this->class->addMethodFromGenerator( $set_method );
        $this->class->addMethodFromGenerator( $get_method );
    }
}
