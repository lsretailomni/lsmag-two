<?php
// @codingStandardsIgnoreFile
declare(strict_types=1);

namespace Ls\Replication\Code;

use Exception;
use Laminas\Code\Generator\AbstractMemberGenerator;
use Laminas\Code\Generator\MethodGenerator;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Omni\Service\Soap\ReplicationOperation;
use \Ls\Replication\Helper\ReplicationHelper;
use ReflectionClass;
use ReflectionException;

/**
 * Responsible for generating interface code for replication model entities.
 */
class ModelInterfaceGenerator extends AbstractGenerator
{
    /** @var string */
    public static string $namespace = "Ls\\Replication\\Api\\Central\\Data";

    /** @var string */
    public string $entityFqn;

    /** @var ReflectionClass */
    public ReflectionClass $reflectedEntity;

    /** @var ReplicationOperation */
    public ReplicationOperation $operation;

    /**
     * @param ReplicationOperation $operation
     * @throws Exception
     * @throws ReflectionException
     */
    public function __construct(ReplicationOperation $operation)
    {
        parent::__construct();
        $this->class = new InterfaceGenerator();
        $this->file->setClass($this->class);
        $this->operation = $operation;
        $this->entityFqn = $this->operation->getOmniEntityFqn();
        $this->reflectedEntity = new ReflectionClass($this->entityFqn);
    }

    /**
     * Generate the interface content.
     *
     * @return string
     */
    public function generate(): string
    {
        $originalClass = $this->entityFqn;
        $this->class->setNamespaceName(self::$namespace);
        $this->class->setName($this->getName());
        if ($this->getName() == 'ReplCountryviewInterface') {
            $x1 = 1;
        }
        foreach ($this->reflectedEntity->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $originalClass) {
                continue;
            }

            $this->copyGivenMethod(
                $method->getName(),
                MethodGenerator::VISIBILITY_PUBLIC,
                false,
                $method->isStatic() ? [AbstractMemberGenerator::FLAG_STATIC] : [],
                $method->getParameters(),
                $method->getReturnType()
            );
        }

        $this->createProperty(
            null,
            '?bool',
            [MethodGenerator::VISIBILITY_PUBLIC],
            ['pascal_name' => 'Processed', 'variable_name' => 'processed', 'interface' => true]
        );
        $this->createProperty(
            null,
            '?bool',
            [MethodGenerator::VISIBILITY_PUBLIC],
            ['pascal_name' => 'IsUpdated', 'variable_name' => 'is_updated', 'interface' => true]
        );
        $this->createProperty(
            null,
            '?bool',
            [MethodGenerator::VISIBILITY_PUBLIC],
            ['pascal_name' => 'IsFailed', 'variable_name' => 'is_failed', 'interface' => true]
        );
        $this->createProperty(
            null,
            '?string',
            [MethodGenerator::VISIBILITY_PUBLIC],
            ['pascal_name' => 'CreatedAt', 'variable_name' => 'created_at', 'interface' => true]
        );
        $this->createProperty(
            null,
            '?string',
            [MethodGenerator::VISIBILITY_PUBLIC],
            ['pascal_name' => 'UpdatedAt', 'variable_name' => 'updated_at', 'interface' => true]
        );
        $this->createProperty(
            null,
            '?string',
            [MethodGenerator::VISIBILITY_PUBLIC],
            ['pascal_name' => 'IdentityValue', 'variable_name' => ReplicationHelper::UNIQUE_HASH_COLUMN_NAME, 'interface' => true]
        );
        $this->createProperty(
            null,
            '?string',
            [MethodGenerator::VISIBILITY_PUBLIC],
            ['pascal_name' => 'Checksum', 'variable_name' => 'checksum', 'interface' => true]
        );
        $this->createProperty(
            null,
            '?string',
            [MethodGenerator::VISIBILITY_PUBLIC],
            ['pascal_name' => 'ProcessedAt', 'variable_name' => 'processed_at', 'interface' => true]
        );

        $content = $this->file->generate();

        // Replace empty body with semicolon
        $content = preg_replace('/\s+{\s+}+/', ";", $content);

        return $this->removeBackSlashFromBuiltinReturnTypes($content);
    }

    /**
     * Get the interface name from the replication operation.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->operation->getInterfaceName();
    }
}
