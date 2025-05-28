<?php
// @codingStandardsIgnoreFile
declare(strict_types=1);

namespace Ls\Replication\Code;

use Exception;
use Laminas\Code\Generator\AbstractMemberGenerator;
use Laminas\Code\Reflection\ClassReflection;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Omni\Service\Soap\ReplicationOperation;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\DataObject\IdentityInterface;
use ReflectionClass;
use ReflectionException;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;

/**
 * Responsible for generating Magento model classes for replication entities.
 */
class ModelGenerator extends AbstractGenerator
{
    /** @var string */
    public static string $namespace = 'Ls\\Replication\\Model';

    /** @var ReplicationOperation */
    public ReplicationOperation $operation;

    /** @var ReflectionClass */
    public ReflectionClass $reflectedEntity;

    /**
     * @param ReplicationOperation $operation
     * @throws Exception
     * @throws ReflectionException
     */
    public function __construct(ReplicationOperation $operation)
    {
        parent::__construct();
        $this->operation = $operation;
        $this->reflectedEntity = new ClassReflection($this->operation->getOmniEntityFqn());
    }

    /**
     * Get the class name to be generated.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->reflectedEntity->getShortName();
    }

    /**
     * Generate the Magento model class code.
     *
     * @return string
     * @throws ReflectionException
     */
    public function generate(): string
    {
        $interfaceName = $this->operation->getInterfaceName();

        $constructorMethod = new MethodGenerator();
        $constructorMethod->setName('_construct');
        $constructorMethod->setBody("\$this->_init('Ls\\Replication\\Model\\ResourceModel\\" . $this->operation->getModelName() . "');");

        $identitiesMethod = new MethodGenerator();
        $identitiesMethod->setName('getIdentities');
        $identitiesMethod->setBody('return [self::CACHE_TAG . \'_\' . $this->getId()];');

        $this->class->setNamespaceName(self::$namespace);
        $this->class->addUse(IdentityInterface::class);
        $this->class->addUse($this->operation->getInterfaceFqn());

        $this->class->setName($this->operation->getModelName());
        $this->class->setExtendedClass($this->operation->getOmniEntityFqn());
        $this->class->setImplementedInterfaces([$interfaceName, IdentityInterface::class]);

        $this->class->addConstant('CACHE_TAG', 'ls_replication_' . $this->operation->getTableName());
        $this->class->addProperty(
            '_cacheTag',
            'ls_replication_' . $this->operation->getTableName(),
            AbstractMemberGenerator::FLAG_PROTECTED
        );
        $this->class->addProperty(
            '_eventPrefix',
            'ls_replication_' . $this->operation->getTableName(),
            AbstractMemberGenerator::FLAG_PROTECTED
        );

        $this->class->addMethodFromGenerator($constructorMethod);
        $this->class->addMethodFromGenerator($identitiesMethod);
        $originalClass = $this->operation->getOmniEntityFqn();
        foreach ($this->reflectedEntity->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $originalClass ||
                $method->getName() == 'getDbColumnsMapping'
            ) {
                continue;
            }
            $body = '';
            preg_match('/self::([A-Z0-9_]+)/', $method->getBody(), $matches);
            if (!empty($matches)) {
                $constName = $matches[1];
                if (str_starts_with($method->getName(), 'get')) {
                    $body = <<<CODE
return \$this->getData(self::getDbColumnsMapping()[self::{$constName}]);
CODE;
                } else {
                    $body = <<<CODE
return \$this->setData(self::getDbColumnsMapping()[self::{$constName}], \$value);
CODE;
                }
            }
            $this->copyGivenMethod(
                $method->getName(),
                AbstractMemberGenerator::VISIBILITY_PUBLIC,
                false,
                $method->isStatic() ? [AbstractMemberGenerator::FLAG_STATIC] : [],
                $method->getParameters(),
                $method->getReturnType(),
                $body
            );
        }

        $this->createProperty(
            null,
            '?bool',
            [PropertyGenerator::FLAG_PROTECTED],
            ['pascal_name' => 'Processed', 'variable_name' => 'processed', 'model' => true]
        );
        $this->createProperty(
            null,
            '?bool',
            [PropertyGenerator::FLAG_PROTECTED],
            ['pascal_name' => 'IsUpdated', 'variable_name' => 'is_updated', 'model' => true]
        );
        $this->createProperty(
            null,
            '?bool',
            [PropertyGenerator::FLAG_PROTECTED],
            ['pascal_name' => 'IsFailed', 'variable_name' => 'is_failed', 'model' => true]
        );
        $this->createProperty(
            null,
            '?string',
            [PropertyGenerator::FLAG_PROTECTED],
            ['pascal_name' => 'CreatedAt', 'variable_name' => 'created_at', 'model' => true]
        );
        $this->createProperty(
            null,
            '?string',
            [PropertyGenerator::FLAG_PROTECTED],
            ['pascal_name' => 'UpdatedAt', 'variable_name' => 'updated_at', 'model' => true]
        );
        $this->createProperty(
            null,
            '?string',
            [PropertyGenerator::FLAG_PROTECTED],
            ['pascal_name' => 'IdentityValue', 'variable_name' => ReplicationHelper::UNIQUE_HASH_COLUMN_NAME, 'model' => true]
        );
        $this->createProperty(
            null,
            '?string',
            [PropertyGenerator::FLAG_PROTECTED],
            ['pascal_name' => 'Checksum', 'variable_name' => 'checksum', 'model' => true]
        );
        $this->createProperty(
            null,
            '?string',
            [PropertyGenerator::FLAG_PROTECTED],
            ['pascal_name' => 'ProcessedAt', 'variable_name' => 'processed_at', 'model' => true]
        );

        $content = $this->file->generate();

        $content = str_replace(
            'extends Magento\\Framework\\Model\\AbstractModel',
            'extends AbstractModel',
            $content
        );
        $content = str_replace("implements \\$interfaceName", "implements $interfaceName", $content);
        $content = str_replace(
            ', Magento\\Framework\\DataObject\\IdentityInterface',
            ', IdentityInterface',
            $content
        );

        return $this->removeBackSlashFromBuiltinReturnTypes($content);
    }
}
