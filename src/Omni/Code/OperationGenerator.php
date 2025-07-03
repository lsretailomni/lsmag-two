<?php
declare(strict_types=1);

namespace Ls\Omni\Code;

use Laminas\Code\Generator\AbstractMemberGenerator;
use \Ls\Omni\Client\AbstractOperation;
use \Ls\Omni\Service\Metadata;
use \Ls\Omni\Service\Service;
use \Ls\Omni\Service\Soap\Client;
use \Ls\Omni\Service\Soap\Operation;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;

class OperationGenerator extends AbstractOmniGenerator
{
    /**
     * @param Operation $operation
     * @param Metadata $metadata
     * @throws \Exception
     */
    public function __construct(public Operation $operation, Metadata $metadata)
    {
        parent::__construct($metadata);
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

        // CLASS DECLARATION

        // NAMESPACE
        $this->class->setNamespaceName($operationNamespace);

        // USE STATEMENTS
        $this->class->addUse(AbstractOperation::class);
        $this->class->addUse(Service::class, 'OmniService');
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
        $this->class->addMethodFromGenerator($this->getExecuteMethod());
        $this->class->addMethodFromGenerator($this->getInputMethod($requestAlias));
        $this->class->addMethodFromGenerator($this->getCreateInstanceMethod());
        $this->class->addMethodFromGenerator($this->getClassMapMethod());

        // CLASS PROPERTIES
        $this->createProperty(
            'client',
            'OmniClient',
            [AbstractMemberGenerator::FLAG_PUBLIC],
            [],
            true
        );
        $this->createProperty(
            'request',
            $requestAlias,
            [AbstractMemberGenerator::FLAG_PUBLIC],
            [],
            true
        );
        $this->createProperty(
            'response',
            $responseAlias,
            [AbstractMemberGenerator::FLAG_PUBLIC],
            [],
            true
        );
        $this->createProperty(
            'requestXml',
            'string',
            [AbstractMemberGenerator::FLAG_PUBLIC],
            [],
            true
        );
        $this->createProperty(
            'responseXml',
            'string',
            [AbstractMemberGenerator::FLAG_PUBLIC],
            [],
            true
        );
        $this->createProperty(
            'error',
            '\Exception',
            [AbstractMemberGenerator::FLAG_PUBLIC],
            [],
            true
        );

        // GENERATE FINAL CLASS CONTENT
        $content = $this->file->generate();

        // Cleanup slashes from common type hints
        $replaceMap = [
            'extends Ls\\Omni\\Client\\AbstractOperation' => 'extends AbstractOperation',
            'public function setOperationInput' => 'public function & setOperationInput',
            'implements Ls\\Omni\\Client\\OperationInterface' => 'implements OperationInterface',
            "\\{$requestAlias}" => $requestAlias,
            "\\{$responseAlias}" => $responseAlias,
            '\\OmniClient' => "OmniClient",
        ];

        return str_replace(array_keys($replaceMap), array_values($replaceMap), $content);
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
parent::__construct();
\$url = OmniService::getUrl(\$baseUrl, true);
\$this->client = \$this->createInstance(OmniClient::class, ['uri' => \$url]);
\$this->client->setClassmap(\$this->getClassMap());
CODE
        );

        return $method;
    }

    /**
     * Generates the execute method for the operation class.
     *
     * @return MethodGenerator The generated execute method.
     */
    private function getExecuteMethod()
    {
        $method = new MethodGenerator();
        $method->setName('execute');
        $method->setBody(
            <<<CODE
return \$this->makeRequest(self::OPERATION_NAME);
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
        $method->setName('setOperationInput');
        $method->setParameter(ParameterGenerator::fromArray([
            'name'         => 'params',
            'type'         => 'array',
            'defaultvalue' => []
        ]));
        $method->setBody(
            <<<CODE
        \$this->setRequest(
    \$this->createInstance(
        $requestAlias::class,
        ['data' => \$params]
    )
);
\$request = \$this->getRequest();
return \$request;
CODE
        );
        return $method;
    }

    /**
     * Generates the method to create instance.
     *
     * @return MethodGenerator The generated method for to create instance.
     */
    private function getCreateInstanceMethod()
    {
        $method = new MethodGenerator();
        $method->setName('createInstance');
        $method->setParameter(ParameterGenerator::fromArray([
            'name'         => 'entityClassName',
            'type'         => 'string',
            'defaultvalue' => null
        ]));
        $method->setParameter(ParameterGenerator::fromArray([
            'name'         => 'data',
            'type'         => 'array',
            'defaultvalue' => []
        ]));
        $method->setBody(
            <<<CODE
    return \Magento\Framework\App\ObjectManager::getInstance()->create(\$entityClassName, \$data);
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
        $method->setVisibility(AbstractMemberGenerator::FLAG_PROTECTED);
        $method->setBody(
            <<<CODE
return ClassMap::getClassMap();
CODE
        );
        return $method;
    }
}
