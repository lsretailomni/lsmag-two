<?php
namespace Ls\Replication\Code;


use Composer\Autoload\ClassLoader;
use Ls\Replication\Api\Anchor;
use Magento\Framework\Api\SearchCriteriaInterface;
use ReflectionClass;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\GeneratorInterface;
use Zend\Code\Generator\ParameterGenerator;

class RepositoryInterfaceGenerator implements GeneratorInterface
{
    /** @var string */
    static public $namespace = "Ls\\Replication\\Api";
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

        /**
         * public function save(ThingInterface $page);
         * public function getById($id);
         * public function getList(SearchCriteriaInterface $criteria);
         * public function delete(ThingInterface $page);
         * public function deleteById($id);
         */
        $interface_generator = new ModelInterfaceGenerator( $this->entity_fqn );
        $entity_interface_name = $interface_generator->getName();
        $entity_interface_namespace = ModelInterfaceGenerator::$namespace;

        $this->class->setName( $this->getName() );
        $this->class->setNamespaceName( self::$namespace );
        $this->class->addUse( "$entity_interface_namespace\\$entity_interface_name" );
        $this->class->addUse( SearchCriteriaInterface::class );
        $this->class->addMethod( 'getList', [ new ParameterGenerator( 'criteria', SearchCriteriaInterface::class ) ] );
        $this->class->addMethod( 'save', [ new ParameterGenerator( 'page', $entity_interface_name ) ] );
        $this->class->addMethod( 'delete', [ new ParameterGenerator( 'page', $entity_interface_name ) ] );
        $this->class->addMethod( 'getById', [ new ParameterGenerator( 'id' ) ] );
        $this->class->addMethod( 'deleteById', [ new ParameterGenerator( 'id' ) ] );

        $content = $this->file->generate();
        $not_abstract = <<<CODE

    {
    }
CODE;

        $content = str_replace( "\\$entity_interface_name \$page", "$entity_interface_name \$page", $content );
        $content = str_replace( "Magento\\Framework\\Api\\SearchCriteriaInterface \$criteria",
                                "SearchCriteriaInterface \$criteria", $content );
        $content = str_replace( $not_abstract, ';', $content );


        return $content;
    }

    /**
     * @return string
     */
    public function getName () {
        $short_name = $this->reflected_entity->getShortName();

        return "{$short_name}RepositoryInterface";
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

}
