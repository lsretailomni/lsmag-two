<?php
namespace Ls\Replication\Job\Code;


use Composer\Autoload\ClassLoader;
use Ls\Omni\Service\Metadata;
use Ls\Omni\Service\Service;
use Ls\Omni\Service\ServiceType;
use Ls\Omni\Service\Soap\Client;
use Ls\Replication\Setup\UpgradeSchema;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\GeneratorInterface;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Reflection\ClassReflection;

class SchemaUpdateGenerator implements GeneratorInterface
{
    /** @var  string */
    private $entity_fqn;
    /** @var ClassReflection */
    private $reflected_entity;
    /** @var FileGenerator */
    private $file;
    /** @var ClassGenerator */
    private $class;
    /** @var Metadata */
    private $metadata;


    public function __construct ( $entity_fqn ) {
        $schema_upgrade_reflection = new ClassReflection( UpgradeSchema::class );
        $this->entity_fqn = $entity_fqn;
        $ecommerce = ServiceType::ECOMMERCE();
        $client = new Client( Service::getUrl( $ecommerce ), $ecommerce );
        $this->metadata = $client->getMetadata();
        $this->reflected_entity = new ClassReflection( $entity_fqn );
        $this->file = new FileGenerator();
        $this->class = ClassGenerator::fromReflection( $schema_upgrade_reflection );
        $this->file->setClass( $this->class );
    }

    public function getPath () {
        /** @var ClassLoader $loader */
        $loader = $GLOBALS[ 'loader' ];

        return $loader->findFile( UpgradeSchema::class );
    }

    /**
     * @return string
     */
    public function generate () {

        $entity_name = $this->reflected_entity->getShortName();
        $uc_en = ucfirst( $entity_name );
        $upgrade_method = new MethodGenerator();
        $upgrade_method->setName( "upgrade{$uc_en}" );
        $restrictions = $this->metadata->getRestrictions();
        $method_body = '';
        $property_types = [ ];
        $simple_types = [ 'boolean', 'string', 'int', 'float' ];
        $missed_types = [ ];
        foreach ( $this->reflected_entity->getProperties() as $property ) {
            $docblock = $property->getDocBlock()->getContents();
            preg_match( '/property\s(:?\w+)\s\$(:?\w+)/m', $docblock, $matches );
            $type = $matches[ 1 ];
            $name = $matches[ 2 ];
            if ( array_search( $type, $simple_types ) === FALSE ) {
                if ( array_key_exists( $type, $restrictions ) ) {
                    $property_types[ $name ] = $type;
                } else {
                    $missed_types[] = $type;
                }
            } else {
                $property_types[ $name ] = $type;
            }
        };
        $this->class->addMethodFromGenerator( $upgrade_method );


        return $this->file->generate();
    }
}
