<?php

namespace Ls\Omni\Code;

use CaseHelper\CaseHelperFactory;
use Ls\Omni\Service\Metadata;
use Ls\Omni\Service\Soap\Restriction;
use MyCLabs\Enum\Enum;
use Zend\Code\Generator\DocBlock\Tag;
use Zend\Code\Generator\DocBlockGenerator;

class RestrictionGenerator extends AbstractOmniGenerator
{
    /** @var array  */
    static private $reserved_words = ['new', 'final'];

    private $caseHelperFactory;

    /** @var array  */
    protected $equivalences = [
        'decimal' => 'float',
        'long' => 'int',
        'dateTime' => 'string',
    ];

    /** @var Restriction */
    private $restriction;

    /**
     * RestrictionGenerator constructor.
     * @param Restriction $restriction
     * @param Metadata $metadata
     * @throws \Exception
     */
    public function __construct(Restriction $restriction, Metadata $metadata)
    {
        parent::__construct($metadata);
        $this->restriction = $restriction;
        $this->case_helper = CaseHelperFactory::make(CaseHelperFactory::INPUT_TYPE_PASCAL_CASE);
    }

    /**
     * @param $name
     * @return string
     */

    function sanitizeConstantValue($name)
    {

        if (array_search(strtolower($name), self::$reserved_words) !== false) {
            $name = ucfirst($name);
            $name = "Type$name";
        }

        return $this->case_helper->toScreamingSnakeCase($name);
    }

    /**
     * @return mixed|string
     */
    function generate()
    {
        $service_folder = ucfirst($this->getServiceType()->getValue());
        $base_namespace = self::fqn('Ls', 'Omni', 'Client', $service_folder);
        $entity_namespace = self::fqn($base_namespace, 'Entity', 'Enum');
        $restriction_name = $this->restriction->getName();
        $enum_class = Enum::class;

        $this->class->setNamespaceName($entity_namespace);
        $this->class->addUse(Enum::class);
        $this->class->setName($restriction_name);
        $this->class->setExtendedClass(Enum::class);

        $docblock = '';
        foreach ($this->restriction->getDefinition() as $definition) {
            $enum_key = $this->sanitizeConstantValue($definition->getValue());
            $this->class->addConstant($enum_key, $definition->getValue());
            $docblock .= "@\$method static $restriction_name $enum_key()\n";
        }
        $this->class->setDocBlock(DocBlockGenerator::fromArray(['shortdescription' => $docblock]));

        $content = $this->file->generate();

        $content = str_replace("extends {$enum_class}", 'extends Enum', $content);

        return $content;
    }

    /**
     * @param $data_type
     * @return mixed
     */
    protected function normalizeDataType($data_type)
    {
        return array_key_exists($data_type, $this->equivalences) ? $this->equivalences[$data_type] : $data_type;
    }
}
