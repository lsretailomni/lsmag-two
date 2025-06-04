<?php
// @codingStandardsIgnoreFile
namespace Ls\Omni\Code;

use ArrayIterator;
use Exception;
use IteratorAggregate;
use \Ls\Omni\Client\RequestInterface;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Service\Metadata;
use \Ls\Omni\Service\Soap\Entity;
use \Ls\Omni\Service\Soap\SoapType;
use Laminas\Code\Generator\DocBlock\Tag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Magento\Catalog\Model\AbstractModel;

class EntityGenerator extends AbstractOmniGenerator
{
    /** @var array Mapping of data types to their equivalents */
    public $dataTypeEquivalences = [
        'decimal'    => 'float',
        'boolean'    => 'bool',
        'long'       => 'int',
        'dateTime'   => 'string',
        'char'       => 'int',
        'guid'       => 'string',
        'StreamBody' => 'string',
        'string'     => 'string',
        'date'       => 'string',
        'time'       => 'string',
    ];

    /** @var Entity */
    private $entity;

    /**
     * @param Entity $entity
     * @param Metadata $metadata
     * @throws Exception
     */
    public function __construct(Entity $entity, Metadata $metadata)
    {
        parent::__construct($metadata);
        $this->entity = $entity;
    }

    /**
     * Generates the entity class based on metadata and the entity's WSDL definition.
     *
     * @return string The generated entity class content.
     */
    public function generate()
    {
        // Define the namespace and class name for the entity
        $serviceFolder = ucfirst($this->getServiceType()->getValue());
        $baseNamespace = self::fqn('Ls', 'Omni', 'Client', $serviceFolder);
        $entityNamespace = self::fqn($baseNamespace, 'Entity');

        $element = $this->entity->getElement();
        $types = $this->metadata->getTypes();
        $classNameOptimized = preg_replace('/[-._]/', '', $this->entity->getName());
        if ($classNameOptimized == 'ContactCreateParameters' || $classNameOptimized == 'WSInventoryBuffer') {
            $x = 1;
        }
        $this->class->setName($classNameOptimized);
        $this->class->setExtendedClass(AbstractModel::class);
        $this->class->setNamespaceName($entityNamespace);
        $this->class->addConstant('CLASS_NAME', $this->entity->getName());

        // Get the type of the element
        $type = $element->getType();
        $type = $types[$type];

        $isArray = $type->getSoapType() == SoapType::ARRAY_OF();

        // Rearrange the type definition for proper handling
        $typeDefinition = $type->getDefinition();
        $typeDefinitionArray = [];

        // Separate ArrayOf types
        foreach ($typeDefinition as $key => $value) {
            if (strpos($value->getDataType(), "ArrayOf") !== false) {
                $typeDefinitionArray[$key] = $value;
            }
        }
        foreach ($typeDefinition as $key => $value) {
            if (strpos($value->getDataType(), "ArrayOf") === false) {
                $typeDefinitionArray[$key] = $value;
            }
        }

        // Handle special case for repl entities (e.g., adding scopeId)
        $lowerString = strtolower($this->entity->getName());
        if (!$isArray && $lowerString && substr($lowerString, 0, 4) == 'repl' &&
            strpos($lowerString, 'replecom') === false &&
            strpos($lowerString, 'response') === false &&
            $lowerString != 'replrequest') {
            $typeDefinitionArray['scope'] = new ComplexTypeDefinition('scope', 'string', '0');
            $typeDefinitionArray['scope_id'] = new ComplexTypeDefinition('scope_id', 'int', '0');
        }
        $formattedConstantsRegistry = [];
        $i = 0;
        // Generate methods based on type definitions
        if ($typeDefinitionArray != null) {
            foreach ($typeDefinitionArray as $fieldName => $fieldType) {
                $fieldDataType = $this->normalizeDataType($fieldType->getDataType()) . ($isArray ? '[]' : '');
                $fieldNameOptimized = $this->formatGivenValue($fieldName);
                $fieldNameForMethodName = $this->formatGivenValue($fieldName, ' ');
                $fieldNameCapitalized = ucwords(strtolower($fieldNameForMethodName));
                $fieldNameCapitalized = str_replace(' ', '', $fieldNameCapitalized);
                $customFieldName = $this->formatGivenValue(ucwords($fieldName), ' ');
                $constantName = str_replace(
                    ' ',
                    '_',
                    strtoupper(preg_replace('/(?<=[a-z0-9])([A-Z])/', '_$1', $customFieldName))
                );
                $eqExists = array_key_exists($fieldType->getDataType(), $this->dataTypeEquivalences);
                if (strtolower($fieldNameCapitalized) == 'id') {
                    $eqExists = false;
                }

                if (isset($formattedConstantsRegistry[$constantName])) {
                    $constantName .= '_'.$i;
                    $fieldNameCapitalized .= '_'.$i;
                }
                $formattedConstantsRegistry[$constantName] = 1;
                $this->class->addConstant($constantName, $fieldName);

                // Check if the field is a restriction type
                $fieldIsRestriction = array_key_exists($fieldDataType, $this->metadata->getRestrictions());
                if ($fieldIsRestriction) {
                    $this->class->addUse(self::fqn($entityNamespace, 'Enum', $fieldDataType));
                }

                // Generate setter method
                $setMethodName = "set{$fieldNameCapitalized}";
                if (!$this->class->hasMethod($setMethodName)) {
                    $setMethod = new MethodGenerator();
                    $setMethod->setName($setMethodName);
                    $param = [
                        'name' => $fieldNameOptimized
                    ];
                    if ($eqExists) {
                        $param['type'] = '?'. $fieldDataType;
                    }
                    $setMethod->setParameter(
                        ParameterGenerator::fromArray(
                            $param
                        )
                    );
                    $setMethod->setDocBlock(
                        DocBlockGenerator::fromArray([
                            'tags' => [
                                new Tag\ParamTag($fieldNameOptimized, [$param['type'] ?? $fieldDataType]),
                                new Tag\ReturnTag(['$this'])
                            ]
                        ])
                    );
                    $setMethod->setBody(<<<CODE
\$this->setData(self::$constantName, \$$fieldNameOptimized);
return \$this;
CODE
                    );
                    if ($fieldIsRestriction) {
                        $setMethod->setDocBlock(
                            DocBlockGenerator::fromArray([
                                'tags' => [
                                    new Tag\ParamTag(
                                        $fieldName,
                                        [$fieldDataType, 'string']
                                    ),
                                    new Tag\ReturnTag(['$this']),
                                    new Tag\ThrowsTag(['InvalidEnumException'])
                                ]
                            ])
                        );
                        $this->class->addUse(InvalidEnumException::class);
                        $setMethod->setBody(<<<CODE
if ( ! \$$fieldName instanceof $fieldDataType ) {
    if ( $fieldDataType::isValid( \$$fieldName ) )
        \$$fieldName = new $fieldDataType( \$$fieldName );
    elseif ( $fieldDataType::isValidKey( \$$fieldName ) )
        \$$fieldName = new $fieldDataType( constant( "$fieldDataType::\$$fieldName" ) );
    elseif ( ! \$$fieldName instanceof $fieldDataType )
        throw new InvalidEnumException();
}
\$this->$fieldName = \$$fieldName->getValue();
return \$this;
CODE
                        );
                    }

                    $this->class->addMethodFromGenerator($setMethod);

                    // Add support for array access if the type is an array
                    if ($isArray) {
                        $this->class->addUse(IteratorAggregate::class);
                        $this->class->addUse(ArrayIterator::class);
                        $this->class->setImplementedInterfaces([IteratorAggregate::class]);
                        $iteratorMethod = new MethodGenerator();
                        $iteratorMethod->setDocBlock(
                            DocBlockGenerator::fromArray(['tags' => [new Tag\ReturnTag(['\Traversable'])]])
                        );
                        $iteratorMethod->setName('getIterator');
                        $iteratorMethod->setReturnType('Traversable');
                        $iteratorMethod->setBody(<<<CODE
return new ArrayIterator( \$this->$fieldName );
CODE
                        );
                        $this->class->addMethodFromGenerator($iteratorMethod);
                    }
                }

                // Generate getter method
                $getMethodName = "get{$fieldNameCapitalized}";
                if (!$this->class->hasMethod($getMethodName)) {
                    $getMethod = new MethodGenerator();
                    $getMethod->setName($getMethodName)
                        ->setDocBlock(
                            DocBlockGenerator::fromArray(['tags' => [new Tag\ReturnTag([$param['type'] ?? $fieldDataType])]])
                        );
                    $getMethod->setBody(<<<CODE
return \$this->getData(self::$constantName);
CODE
                    );
                    if ($eqExists) {
                        $getMethod->setReturnType('?'. $fieldDataType);
                    }

                    $this->class->addMethodFromGenerator($getMethod);
                }
                $i++;
            }
        }

        // Add Request and Response interfaces if necessary
        if ($element->isRequest()) {
            $this->class->addUse(RequestInterface::class);
            $this->class->setImplementedInterfaces([RequestInterface::class]);
        }

        if ($element->isResponse()) {
            $this->class->addUse(ResponseInterface::class);
            $this->class->setImplementedInterfaces([ResponseInterface::class]);
            foreach ($type->getDefinition() as $fieldName => $fieldType) {
                $fieldDataType = $this->normalizeDataType($fieldType->getDataType());
                $methodName = "getResult";

                if (!$this->class->hasMethod($methodName)) {
                    $method = new MethodGenerator();
                    $method->setName($methodName)
                        ->setDocBlock(
                            DocBlockGenerator::fromArray(['tags' => [new Tag\ReturnTag([$fieldDataType])]])
                        );
                    $method->setBody(<<<CODE
return \$this->$fieldName;
CODE
                    );

                    $this->class->addMethodFromGenerator($method);
                }
            }
        }

        // Set base class for internal development if specified
        if ($type->getBase()) {
            $this->class->setExtendedClass("meannothing" . $type->getBase());
        }

        // Generate the class content
        $content = $this->file->generate();

        // Clean up any unwanted class references
        if ($type->getBase()) {
            $content = str_replace("\\meannothing" . $type->getBase(), $type->getBase(), $content);
        }

        // Final cleanup of interface implementations
        $content = str_replace(array(
            'implements \\IteratorAggregate',
            'implements Ls\\Omni\\Client\\RequestInterface',
            'implements Ls\\Omni\\Client\\ResponseInterface'
        ), array('implements IteratorAggregate', 'implements RequestInterface', 'implements ResponseInterface'),
            $content);

        return $content;
    }

    /**
     * Normalizes a data type by looking up its equivalence.
     *
     * @param string $dataType The data type to normalize.
     * @return string The normalized data type.
     */
    protected function normalizeDataType($dataType)
    {
        return array_key_exists($dataType, $this->dataTypeEquivalences) ? $this->dataTypeEquivalences[$dataType] : $dataType;
    }

    /**
     * Get formatted value
     *
     * @param string $value
     * @param string $replaceWith
     * @return string
     */
    public function formatGivenValue(string $value, string $replaceWith = ''): string
    {
        // Step 1: Remove special characters
        $cleaned = preg_replace('/[\/\[\]()$\-._%&]/', $replaceWith, $value);
        // Step 2: Replace multiple spaces with a single space
        $cleaned = preg_replace('/ {2,}/', ' ', $cleaned);

        return trim($cleaned);
    }
}
