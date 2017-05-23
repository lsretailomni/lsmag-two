<?php
namespace Ls\Omni\Client\Code;

use Ls\Omni\Client\IRequest;
use Ls\Omni\Client\IResponse;
use Ls\Omni\Service\Metadata;
use Ls\Omni\Service\Soap\Entity;
use Ls\Omni\Service\Soap\SoapType;
use Zend\Code\Generator\DocBlock\Tag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

class EntityGenerator extends AbstractGenerator
{
    /**
     * @var array
     */
    protected $equivalences = [
        'decimal' => 'float',
        'long' => 'int',
        'dateTime' => 'string',
        'char' => 'int',
        'guid' => 'string',
        'StreamBody' => 'string',
        'NotificationStatus' => 'string',
    ];
    /** @var Entity */
    private $entity;

    /**
     * @param Entity   $restriction
     * @param Metadata $metadata
     */
    public function __construct ( Entity $restriction, Metadata $metadata ) {
        parent::__construct( $metadata );
        $this->entity = $restriction;
    }

    function generate () {

        $service_folder = ucfirst( $this->getServiceType()->getValue() );
        $base_namespace = self::fqn( 'Ls', 'Omni', 'Client', $service_folder );
        $entity_namespace = self::fqn( $base_namespace, 'Entity' );

        $element = $this->entity->getElement();
        $types = $this->metadata->getTypes();

        $this->class->setName( $this->entity->getName() );
        $this->class->setNamespaceName( $entity_namespace );

        $type = $element->getType();
        $type = $types[ $type ];
        $is_array = $type->getSoapType() == SoapType::ARRAY_OF();

        // TRAVERSE THE COMPLEX TYPE DISCOVERED BY THE WSDL PROCESSOR
        // OUR ENTITIES HAVE A NASTY MERGE SO THEM CAN WORK ON OVERLAPPING SCHEMA DEFINITIONS
        foreach ( $type->getDefinition() as $field_name => $field_type ) {

            $field_data_type = $this->normalizeDataType( $field_type->getDataType() ) . ( $is_array ? '[]' : '' );
            $field_name_capitalized = ucfirst( $field_name );

            if ( array_key_exists( $field_data_type, $this->metadata->getRestrictions() ) ) {
                $this->class->addUse( self::fqn( $entity_namespace, 'Enum', $field_data_type ) );
            }
            $this->class->addPropertyFromGenerator( PropertyGenerator::fromArray(
                [ 'name' => $field_name,
                  'defaultvalue' => NULL,
                  'docblock' => DocBlockGenerator::fromArray(
                      [ 'tags' => [ new Tag\PropertyTag( $field_name, [ $field_data_type ] ) ] ] ),
                  'flags' => [ PropertyGenerator::FLAG_PROTECTED ] ] ) );

            $set_method_name = "set{$field_name_capitalized}";
            $get_method_name = "get{$field_name_capitalized}";

            if ( !$this->class->hasMethod( $set_method_name ) ) {
                $set_method = new MethodGenerator();
                $set_method->setName( $set_method_name );
                $set_method->setParameter( ParameterGenerator::fromArray( [ 'name' => $field_name ] ) );
                $set_method->setDocBlock(
                    DocBlockGenerator::fromArray( [ 'tags' => [ new Tag\ParamTag( $field_name, $field_data_type ),
                                                                new Tag\ReturnTag( [ '$this', ] ) ] ] ) );

                $this->class->addMethodFromGenerator( $set_method );
            }

            if ( !$this->class->hasMethod( $get_method_name ) ) {
                $get_method = new MethodGenerator();
                $get_method->setName( $get_method_name )
                           ->setDocBlock(
                               DocBlockGenerator::fromArray( [ 'tags' => [ new Tag\ReturnTag( [ $field_data_type ] ) ] ] ) );

                $this->class->addMethodFromGenerator( $get_method );
            }
        }
        // ADD REQUEST INTERFACE
        if ( $element->isRequest() ) {
            $this->class->addUse( IRequest::class );
            $this->class->setImplementedInterfaces( [ IRequest::class ] );
        }
        // ADD RESPONSE INTERFACE
        if ( $element->isResponse() ) {
            $this->class->addUse( IResponse::class );
            $this->class->setImplementedInterfaces( [ IResponse::class ] );
            foreach ( $type->getDefinition() as $field_name => $field_type ) {

                $field_data_type = $this->normalizeDataType( $field_type->getDataType() );
                $method_name = "getResponse";

                if ( !$this->class->hasMethod( $method_name ) ) {
                    $method = new MethodGenerator();
                    $method->setName( $method_name )
                           ->setDocBlock(
                               DocBlockGenerator::fromArray( [ 'tags' => [ new Tag\ReturnTag( [ $field_data_type ] ) ] ] ) );

                    $this->class->addMethodFromGenerator( $method );
                }
            }
        }
        // ADD ArrayOf's ARRAY ACCESS SUPPORT
        if ( $is_array ) {

        }

        $this->file->setClass( $this->class );

        $content = $this->file->generate();
        $content = str_replace( 'implements Ls\\Omni\\Client\\IRequest', 'implements IRequest', $content );
        $content = str_replace( 'implements Ls\\Omni\\Client\\IResponse', 'implements IResponse', $content );

        return $content;
    }

    /**
     * @param string $data_type
     *
     * @return string
     */
    protected function normalizeDataType ( $data_type ) {
        return array_key_exists( $data_type, $this->equivalences ) ? $this->equivalences[ $data_type ] : $data_type;
    }
}
