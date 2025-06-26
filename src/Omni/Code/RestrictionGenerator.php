<?php
declare(strict_types=1);

namespace Ls\Omni\Code;

use CaseHelper\CaseHelperFactory;
use Exception;
use \Ls\Omni\Service\Metadata;
use \Ls\Omni\Service\Soap\Restriction;
use MyCLabs\Enum\Enum;
use Laminas\Code\Generator\DocBlockGenerator;

class RestrictionGenerator extends AbstractOmniGenerator
{
    /** @var array Reserved words that need special handling */
    private static $reservedWords = ['new', 'final'];

    /** @var CaseHelperFactory */
    private $caseHelperFactory;

    /** @var array Data type equivalences for normalization */
    public $equivalences = [
        'decimal'  => 'float',
        'long'     => 'int',
        'dateTime' => 'string',
    ];

    /**
     * @param Restriction $restriction
     * @param Metadata $metadata
     * @throws Exception
     */
    public function __construct(public Restriction $restriction, Metadata $metadata)
    {
        parent::__construct($metadata);
        $this->caseHelperFactory = CaseHelperFactory::make(CaseHelperFactory::INPUT_TYPE_PASCAL_CASE);
    }

    /**
     * Sanitizes a constant name by checking for reserved words and converting it to a valid constant name.
     *
     * @param string $name The name to sanitize.
     * @return string The sanitized constant name.
     */
    public function sanitizeConstantValue($name)
    {
        if ($name && array_search(strtolower($name), self::$reservedWords) !== false) {
            $name = ucfirst($name);
            $name = "Type$name";
        }

        return $this->caseHelperFactory->toScreamingSnakeCase($name);
    }

    /**
     * Generates the enum class based on the restriction definition.
     *
     * @return string The generated enum class content.
     */
    public function generate()
    {
        $serviceFolder = ucfirst($this->getServiceType()->getValue());
        $baseNamespace = self::fqn('Ls', 'Omni', 'Client', $serviceFolder);
        $entityNamespace = self::fqn($baseNamespace, 'Entity', 'Enum');
        $restrictionName = $this->restriction->getName();
        $enumClass = Enum::class;

        // Set up class namespace and imports
        $this->class->setNamespaceName($entityNamespace);
        $this->class->addUse(Enum::class);
        $this->class->setName($restrictionName);
        $this->class->setExtendedClass(Enum::class);

        // Add constants based on the restriction definition
        $docblock = '';
        foreach ($this->restriction->getDefinition() as $definition) {
            $enumKey = $this->sanitizeConstantValue($definition->getValue());
            $this->class->addConstant($enumKey, $definition->getValue());
            $docblock .= "@\$method static $restrictionName $enumKey()\n";
        }
        $this->class->setDocBlock(DocBlockGenerator::fromArray(['shortdescription' => $docblock]));

        // Generate class content and replace Enum class reference
        $content = $this->file->generate();
        $content = str_replace("extends {$enumClass}", 'extends Enum', $content);

        return $content;
    }

    /**
     * Normalizes a data type to its equivalent type based on predefined mappings.
     *
     * @param string $dataType The data type to normalize.
     * @return mixed The normalized data type.
     */
    protected function normalizeDataType($dataType)
    {
        return array_key_exists($dataType, $this->equivalences) ? $this->equivalences[$dataType] : $dataType;
    }
}
