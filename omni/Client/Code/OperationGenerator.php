<?php
namespace Ls\Omni\Client\Code;

use Ls\Omni\Client\AbstractOperation;
use Ls\Omni\Client\IRequest;
use Ls\Omni\Client\IResponse;
use Ls\Omni\Service\Metadata;
use Ls\Omni\Service\Service;
use Ls\Omni\Service\ServiceType;
use Ls\Omni\Service\Soap\Client;
use Ls\Omni\Service\Soap\Operation;
use Zend\Code\Generator\DocBlock\Tag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

class OperationGenerator extends AbstractGenerator
{
    /** @var  Operation */
    private $operation;

    /**
     * @param Operation $operation
     * @param Metadata  $metadata
     */
    public function __construct ( Operation $operation, Metadata $metadata ) {
        parent::__construct( $metadata );
        $this->operation = $operation;
    }

    /**
     * @return string
     */
    public function generate () {

        $request_type = $this->operation->getRequest()->getType();
        $response_type = $this->operation->getResponse()->getType();

        $service_folder = ucfirst( $this->getServiceType()->getValue() );
        $base_namespace = self::fqn( 'Ls', 'Omni', 'Client', $service_folder );
        $operation_name = $this->operation->getName();
        $operation_namespace = self::fqn( $base_namespace, 'Operation' );
        $entity_namespace = self::fqn( $base_namespace, 'Entity' );

        $request_fqn = self::fqn( $entity_namespace, $request_type );
        $request_alias = "{$operation_name}Request";
        $response_fqn = self::fqn( $entity_namespace, $response_type );
        $response_alias = "{$operation_name}Response";

        // CLASS DECLARATION

        // NAMESPACE
        $this->class->setNamespaceName( $operation_namespace );

        // USE STATEMENTS
        $this->class->addUse( IRequest::class );
        $this->class->addUse( IResponse::class );
        $this->class->addUse( AbstractOperation::class );
        $this->class->addUse( Service::class, 'OmniService' );
        $this->class->addUse( ServiceType::class );
        $this->class->addUse( Client::class, 'OmniClient' );
        $this->class->addUse( self::fqn( $base_namespace, 'ClassMap' ) );
        $this->class->addUse( $request_fqn, $request_alias );
        $this->class->addUse( $response_fqn, $response_alias );

        // CLASS DEFINITION
        // class OPERATION extends AbstractOperation implements IOperation
        $this->class->setName( $this->operation->getName() );
        $this->class->setExtendedClass( AbstractOperation::class );

        // SOME NICE TO HAVE STRING VALUES
        // const OPERATION_NAME = 'OPERATION_NAME'
        // const SERVICE_TYPE = 'ecommerce|loyalty|general'
        $this->class->addConstant( 'OPERATION_NAME', $this->operation->getScreamingSnakeName() );
        $this->class->addConstant( 'SERVICE_TYPE', $this->metadata->getClient()->getServiceType()->getValue() );

        // ADD METHODS
        //  __construct & execute & getOperationInput
        $this->class->addMethodFromGenerator( $this->getConstructorMethod() );
        $this->class->addMethodFromGenerator( $this->getExecuteMethod( $request_alias, $response_alias,
                                                                       $operation_name ) );
        $this->class->addMethodFromGenerator( $this->getInputMethod( $request_alias ) );
        $this->class->addMethodFromGenerator( $this->getClassMapMethod() );

        // CLASS PROPERTIES TO BE USED BY THE AbstractOperation
        $this->createProperty( 'client', 'OmniClient', [ PropertyGenerator::VISIBILITY_PROTECTED ] );
        $this->createProperty( 'request', $request_alias );
        $this->createProperty( 'response', $response_alias );
        $this->createProperty( 'request_xml', $request_alias );
        $this->createProperty( 'response_xml', $response_alias );
        $this->createProperty( 'error' );

        // THE CLASS
        $this->file->setClass( $this->class );
        $content = $this->file->generate();

        // ADDING A PHPSTORM NO INSPECTION DOCBLOCK
        // NOT AN ELEGANT MOVE TO ADD THE INSPECTION TO EVERY DOCBLOCK... BUT WILL DO FOR NOW
        // TODO: IMPROVE REGEX TO ONLY ADD INSPECTION PARAMETER SUPRESSION TO THE execute METHOD
        $no_inspection = '/** @noinspection PhpDocSignatureInspection */';
        $execute_docblock = <<<COMMENT
	/**
     * @param $request_alias \$request
COMMENT;
        $content = str_replace( $execute_docblock, "$no_inspection\n$execute_docblock", $content );
        // USE SIMPLIFIED FULLY QUALIFIED NAME                                                                 f
        $content = str_replace( 'execute(\\IRequest', 'execute(IRequest', $content );
        $content = str_replace( 'implements Ls\\Omni\\Client\\IOperation', 'implements IOperation', $content );
        $content = str_replace( 'extends Ls\\Omni\\Client\\AbstractOperation', 'extends AbstractOperation',
                                $content );
        $content = str_replace( 'public function getOperationInput()', 'public function & getOperationInput()',
                                $content );

        return $content;
    }

    private function getConstructorMethod () {

        $method = new MethodGenerator();
        $method->setName( '__construct' );
        $method->setDocBlock(
            DocBlockGenerator::fromArray( [ 'tags' => [ new Tag\ParamTag( 'service_type', 'ServiceType' ) ] ] ) );
        $method->setBody( <<<CODE
\$service_type = new ServiceType( self::SERVICE_TYPE );
parent::__construct( \$service_type );
\$url = OmniService::getUrl( \$service_type ); 
\$this->client = new OmniClient( \$url, \$service_type );
\$this->client->setClassmap( \$this->getClassMap() );
CODE
        );

        return $method;
    }

    private function getExecuteMethod ( $request_alias, $response_alias, $operation_name ) {

        $method = new MethodGenerator();
        $method->setName( 'execute' );
        $method->setParameter( ParameterGenerator::fromArray( [ 'name' => 'request',
                                                                'type' => 'IRequest',
                                                                'defaultvalue' => NULL ] ) );
        $method->setDocBlock(
            DocBlockGenerator::fromArray( [ 'tags' => [ new Tag\ParamTag( 'request', $request_alias ),
                                                        new Tag\ReturnTag( [ 'IResponse', $response_alias ] ) ] ] ) );
        $method->setBody( <<<CODE
if ( !is_null( \$request ) ) {
    \$this->setRequest( \$request );
}
return \$this->makeRequest( '$operation_name' );
CODE
        );

        return $method;
    }

    private function getInputMethod ( $request_alias ) {

        $method = new MethodGenerator();
        $method->setName( 'getOperationInput' );
        $method->setDocBlock(
            DocBlockGenerator::fromArray( [ 'tags' => [ new Tag\ReturnTag( [ $request_alias ] ) ] ] ) );
        $method->setBody( <<<CODE
if ( is_null( \$this->request ) ) {
    \$this->request = new $request_alias();
}
return \$this->request;
CODE
        );

        return $method;
    }

    private function getClassMapMethod () {

        $method = new MethodGenerator();
        $method->setName( 'getClassMap' );
        $method->setVisibility( MethodGenerator::FLAG_PROTECTED );
        $method->setDocBlock(
            DocBlockGenerator::fromArray( [ 'tags' => [ new Tag\ReturnTag( [ 'array' ] ) ] ] ) );
        $method->setBody( <<<CODE
return ClassMap::getClassMap();
CODE
        );

        return $method;
    }
}
