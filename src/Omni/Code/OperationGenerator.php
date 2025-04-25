<?php

namespace Ls\Omni\Code;

use Exception;
use Laminas\Code\Generator\AbstractMemberGenerator;
use \Ls\Omni\Client\AbstractOperation;
use \Ls\Omni\Client\RequestInterface;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Service\Metadata;
use \Ls\Omni\Service\Soap\Operation;
use Laminas\Code\Generator\DocBlock\Tag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;

class OperationGenerator extends AbstractOmniGenerator
{
    /** @var  Operation */
    private $operation;

    private $tokenized_operations = ['AccountGetById', 'ChangePassword', 'TransactionGetById', 'OneListDeleteById'];

    /**
     * @param Operation $operation
     * @param Metadata $metadata
     * @throws Exception
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
        $request_type        = $this->operation->getRequest()->getName();
        $response_type       = $this->operation->getResponse()->getName();
        $service_folder      = ucfirst($this->getServiceType());
        $base_namespace      = self::fqn('Ls', 'Omni', 'Client', $service_folder);
        $operation_name      = $this->operation->getName();
        $operation_namespace = self::fqn($base_namespace, 'Operation');
        $entity_namespace    = self::fqn($base_namespace, 'Entity');
        $request_fqn         = self::fqn($entity_namespace, $request_type);
        $request_alias       = "{$operation_name}Request";
        $response_fqn        = self::fqn($entity_namespace, $response_type);
        $response_alias      = "{$operation_name}Response";

        $is_tokenized = array_search($operation_name, $this->tokenized_operations) !== false ? 'TRUE' : 'FALSE';

        $this->class->setNamespaceName($operation_namespace);

        $this->class->addUse(RequestInterface::class);
        $this->class->addUse(ResponseInterface::class);
        $this->class->addUse(AbstractOperation::class);
        $this->class->addUse($request_fqn, $request_alias);
        $this->class->addUse($response_fqn, $response_alias);

        $this->class->setName($this->operation->getName());
        $this->class->setExtendedClass(AbstractOperation::class);

        $this->class->addConstant('OPERATION_NAME', $operation_name);
        $this->class->addConstant('SOAP_ACTION', $this->operation->getSoapAction());
        $this->class->addMethodFromGenerator($this->getConstructorMethod());
        $this->class->addMethodFromGenerator($this->getExecuteMethod(
            $request_alias,
            $response_alias,
            $operation_name
        ));
        $this->class->addMethodFromGenerator($this->getInputMethod($request_alias));
        $this->class->addMethod('isTokenized', [], AbstractMemberGenerator::FLAG_PUBLIC, "return $is_tokenized;");

        $this->createProperty('request', $request_alias);
        $this->createProperty('response', $response_alias);
        $this->createProperty('requestXml', 'string');
        $this->createProperty('responseXml', 'string');
        $this->createProperty('error', Exception::class);

        // THE CLASS
        $content = $this->file->generate();

        $no_inspection    = '/** @noinspection PhpDocSignatureInspection */';
        $execute_docblock = <<<COMMENT
	/**
     * @param $request_alias \$request
COMMENT;
        $content          = str_replace($execute_docblock, "$no_inspection\n$execute_docblock", $content);
        // USE SIMPLIFIED FULLY QUALIFIED NAME
        $content = str_replace('execute(\\RequestInterface', 'execute(RequestInterface', $content);
        $content = str_replace(
            'implements Ls\\Omni\\Client\\OperationInterface',
            'implements OperationInterface',
            $content
        );
        $content = str_replace('extends Ls\\Omni\\Client\\AbstractOperation', 'extends AbstractOperation', $content);
        return str_replace(
            'public function getOperationInput()',
            'public function & getOperationInput()',
            $content
        );
    }

    /**
     * @return MethodGenerator
     */
    private function getConstructorMethod()
    {
        // @codingStandardsIgnoreLine
        $method = new MethodGenerator();
        $method->setParameters([new ParameterGenerator('baseUrl', null, '')]);
        $method->setName('__construct');
        $method->setBody(
            <<<CODE
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
        // @codingStandardsIgnoreLine
        $method = new MethodGenerator();
        $method->setName('execute');
        $method->setParameter(ParameterGenerator::fromArray([
            'name'         => 'request',
            'type'         => 'RequestInterface',
            'defaultvalue' => null
        ]));
        // @codingStandardsIgnoreStart
        $method->setDocBlock(
            DocBlockGenerator::fromArray([
                'tags' => [
                    new Tag\ParamTag('request', $request_alias),
                    new Tag\ReturnTag([
                        'ResponseInterface',
                        $response_alias
                    ])
                ]
            ])
        );
        // @codingStandardsIgnoreEnd
        $method->setBody(
            <<<CODE
if ( !is_null( \$request ) ) {
    \$this->setRequest( \$request );
}
return \$this->makeRequest(self::OPERATION_NAME);
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
        // @codingStandardsIgnoreStart
        $method = new MethodGenerator();
        $method->setName('getOperationInput');
        $method->setDocBlock(
            DocBlockGenerator::fromArray(['tags' => [new Tag\ReturnTag([$request_alias])]])
        );
        // @codingStandardsIgnoreEnd
        $method->setBody(
            <<<CODE
if ( is_null( \$this->request ) ) {
    \$this->request = new $request_alias();
}
return \$this->request;
CODE
        );
        return $method;
    }
}
