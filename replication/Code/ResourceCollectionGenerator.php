<?php
namespace Ls\Replication\Code;


use Composer\Autoload\ClassLoader;
use Ls\Replication\Model\ResourceModel\Anchor;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use ReflectionClass;
use Symfony\Component\Filesystem\Filesystem;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\GeneratorInterface;
use Zend\Code\Generator\MethodGenerator;

class ResourceCollectionGenerator implements GeneratorInterface
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
    /** @var Filesystem */
    private $fs;

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
        $this->fs = new Filesystem();
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
        $path = str_replace( 'Anchor.php', $this->getName(), $path );
        if ( !$this->fs->exists( $path ) ) $this->fs->mkdir( $path );
        $path .= DIRECTORY_SEPARATOR . 'Collection.php';

        return $path;
    }

    public function getNamespace () {
        $entity_name = $this->getName();

        return self::$namespace . "\\{$entity_name}";
    }

    /**
     * @return string
     */
    public function generate () {

        $model_generator = new ModelGenerator( $this->entity_fqn, $this->table_name );
        $resource_model_generator = new ResourceModelGenerator( $this->entity_fqn, $this->table_name );
        $model_class = $model_generator::$namespace . "\\" . $model_generator->getName();
        $resource_model_class = $resource_model_generator::$namespace . "\\" . $resource_model_generator->getName();

        $contructor_method = new MethodGenerator();
        $contructor_method->setName( '_construct' );
        $contructor_method->setBody( "\$this->_init( '$model_class', '$resource_model_class' );" );

        $this->class->setNamespaceName( $this->getNamespace() );
        $this->class->addUse( AbstractCollection::class );

        $this->class->setName( 'Collection' );
        $this->class->setExtendedClass( AbstractCollection::class );

        $this->class->addMethodFromGenerator( $contructor_method );

        $content = $this->file->generate();
        $content =
            str_replace( 'extends Magento\\Framework\\Model\\ResourceModel\\Db\\Collection\\AbstractCollection',
                         'extends AbstractCollection', $content );

        return $content;
    }
}
