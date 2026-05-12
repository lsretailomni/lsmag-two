<?php
// @codingStandardsIgnoreFile
declare(strict_types=1);

namespace Ls\Replication\Code;

use Exception;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Omni\Service\Soap\ReplicationOperation;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Laminas\Code\Generator\DocBlock\Tag\PropertyTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;

/**
 * Generates PHPUnit tests for a given Magento repository.
 */
class RepositoryTestGenerator extends AbstractGenerator
{
    /**
     * Target namespace for generated test class.
     *
     * @var string
     */
    public static string $namespace = 'Ls\\Replication\\Test\\Unit\\Model\\Central';

    /**
     * @param ReplicationOperation $operation
     * @throws Exception
     */
    public function __construct(public ReplicationOperation $operation)
    {
        parent::__construct();
    }

    /**
     * Generate the PHPUnit test class.
     *
     * @return string
     */
    public function generate(): string
    {
        $entityName = $this->operation->getEntityName();
        $this->class->setNamespaceName(self::$namespace);
        $this->class->addUse(CouldNotDeleteException::class);
        $this->class->addUse(CouldNotSaveException::class);
        $this->class->addUse(NoSuchEntityException::class);
        $this->class->addUse(SearchCriteriaInterface::class);
        $this->class->addUse(Exception::class);
        $this->class->addUse(Phrase::class);
        $this->class->addUse(SortOrder::class);
        $this->class->addUse(TestCase::class);
        $this->class->addUse(ObjectManager::class);
        $this->class->addUse("\\" . $this->operation->getRepositoryFqn());
        $this->class->addUse("\\" . $this->operation->getRepositoryInterfaceFqn());
        $this->class->addUse("\\" . $this->operation->getResourceCollectionFqn());
        $this->class->addUse("\\" . $this->operation->getResourceCollectionFactoryFqn());
        $this->class->addUse("\\" . $this->operation->getRepositoryInterfaceFqn());
        $this->class->addUse("\\" . $this->operation->getInterfaceFqn());
        $this->class->addUse("\\" . $this->operation->getSearchInterfaceFqn());
        $this->class->addUse("\\" . $this->operation->getFactoryFqn());
        $this->class->addUse("\\" . $this->operation->getSearchFactoryFqn());
        $this->class->setName($this->operation->getRepositoryTestName());
        $this->class->setExtendedClass(TestCase::class);

        // Object Factory property
        $objectFactoryProperty = new PropertyGenerator();
        $objectFactoryProperty->setName('objectFactory');
        $objectFactoryProperty->setDefaultValue(null);
        $objectFactoryProperty->setVisibility(PropertyGenerator::VISIBILITY_PROTECTED);
        $objectFactoryProperty->setDocBlock(DocBlockGenerator::fromArray([
            'tags' => [new PropertyTag('objectFactory', $entityName . 'Factory')]
        ]));

        // Collection Factory property
        $collectionFactoryProperty = new PropertyGenerator();
        $collectionFactoryProperty->setName('collectionFactory');
        $collectionFactoryProperty->setDefaultValue(null);
        $collectionFactoryProperty->setVisibility(PropertyGenerator::VISIBILITY_PROTECTED);
        $collectionFactoryProperty->setDocBlock(DocBlockGenerator::fromArray([
            'tags' => [new PropertyTag('collectionFactory', 'CollectionFactory')]
        ]));

        // Result Factory property
        $resultFactoryProperty = new PropertyGenerator();
        $resultFactoryProperty->setName('resultFactory');
        $resultFactoryProperty->setDefaultValue(null);
        $resultFactoryProperty->setVisibility(PropertyGenerator::VISIBILITY_PROTECTED);
        $resultFactoryProperty->setDocBlock(DocBlockGenerator::fromArray([
            'tags' => [new PropertyTag('resultFactory', $this->operation->getSearchFactory())]
        ]));

        $this->class->addPropertyFromGenerator($objectFactoryProperty);
        $this->class->addPropertyFromGenerator($collectionFactoryProperty);
        $this->class->addPropertyFromGenerator($resultFactoryProperty);

        // Model property
        $modelProperty = new PropertyGenerator();
        $modelProperty->setName('model');
        $modelProperty->setVisibility(PropertyGenerator::VISIBILITY_PRIVATE);
        $modelProperty->setDocBlock(DocBlockGenerator::fromArray([
            'tags' => [new PropertyTag('model', $this->operation->getRepositoryName())]
        ]));

        // Entity interface property
        $entityInterfaceProperty = new PropertyGenerator();
        $entityInterfaceProperty->setName('entityInterface');
        $entityInterfaceProperty->setVisibility(PropertyGenerator::VISIBILITY_PRIVATE);
        $entityInterfaceProperty->setDocBlock(DocBlockGenerator::fromArray([
            'tags' => [new PropertyTag('entityInterface', $this->operation->getInterfaceName())]
        ]));

        // Search results interface property
        $entitySearchResultsInterfaceProperty = new PropertyGenerator();
        $entitySearchResultsInterfaceProperty->setName('entitySearchResultsInterface');
        $entitySearchResultsInterfaceProperty->setVisibility(PropertyGenerator::VISIBILITY_PRIVATE);
        $entitySearchResultsInterfaceProperty->setDocBlock(DocBlockGenerator::fromArray([
            'tags' => [new PropertyTag('entitySearchResultsInterface', $this->operation->getSearchInterfaceName())]
        ]));

        $this->class->addPropertyFromGenerator($modelProperty);
        $this->class->addPropertyFromGenerator($entityInterfaceProperty);
        $this->class->addPropertyFromGenerator($entitySearchResultsInterfaceProperty);

        // Add test methods
        $this->class->addMethodFromGenerator($this->getSetUpMethod());
        $this->class->addMethodFromGenerator($this->getGetByIdMethod());
        $this->class->addMethodFromGenerator($this->getGetWithNoSuchEntityExceptionMethod());
        $this->class->addMethodFromGenerator($this->getGetListMethod());
        $this->class->addMethodFromGenerator($this->getSaveMethod());
        $this->class->addMethodFromGenerator($this->getSaveWithCouldNotSaveExceptionMethod());

        return $this->file->generate();
    }

    /**
     * Generate setUp method.
     *
     * @return MethodGenerator
     */
    public function getSetUpMethod(): MethodGenerator
    {
        $method = new MethodGenerator();
        $method->setReturnType('void');
        $method->setName('setUp');

        $entityName = $this->operation->getEntityName();
        $objectFactory = $entityName . 'Factory';
        $searchFactory = $this->operation->getSearchFactory();
        $entityInterface = $this->operation->getInterfaceName();
        $repository = $this->operation->getRepositoryName();
        $searchResultsInterface = $this->operation->getSearchInterfaceName();

        $method->setBody(
            <<<CODE
\$this->objectFactory = \$this->createPartialMock($objectFactory::class, ['create']);
\$this->collectionFactory = \$this->createMock(CollectionFactory::class);
\$this->resultFactory = \$this->createMock($searchFactory::class);
\$this->entityInterface = \$this->createMock($entityInterface::class);
\$this->entitySearchResultsInterface = \$this->createMock($searchResultsInterface::class);
\$this->model = new $repository(
    \$this->objectFactory,
    \$this->collectionFactory,
    \$this->resultFactory
);
CODE
        );

        return $method;
    }

    /**
     * Generate test method for getById.
     *
     * @return MethodGenerator
     */
    public function getGetByIdMethod(): MethodGenerator
    {
        $method = new MethodGenerator();
        $method->setName('testGetById');
        $repository = $this->operation->getRepositoryName();

        $method->setBody(
            <<<CODE
\$entityId = 1;
\$entityMock = \$this->createMock($repository::class);
\$entityMock->method('getById')
    ->with(\$entityId)
    ->willReturn(\$entityId);
\$this->assertEquals(\$entityId, \$entityMock->getById(\$entityId));
CODE
        );

        return $method;
    }

    /**
     * Generate test method for NoSuchEntityException.
     *
     * @return MethodGenerator
     */
    public function getGetWithNoSuchEntityExceptionMethod(): MethodGenerator
    {
        $method = new MethodGenerator();
        $method->setName('testGetWithNoSuchEntityException');
        $repository = $this->operation->getRepositoryName();

        $method->setBody(
            <<<CODE
\$this->expectExceptionMessage("Object with id 1 does not exist.");
\$this->expectException(\\Magento\\Framework\\Exception\\NoSuchEntityException::class);
\$entityId = 1;
\$entityMock = \$this->createMock($repository::class);
\$entityMock->method('getById')
    ->with(\$entityId)
    ->willThrowException(
        new NoSuchEntityException(
            new Phrase('Object with id ' . \$entityId . ' does not exist.')
        )
    );
\$entityMock->getById(\$entityId);
CODE
        );

        return $method;
    }

    /**
     * Generate test method for getList with SearchCriteria.
     *
     * @return MethodGenerator
     */
    public function getGetListMethod(): MethodGenerator
    {
        $method = new MethodGenerator();
        $method->setName('testGetListWithSearchCriteria');
        $repository = $this->operation->getRepositoryName();

        $method->setBody(
            <<<CODE
\$searchCriteria = \$this->getMockBuilder(SearchCriteriaInterface::class)->getMock();
\$entityMock = \$this->createMock($repository::class);
\$entityMock->method('getList')
    ->with(\$searchCriteria)
    ->willReturn(\$this->entitySearchResultsInterface);
\$this->assertEquals(\$this->entitySearchResultsInterface, \$entityMock->getList(\$searchCriteria));
CODE
        );

        return $method;
    }

    /**
     * Generate test method for save.
     *
     * @return MethodGenerator
     */
    public function getSaveMethod(): MethodGenerator
    {
        $method = new MethodGenerator();
        $method->setName('testSave');
        $repository = $this->operation->getRepositoryName();

        $method->setBody(
            <<<CODE
\$entityMock = \$this->createMock($repository::class);
\$entityMock->method('save')
    ->with(\$this->entityInterface)
    ->willReturn(\$this->entityInterface);
\$this->assertEquals(\$this->entityInterface, \$entityMock->save(\$this->entityInterface));
CODE
        );

        return $method;
    }

    /**
     * Generate test method for save failure.
     *
     * @return MethodGenerator
     */
    public function getSaveWithCouldNotSaveExceptionMethod(): MethodGenerator
    {
        $method = new MethodGenerator();
        $method->setName('testSaveWithCouldNotSaveException');
        $repository = $this->operation->getRepositoryName();

        $method->setBody(
            <<<CODE
\$this->expectExceptionMessage("Could not save entity");
\$this->expectException(\\Magento\\Framework\\Exception\\CouldNotSaveException::class);
\$entityMock = \$this->createMock($repository::class);
\$entityMock->method('save')
    ->with(\$this->entityInterface)
    ->willThrowException(
        new CouldNotSaveException(
            __('Could not save entity')
        )
    );
\$entityMock->save(\$this->entityInterface);
CODE
        );

        return $method;
    }
}
