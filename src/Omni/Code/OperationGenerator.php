<?php

namespace Ls\Omni\Code;

use \Ls\Omni\Client\AbstractOperation;
use \Ls\Omni\Client\RequestInterface;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Service\Metadata;
use \Ls\Omni\Service\Service;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client;
use \Ls\Omni\Service\Soap\Operation;
use Laminas\Code\Generator\DocBlock\Tag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;

class OperationGenerator extends AbstractOmniGenerator
{
    /** @var Operation */
    private $operation;

    /** @var array */
    private $tokenizedOperations = ['AccountGetById', 'ChangePassword', 'TransactionGetById', 'OneListDeleteById'];

    /**
     * @param Operation $operation
     * @param Metadata $metadata
     * @throws \Exception
     */
    public function __construct(Operation $operation, Metadata $metadata)
    {
        parent::__construct($metadata);
        $this->operation = $operation;
    }

    /**
     * Generates the operation class based on the operation and metadata.
     *
     * @return string The generated operation class content.
     */
    public function generate()
    {
        $requestType        = preg_replace('/[-._]/', '', $this->operation->getRequest()->getType());
        $responseType       = preg_replace('/[-._]/', '', $this->operation->getResponse()->getType());
        $serviceFolder      = ucfirst($this->getServiceType()->getValue());
        $baseNamespace      = self::fqn('Ls', 'Omni', 'Client', $serviceFolder);
        $operationName      = $this->operation->getName();
        $operationNamespace = self::fqn($baseNamespace, 'Operation');
        $entityNamespace    = self::fqn($baseNamespace, 'Entity');
        $requestFqn         = self::fqn($entityNamespace, $requestType);
        $requestAlias       = "{$operationName}Request";
        $responseFqn        = self::fqn($entityNamespace, $responseType);
        $responseAlias      = "{$operationName}Response";

        // Determine if the operation is tokenized
        $isTokenized = in_array($operationName, $this->tokenizedOperations) ? 'TRUE' : 'FALSE';

        // CLASS DECLARATION

        // NAMESPACE
        $this->class->setNamespaceName($operationNamespace);

        // USE STATEMENTS
        $this->class->addUse(RequestInterface::class);
        $this->class->addUse(ResponseInterface::class);
        $this->class->addUse(AbstractOperation::class);
        $this->class->addUse(Service::class, 'OmniService');
        $this->class->addUse(ServiceType::class);
        $this->class->addUse(Client::class, 'OmniClient');
        $this->class->addUse(self::fqn($baseNamespace, 'ClassMap'));
        $this->class->addUse($requestFqn, $requestAlias);
        $this->class->addUse($responseFqn, $responseAlias);

        // CLASS DEFINITION
        $this->class->setName($this->operation->getName());
        $this->class->setExtendedClass(AbstractOperation::class);

        // ADDING CONSTANTS
        $this->class->addConstant('OPERATION_NAME', $operationName);
        $this->class->addConstant('SERVICE_TYPE', $this->metadata->getClient()->getServiceType()->getValue());

        // ADDING METHODS
        $this->class->addMethodFromGenerator($this->getConstructorMethod());
        $this->class->addMethodFromGenerator($this->getExecuteMethod(
            $requestAlias,
            $responseAlias,
            $operationName
        ));
        $this->class->addMethodFromGenerator($this->getInputMethod($requestAlias));
        $this->class->addMethodFromGenerator($this->getClassMapMethod());
        $this->class->addMethod('isTokenized', [], MethodGenerator::FLAG_PUBLIC, "return $isTokenized;");

        // CLASS PROPERTIES
        $this->createProperty('client', 'OmniClient');
        $this->createProperty('request', $requestAlias);
        $this->createProperty('response', $responseAlias);
        $this->createProperty('requestXml', 'string');
        $this->createProperty('responseXml', 'string');
        $this->createProperty('error', '\Exception');

        // GENERATE FINAL CLASS CONTENT
        $content = $this->file->generate();

        // Adjust docblock and fully qualified names
        $noInspection    = '/** @noinspection PhpDocSignatureInspection */';
        $executeDocBlock = <<<COMMENT
    /**
     * @param $requestAlias \$request
COMMENT;
        $content          = str_replace($executeDocBlock, "$noInspection\n$executeDocBlock", $content);
        $content = str_replace('execute(\\RequestInterface', 'execute(RequestInterface', $content);
        $content = str_replace(
            'implements Ls\\Omni\\Client\\OperationInterface',
            'implements OperationInterface',
            $content
        );
        $content = str_replace('extends Ls\\Omni\\Client\\AbstractOperation', 'extends AbstractOperation', $content);
        $content = str_replace(
            'public function getOperationInput()',
            'public function & getOperationInput()',
            $content
        );
        return $content;
    }

    /**
     * Generates the constructor method for the operation class.
     *
     * @return MethodGenerator The generated constructor method.
     */
    private function getConstructorMethod()
    {
        $method = new MethodGenerator();
        $method->setParameters([new ParameterGenerator('baseUrl', null, '')]);
        $method->setName('__construct');
        $method->setBody(
            <<<CODE
\$serviceType = new ServiceType( self::SERVICE_TYPE );
parent::__construct( \$serviceType );
\$url = OmniService::getUrl( \$serviceType, \$baseUrl );
\$this->client = new OmniClient( \$url, \$serviceType );
\$this->client->setClassmap( \$this->getClassMap() );
CODE
        );

        return $method;
    }

    /**
     * Generates the execute method for the operation class.
     *
     * @param string $requestAlias The alias for the request class.
     * @param string $responseAlias The alias for the response class.
     * @param string $operationName The name of the operation.
     *
     * @return MethodGenerator The generated execute method.
     */
    private function getExecuteMethod($requestAlias, $responseAlias, $operationName)
    {
        $method = new MethodGenerator();
        $method->setName('execute');
        $method->setParameter(ParameterGenerator::fromArray([
            'name'         => 'request',
            'type'         => 'RequestInterface',
            'defaultvalue' => null
        ]));

        $method->setDocBlock(
            DocBlockGenerator::fromArray([
                'tags' => [
                    new Tag\ParamTag('request', $requestAlias),
                    new Tag\ReturnTag(['ResponseInterface', $responseAlias])
                ]
            ])
        );

        $method->setBody(
            <<<CODE
if ( !is_null( \$request ) ) {
    \$this->setRequest( \$request );
}
return \$this->makeRequest( self::OPERATION_NAME );
CODE
        );

        return $method;
    }

    /**
     * Generates the method to get the operation input.
     *
     * @param string $requestAlias The alias for the request class.
     *
     * @return MethodGenerator The generated method for getting operation input.
     */
    private function getInputMethod($requestAlias)
    {
        $method = new MethodGenerator();
        $method->setName('getOperationInput');
        $method->setDocBlock(
            DocBlockGenerator::fromArray(['tags' => [new Tag\ReturnTag([$requestAlias])]])
        );
        $method->setBody(
            <<<CODE
if ( is_null( \$this->request ) ) {
    \$this->request = new $requestAlias();
}
return \$this->request;
CODE
        );
        return $method;
    }

    /**
     * Generates the method to get the class map for the operation.
     *
     * @return MethodGenerator The generated method for getting the class map.
     */
    private function getClassMapMethod()
    {
        $method = new MethodGenerator();
        $method->setName('getClassMap');
        $method->setVisibility(MethodGenerator::FLAG_PROTECTED);
        $method->setDocBlock(
            DocBlockGenerator::fromArray(['tags' => [new Tag\ReturnTag(['array'])]])
        );
        $method->setBody(
            <<<CODE
return ClassMap::getClassMap();
CODE
        );
        return $method;
    }
}
