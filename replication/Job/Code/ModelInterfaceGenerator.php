<?php
namespace Ls\Replication\Job\Code;


use Composer\Autoload\ClassLoader;
use Ls\Replication\Api\Data\Anchor;
use ReflectionClass;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\GeneratorInterface;
use Zend\Code\Generator\InterfaceGenerator;

class ModelInterfaceGenerator implements GeneratorInterface
{
    /** @var string */
    static public $namespace = "Ls\\Replication\\Api\\Data";
    /** @var  string */
    private $entity_fqn;
    /** @var FileGenerator */
    private $file;
    /** @var InterfaceGenerator */
    private $class;
    /** @var ReflectionClass */
    private $reflected_entity;

    /**
     * @param string $entity_fqn
     */
    public function __construct ( $entity_fqn ) {
        $this->entity_fqn = $entity_fqn;
        $this->reflected_entity = new ReflectionClass( $entity_fqn );
        $this->file = new FileGenerator();
        $this->class = new InterfaceGenerator();
        $this->file->setClass( $this->class );
    }

    /**
     * @return string
     */
    public function generate () {

        $this->class->setNamespaceName( self::$namespace );
        $this->class->setName( $this->getName() );

        return $this->file->generate();
    }

    /**
     * @return string
     */
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
        $short_name = $this->reflected_entity->getShortName();

        return "{$short_name}Interface";
    }

}
