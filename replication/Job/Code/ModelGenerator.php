<?php
namespace Ls\Replication\Job\Code;


use Composer\Autoload\ClassLoader;
use Ls\Replication\Model\Anchor;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use ReflectionClass;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\GeneratorInterface;
use Zend\Code\Generator\MethodGenerator;

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

    /**
     * @return string
     */
    public function getName () {
        return $this->reflected_entity->getShortName();
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
        $this->class->addMethodFromGenerator( $contructor_method );
        $this->class->addMethodFromGenerator( $identities_method );

        $content = $this->file->generate();
        $content = str_replace( 'extends \\Magento\\Framework\\Model\\AbstractModel',
                                'extends AbstractModel', $content );
        $content = str_replace( "implements \\$interface_name", "implements $interface_name", $content );
        $content = str_replace( ', \\Magento\\Framework\\DataObject\\IdentityInterface',
                                ', IdentityInterface', $content );

        return $content;
    }
}
