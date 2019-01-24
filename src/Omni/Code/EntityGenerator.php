<?php
// @codingStandardsIgnoreFile
namespace Ls\Omni\Code;

use ArrayIterator;
use IteratorAggregate;
use Ls\Omni\Client\RequestInterface;
use Ls\Omni\Client\ResponseInterface;
use Ls\Omni\Exception\InvalidEnumException;
use Ls\Omni\Service\Metadata;
use Ls\Omni\Service\Soap\Entity;
use Ls\Omni\Service\Soap\SoapType;
use Zend\Code\Generator\DocBlock\Tag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

/**
 * Class EntityGenerator
 * @package Ls\Omni\Code
 */
class EntityGenerator extends AbstractOmniGenerator
{

    /** @var array  */
    public $equivalences = [
        'decimal' => 'float',
        'long' => 'int',
        'dateTime' => 'string',
        'char' => 'int',
        'guid' => 'string',
        'StreamBody' => 'string',
    ];

    /** @var Entity */
    private $entity;

    /**
     * EntityGenerator constructor.
     * @param Entity $restriction
     * @param Metadata $metadata
     * @throws \Exception
     */
    public function __construct(Entity $restriction, Metadata $metadata)
    {
        parent::__construct($metadata);
        $this->entity = $restriction;
    }

    /**
     * @return mixed|string
     */
    function generate()
    {

        $service_folder = ucfirst($this->getServiceType()->getValue());
        $base_namespace = self::fqn('Ls', 'Omni', 'Client', $service_folder);
        $entity_namespace = self::fqn($base_namespace, 'Entity');

        $element = $this->entity->getElement();
        $types = $this->metadata->getTypes();

        $this->class->setName($this->entity->getName());

        $this->class->setNamespaceName($entity_namespace);

        $type = $element->getType();

        $type = $types[ $type ];

        $is_array = $type->getSoapType() == SoapType::ARRAY_OF();

        //Force those Response classes to put the iterator methods to be first
        //See Replication/Cron/AbstractReplicationTask->getIterator for how these Entity classes are used by Replication
        $typeDefinition = $type->getDefinition();

        $typeDefinitionArray = [];
        foreach ($typeDefinition as $k => $v) {
            if (strpos($v->getDataType(), "ArrayOf") !== false) {
                $typeDefinitionArray[$k] = $v;
            }
        }
        foreach ($typeDefinition as $k => $v) {
            if (strpos($v->getDataType(), "ArrayOf") !== true) {
                $typeDefinitionArray[$k] = $v;
            }
        }

        // TRAVERSE THE COMPLEX TYPE DISCOVERED BY THE WSDL PROCESSOR
        // OUR ENTITIES HAVE A NASTY MERGE SO THEM CAN WORK ON OVERLAPPING SCHEMA DEFINITIONS
        if ($typeDefinitionArray != null) {
            foreach ($typeDefinitionArray as $field_name => $field_type) {
                $field_data_type = $this->normalizeDataType($field_type->getDataType()) . ($is_array ? '[]' : '');
                $field_name_capitalized = ucfirst($field_name);

                $field_is_restriction = array_key_exists($field_data_type, $this->metadata->getRestrictions());
                if ($field_is_restriction) {
                    $this->class->addUse(self::fqn($entity_namespace, 'Enum', $field_data_type));
                }
                $this->class->addPropertyFromGenerator(PropertyGenerator::fromArray(
                    ['name' => $field_name,
                        'defaultvalue' => $is_array ? [] : null,
                        'docblock' => DocBlockGenerator::fromArray(
                            ['tags' => [new Tag\PropertyTag($field_name, [$field_data_type])]]
                        ),
                    'flags' => [PropertyGenerator::FLAG_PROTECTED]]
                ));

                $set_method_name = "set{$field_name_capitalized}";
                $get_method_name = "get{$field_name_capitalized}";

                if (!$this->class->hasMethod($set_method_name)) {
                    $set_method = new MethodGenerator();
                    $set_method->setName($set_method_name);
                    $set_method->setParameter(ParameterGenerator::fromArray(['name' => $field_name]));
                    $set_method->setDocBlock(
                        DocBlockGenerator::fromArray(['tags' => [new Tag\ParamTag($field_name, [$field_data_type]),
                        new Tag\ReturnTag(['$this',])]])
                    );
                    $set_method->setBody(<<<CODE
\$this->$field_name = \$$field_name;
return \$this;
CODE
                    );
                    if ($field_is_restriction) {
                        $set_method->setDocBlock(
                            DocBlockGenerator::fromArray(['tags' => [new Tag\ParamTag(
                                $field_name,
                                [$field_data_type, 'string']
                            ),
                                new Tag\ReturnTag(['$this']),
                            new Tag\ThrowsTag(['InvalidEnumException'])]])
                        );
                        $this->class->addUse(InvalidEnumException::class);
                        $set_method->setBody(<<<CODE
if ( ! \$$field_name instanceof $field_data_type ) {
    if ( $field_data_type::isValid( \$$field_name ) ) 
        \$$field_name = new $field_data_type( \$$field_name );
    elseif ( $field_data_type::isValidKey( \$$field_name ) ) 
        \$$field_name = new $field_data_type( constant( "$field_data_type::\$$field_name" ) );
    elseif ( ! \$$field_name instanceof $field_data_type )
        throw new InvalidEnumException();
}
\$this->$field_name = \${$field_name}->getValue();

return \$this;
CODE
                        );
                    }

                    $this->class->addMethodFromGenerator($set_method);

                    // ADD ArrayOf's ARRAY ACCESS SUPPORT
                    if ($is_array) {
                        $this->class->addUse(IteratorAggregate::class);
                        $this->class->addUse(ArrayIterator::class);
                        $this->class->setImplementedInterfaces([IteratorAggregate::class]);
                        $iterator_method = new MethodGenerator();
                        $iterator_method->setDocBlock(
                            DocBlockGenerator::fromArray(['tags' => [new Tag\ReturnTag([$field_data_type])]])
                        );
                        $iterator_method->setName('getIterator');
                        $iterator_method->setBody(<<<CODE
return new ArrayIterator( \$this->$field_name );
CODE
                        );
                        $this->class->addMethodFromGenerator($iterator_method);
                    }
                }

                if (!$this->class->hasMethod($get_method_name)) {
                    $get_method = new MethodGenerator();
                    $get_method->setName($get_method_name)
                        ->setDocBlock(
                            DocBlockGenerator::fromArray(['tags' => [new Tag\ReturnTag([$field_data_type])]])
                        );
                    $get_method->setBody(<<<CODE
return \$this->$field_name;
CODE
                    );

                    $this->class->addMethodFromGenerator($get_method);
                }
            }
        }

        // ADD REQUEST INTERFACE
        if ($element->isRequest()) {
            $this->class->addUse(RequestInterface::class);
            $this->class->setImplementedInterfaces([ RequestInterface::class ]);
        }
        // ADD RESPONSE INTERFACE
        if ($element->isResponse()) {
            $this->class->addUse(ResponseInterface::class);
            $this->class->setImplementedInterfaces([ ResponseInterface::class ]);
            foreach ($type->getDefinition() as $field_name => $field_type) {
                $field_data_type = $this->normalizeDataType($field_type->getDataType());
                $method_name = "getResult";

                if (!$this->class->hasMethod($method_name)) {
                    $method = new MethodGenerator();
                    $method->setName($method_name)
                           ->setDocBlock(
                               DocBlockGenerator::fromArray([ 'tags' => [ new Tag\ReturnTag([ $field_data_type ]) ] ])
                           );
                    $method->setBody(<<<CODE
return \$this->$field_name;
CODE
                    );

                    $this->class->addMethodFromGenerator($method);
                }
            }
        }

        if ($type->getBase()) {
            // for internal development only.
            $this->class->setExtendedClass("meannothing".$type->getBase());
        }

        $content = $this->file->generate();

        // Zend add / in the start of base class which we dont need. so replace this with blah.
        if ($type->getBase()) {
            $content = str_replace("\\meannothing".$type->getBase(), $type->getBase(), $content);
        }


        $content = str_replace('implements \\IteratorAggregate', 'implements IteratorAggregate', $content);
        $content = str_replace('implements Ls\\Omni\\Client\\RequestInterface', 'implements RequestInterface', $content);
        $content = str_replace('implements Ls\\Omni\\Client\\ResponseInterface', 'implements ResponseInterface', $content);

        return $content;
    }

    /**
     * @param string $data_type
     *
     * @return string
     */
    protected function normalizeDataType($data_type)
    {
        return array_key_exists($data_type, $this->equivalences) ? $this->equivalences[ $data_type ] : $data_type;
    }
}
