<?php
namespace Ls\Replication\Job\Code;


use Composer\Autoload\ClassLoader;
use Ls\Replication\Model\ResourceModel\Anchor;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use ReflectionClass;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\GeneratorInterface;
use Zend\Code\Generator\MethodGenerator;

class ResourceModelGenerator implements GeneratorInterface
{
    /** @var string */
    static public $namespace = 'Ls\\Replication\\Model\\ResourceModel';
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
        $idx_column = str_replace( 'lsr_replication_', '', $this->table_name ) . '_id';
        $contructor_method->setBody( "\$this->_init( '{$this->table_name}', '$idx_column' );" );

        $this->class->setNamespaceName( self::$namespace );
        $this->class->addUse( AbstractDb::class );

        $this->class->setName( $this->getName() );
        $this->class->setExtendedClass( AbstractDb::class );

        $this->class->addMethodFromGenerator( $contructor_method );

        $content = $this->file->generate();
        $content = str_replace( 'extends \\Magento\\Framework\\Model\\AbstractModel',
                                'extends AbstractModel', $content );
        $content = str_replace( "implements \\$interface_name", "implements $interface_name", $content );
        $content = str_replace( ', \\Magento\\Framework\\DataObject\\IdentityInterface',
                                ', IdentityInterface', $content );

        return $content;
    }
}
