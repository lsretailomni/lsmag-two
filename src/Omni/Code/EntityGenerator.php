<?php
// @codingStandardsIgnoreFile
namespace Ls\Omni\Code;

use Exception;
use Laminas\Code\Generator\AbstractMemberGenerator;
use \Ls\Omni\Service\Metadata;
use \Ls\Omni\Service\Soap\Entity;
use Laminas\Code\Generator\DocBlock\Tag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Magento\Framework\DataObject;

class EntityGenerator extends AbstractOmniGenerator
{
    /** @var array */
    public $equivalences = [
        'decimal'    => 'float',
        'long'       => 'int',
        'dateTime'   => 'string',
        'char'       => 'int',
        'guid'       => 'string',
        'StreamBody' => 'string',
    ];

    /** @var Entity */
    private $entity;

    /**
     * @param Entity $restriction
     * @param Metadata $metadata
     * @throws Exception
     */
    public function __construct(Entity $restriction, Metadata $metadata)
    {
        parent::__construct($metadata);
        $this->entity = $restriction;
    }

    /**
     * Generate code
     *
     * @return string
     */
    function generate()
    {
        $service_folder   = ucfirst($this->getServiceType());
        $base_namespace   = self::fqn('Ls', 'Omni', 'Client', $service_folder);
        $entity_namespace = self::fqn($base_namespace, 'Entity');
        $this->class->setName($this->entity->getName());
        $this->class->setExtendedClass(DataObject::class);
        $this->class->setNamespaceName($entity_namespace);
        $typeDefinitionArray = $this->entity->getDefinition();
        $fieldNameMapping = [];
        if ($typeDefinitionArray != null) {
            $typeDefinitionArray = array_unique($typeDefinitionArray, SORT_REGULAR);
            foreach ($typeDefinitionArray as $field) {
                $field_name = $field['name'];
                $field_type = $field['type'];
                $field_data_type = $this->normalizeDataType($field_type);
                $field_name_optimized   = preg_replace('/[-._]/', '', $field_name);
                $field_name_optimizedForMethodName   = preg_replace('/[-._]/', ' ', $field_name);
                $field_name_capitalized = ucwords($field_name_optimizedForMethodName);
                $field_name_capitalized = str_replace(' ', '', $field_name_capitalized);
                $fieldNameMapping[$field_name_optimized] = $field_name;
                $constantName = strtoupper(preg_replace('/\B([A-Z])/', '_$1', $field_name_optimized));
                $this->class->addConstant($constantName, $field_name);
                $set_method_name = "set{$field_name_capitalized}";
                $get_method_name = "get{$field_name_capitalized}";

                if (!$this->class->hasMethod($set_method_name)) {
                    $set_method = new MethodGenerator();
                    $set_method->setName($set_method_name);
                    $set_method->setParameter(ParameterGenerator::fromArray(['name' => $field_name_optimized]));
                    $set_method->setDocBlock(
                        DocBlockGenerator::fromArray([
                            'tags' => [
                                new Tag\ParamTag($field_name_optimized, [$field_data_type]),
                                new Tag\ReturnTag(['$this',])
                            ]
                        ])
                    );
                    $set_method->setBody(<<<CODE
\$this->setData(self::$constantName, \$$field_name_optimized);
return \$this;
CODE
                    );

                    $this->class->addMethodFromGenerator($set_method);
                }

                if (!$this->class->hasMethod($get_method_name)) {
                    $get_method = new MethodGenerator();
                    $get_method->setName($get_method_name)
                        ->setDocBlock(
                            DocBlockGenerator::fromArray(['tags' => [new Tag\ReturnTag([$field_data_type])]])
                        );
                    $get_method->setBody(<<<CODE
return \$this->getData(self::$constantName);
CODE
                    );

                    $this->class->addMethodFromGenerator($get_method);
                }
            }
        }

        $this->class->addPropertyFromGenerator(PropertyGenerator::fromArray(
            [
                'name'         => 'xmlFieldMap',
                'defaultvalue' => $fieldNameMapping,
                'docblock'     => DocBlockGenerator::fromArray(
                    ['tags' => [new Tag\PropertyTag('xmlFieldMap', 'array')]]
                ),
                'flags'        => [AbstractMemberGenerator::FLAG_PUBLIC, AbstractMemberGenerator::FLAG_STATIC]
            ]
        ));

        return $this->file->generate();
    }

    /**
     * @param string $data_type
     *
     * @return string
     */
    protected function normalizeDataType($data_type)
    {
        return array_key_exists($data_type, $this->equivalences) ? $this->equivalences[$data_type] : $data_type;
    }
}
