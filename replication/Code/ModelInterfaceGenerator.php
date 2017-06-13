<?php
namespace Ls\Replication\Code;


use Composer\Autoload\ClassLoader;
use Ls\Replication\Api\Data\Anchor;
use ReflectionClass;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\GeneratorInterface;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;

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

        /**
         * @property boolean $Blocked
         */
        $property_regex = '/\@property\s(:?\w+)\s\$(:?\w+)/';
        foreach ( $this->reflected_entity->getProperties() as $property ) {

            $property_name = $property->getName();
            preg_match( $property_regex, $property->getDocComment(), $matches );
            $property_type = $matches[ 1 ];
            $check = $property_name == $matches[ 2 ];

            $set_method = new MethodGenerator();
            $set_method->setName( 'set' . $property->getName() );
            $set_method->setParameters( [ new ParameterGenerator( $property->getName() ) ] );
            $set_method->setDocBlock( DocBlockGenerator::fromArray( [ 'tags' => [ new ParamTag( $property_name,
                                                                                                $property_type ),
                                                                                  new ReturnTag( '$this' ) ] ] ) );
            $get_method = new MethodGenerator();
            $set_method->setDocBlock( DocBlockGenerator::fromArray( [ 'tags' => [ new ReturnTag( $property_type ) ] ] ) );
            $get_method->setName( 'get' . $property->getName() );

            $this->class->addMethodFromGenerator( $set_method );
            $this->class->addMethodFromGenerator( $get_method );
        }

        $content = $this->file->generate();
        $not_abstract = <<<CODE

    {
    }

CODE;
        $content = str_replace( $not_abstract, ';', $content );

        return $content;
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
