<?php

namespace Ls\Omni\Code;

use Exception;
use Ls\Omni\Client\AbstractOperation;
use Ls\Omni\Client\RequestInterface;
use Ls\Omni\Client\ResponseInterface;
use Ls\Omni\Service\Metadata;
use Ls\Omni\Service\Service;
use Ls\Omni\Service\ServiceType;
use Ls\Omni\Service\Soap\Client;
use Ls\Omni\Service\Soap\Operation;
use Zend\Code\Generator\DocBlock\Tag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;

class OperationGenerator extends AbstractOmniGenerator
{
    /** @var  Operation */
    private $operation;

    private $tokenized_operations = ['AccountGetById', 'ChangePassword', 'TransactionGetById', 'OneListDeleteById'];

    /**
     * @param Operation $operation
     * @param Metadata $metadata
     */
    public function __construct(Operation $operation, Metadata $metadata)
    {
        parent::__construct($metadata);
        $this->operation = $operation;
    }

    /**
     * @return mixed|string
     */
    public function generate()
    {

        $request_type = $this->operation->getRequest()->getType();
        $response_type = $this->operation->getResponse()->getType();
        $service_folder = ucfirst($this->getServiceType()->getValue());
        $base_namespace = self::fqn('Ls', 'Omni', 'Client', $service_folder);
        $operation_name = $this->operation->getName();
        $operation_namespace = self::fqn($base_namespace, 'Operation');
        $entity_namespace = self::fqn($base_namespace, 'Entity');
        $request_fqn = self::fqn($entity_namespace, $request_type);
        $request_alias = "{$operation_name}Request";
        $response_fqn = self::fqn($entity_namespace, $response_type);
        $response_alias = "{$operation_name}Response";

        $is_tokenized = array_search($operation_name, $this->tokenized_operations) !== false ? 'TRUE' : 'FALSE';

        // CLASS DECLARATION

        // NAMESPACE
        $this->class->setNamespaceName($operation_namespace);

        // USE STATEMENTS
        $this->class->addUse(RequestInterface::class);
        $this->class->addUse(ResponseInterface::class);
        $this->class->addUse(AbstractOperation::class);
        $this->class->addUse(Service::class, 'OmniService');
        $this->class->addUse(ServiceType::class);
        $this->class->addUse(Client::class, 'OmniClient');
        $this->class->addUse(self::fqn($base_namespace, 'ClassMap'));
        $this->class->addUse($request_fqn, $request_alias);
        $this->class->addUse($response_fqn, $response_alias);

        // CLASS DEFINITION
        // class OPERATION extends AbstractOperation implements OperationInterface
        $this->class->setName($this->operation->getName());
        $this->class->setExtendedClass(AbstractOperation::class);

        // SOME NICE TO HAVE STRING VALUES
        // const OPERATION_NAME = 'OPERATION_NAME'
        $this->class->addConstant('OPERATION_NAME', $this->operation->getScreamingSnakeName());
        $this->class->addConstant('SERVICE_TYPE', $this->metadata->getClient()->getServiceType()->getValue());


        // ADD METHODS
        $this->class->addMethodFromGenerator($this->getConstructorMethod());
        $this->class->addMethodFromGenerator($this->getExecuteMethod(
            $request_alias,
            $response_alias,
            $operation_name
        ));
        $this->class->addMethodFromGenerator($this->getInputMethod($request_alias));
        $this->class->addMethodFromGenerator($this->getClassMapMethod());
        $this->class->addMethod('isTokenized', [], MethodGenerator::FLAG_PROTECTED, "return $is_tokenized;");

        // CLASS PROPERTIES TO BE USED BY THE AbstractOperation
        $this->createProperty('client', 'OmniClient');
        $this->createProperty('request', $request_alias);
        $this->createProperty('response', $response_alias);
        $this->createProperty('requestXml', 'string');
        $this->createProperty('responseXml', 'string');
        $this->createProperty('error', Exception::class);

        // THE CLASS
        $content = $this->file->generate();

        $no_inspection = '/** @noinspection PhpDocSignatureInspection */';
        $execute_docblock = <<<COMMENT
	/**
     * @param $request_alias \$request
COMMENT;
        $content = str_replace($execute_docblock, "$no_inspection\n$execute_docblock", $content);
        // USE SIMPLIFIED FULLY QUALIFIED NAME
        $content = str_replace('execute(\\RequestInterface', 'execute(RequestInterface', $content);
        $content = str_replace('implements Ls\\Omni\\Client\\OperationInterface', 'implements OperationInterface', $content);
        $content = str_replace('extends Ls\\Omni\\Client\\AbstractOperation', 'extends AbstractOperation', $content);
        $content = str_replace('public function getOperationInput()', 'public function & getOperationInput()', $content);
        return $content;
    }

    /**
     * @return MethodGenerator
     */
    private function getConstructorMethod()
    {
        $method = new MethodGenerator();
        $method->setName('__construct');
        $method->setBody(<<<CODE
\$service_type = new ServiceType( self::SERVICE_TYPE );
parent::__construct( \$service_type );
\$url = OmniService::getUrl( \$service_type ); 
\$this->client = new OmniClient( \$url, \$service_type );
\$this->client->setClassmap( \$this->getClassMap() );
CODE
        );

        return $method;
    }

    /**
     * @param $request_alias
     * @param $response_alias
     * @param $operation_name
     * @return MethodGenerator
     */
    private function getExecuteMethod($request_alias, $response_alias, $operation_name)
    {
        $method = new MethodGenerator();
        $method->setName('execute');
        $method->setParameter(ParameterGenerator::fromArray(['name' => 'request',
            'type' => 'RequestInterface',
            'defaultvalue' => null]));
        $method->setDocBlock(
            DocBlockGenerator::fromArray(['tags' => [new Tag\ParamTag('request', $request_alias),
                new Tag\ReturnTag(['ResponseInterface',
            $response_alias])]])
        );
        $method->setBody(<<<CODE
if ( !is_null( \$request ) ) {
    \$this->setRequest( \$request );
}
return \$this->makeRequest( '$operation_name' );
CODE
        );

        return $method;
    }

    /**
     * @param $request_alias
     * @return MethodGenerator
     */
    private function getInputMethod($request_alias)
    {

        $method = new MethodGenerator();
        $method->setName('getOperationInput');
        $method->setDocBlock(
            DocBlockGenerator::fromArray(['tags' => [new Tag\ReturnTag([$request_alias])]])
        );
        $method->setBody(<<<CODE
if ( is_null( \$this->request ) ) {
    \$this->request = new $request_alias();
}
return \$this->request;
CODE
        );
        return $method;
    }

    /**
     * @return MethodGenerator
     */
    private function getClassMapMethod()
    {
        $method = new MethodGenerator();
        $method->setName('getClassMap');
        $method->setVisibility(MethodGenerator::FLAG_PROTECTED);
        $method->setDocBlock(
            DocBlockGenerator::fromArray(['tags' => [new Tag\ReturnTag(['array'])]])
        );
        $method->setBody(<<<CODE
return ClassMap::getClassMap();
CODE
        );
        return $method;
    }
}
