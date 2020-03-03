<?php
// @codingStandardsIgnoreFile

namespace Ls\Replication\Code;

use Exception;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Omni\Service\Soap\ReplicationOperation;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Phrase;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlock\Tag\PropertyTag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

/**
 * Class RepositoryTestGenerator
 * @package Ls\Replication\Code
 */
class RepositoryTestGenerator extends AbstractGenerator
{
    /** @var string */
    public static $namespace = 'Ls\\Replication\\Test\\Unit\\Model';

    /** @var ReplicationOperation */
    protected $operation;

    /** @var FileGenerator */
    protected $file;

    /** @var ClassGenerator */
    protected $class;

    /**
     * RepositoryGenerator constructor.
     * @param ReplicationOperation $operation
     * @throws Exception
     */
    public function __construct(ReplicationOperation $operation)
    {
        parent::__construct();
        $this->operation = $operation;
    }

    /**
     * @return mixed|string
     */
    public function generate()
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
        $this->class->setExtendedClass('PHPUnit\Framework\TestCase');

        $objectFactoryProperty = new PropertyGenerator();
        $objectFactoryProperty->setName('objectFactory');
        $objectFactoryProperty->setDefaultValue(null);
        $objectFactoryProperty->setVisibility(PropertyGenerator::VISIBILITY_PROTECTED);
        $objectFactoryProperty->setDocBlock(DocBlockGenerator::fromArray(
            ['tags' => [new PropertyTag('objectFactory', $entityName . 'Factory')]]
        ));

        $collectionFactoryProperty = new PropertyGenerator();
        $collectionFactoryProperty->setName('collectionFactory');
        $collectionFactoryProperty->setDefaultValue(null);
        $collectionFactoryProperty->setVisibility(PropertyGenerator::VISIBILITY_PROTECTED);
        $collectionFactoryProperty->setDocBlock(DocBlockGenerator::fromArray(
            ['tags' => [new PropertyTag('collectionFactory', 'CollectionFactory')]]
        ));

        $resultFactoryProperty = new PropertyGenerator();
        $resultFactoryProperty->setName('resultFactory');
        $resultFactoryProperty->setDefaultValue(null);
        $resultFactoryProperty->setVisibility(PropertyGenerator::VISIBILITY_PROTECTED);
        $resultFactoryProperty->setDocBlock(DocBlockGenerator::fromArray(
            ['tags' => [new PropertyTag('resultFactory', $this->operation->getSearchFactory())]]
        ));

        $this->class->addPropertyFromGenerator($objectFactoryProperty);
        $this->class->addPropertyFromGenerator($collectionFactoryProperty);
        $this->class->addPropertyFromGenerator($resultFactoryProperty);

        $modelProperty = new PropertyGenerator();
        $modelProperty->setName('model');
        $modelProperty->setVisibility(PropertyGenerator::VISIBILITY_PRIVATE);
        $modelProperty->setDocBlock(DocBlockGenerator::fromArray(
            ['tags' => [new PropertyTag('model', $this->operation->getRepositoryName())]]
        ));

        $entityInterfaceProperty = new PropertyGenerator();
        $entityInterfaceProperty->setName('entityInterface');
        $entityInterfaceProperty->setVisibility(PropertyGenerator::VISIBILITY_PRIVATE);
        $entityInterfaceProperty->setDocBlock(DocBlockGenerator::fromArray(
            ['tags' => [new PropertyTag('entityInterface', $this->operation->getInterfaceName())]]
        ));

        $entitySearchResultsInterfaceProperty = new PropertyGenerator();
        $entitySearchResultsInterfaceProperty->setName('entitySearchResultsInterface');
        $entitySearchResultsInterfaceProperty->setVisibility(PropertyGenerator::VISIBILITY_PRIVATE);
        $entitySearchResultsInterfaceProperty->setDocBlock(DocBlockGenerator::fromArray(
            ['tags' => [new PropertyTag('entitySearchResultsInterface', $this->operation->getSearchInterfaceName())]]
        ));

        $this->class->addPropertyFromGenerator($modelProperty);
        $this->class->addPropertyFromGenerator($entityInterfaceProperty);
        $this->class->addPropertyFromGenerator($entitySearchResultsInterfaceProperty);

        $this->class->addMethodFromGenerator($this->getSetUpMethod());
        $this->class->addMethodFromGenerator($this->getGetByIdMethod());
        $this->class->addMethodFromGenerator($this->getGetWithNoSuchEntityExceptionMethod());
        $this->class->addMethodFromGenerator($this->getGetListMethod());
        $this->class->addMethodFromGenerator($this->getSaveMethod());
        $content = $this->file->generate();
        return $content;
    }

    /**
     * @return MethodGenerator
     */
    public function getSetUpMethod()
    {
        $method                       = new MethodGenerator();
        $entityName                   = $this->operation->getEntityName();
        $objectFactory                = $entityName . 'Factory';
        $searchFactoryProperty        = $this->operation->getSearchFactory();
        $entityInterface              = $this->operation->getInterfaceName();
        $entityRepository             = $this->operation->getRepositoryName();
        $entitySearchResultsInterface = $this->operation->getSearchInterfaceName();
        $method->setName('setUp');
        $method->setBody(
            <<<CODE
\$this->objectFactory = \$this->createPartialMock($objectFactory::class, ['create']);
\$this->collectionFactory = \$this->createMock(CollectionFactory::class);
\$this->resultFactory = \$this->createMock($searchFactoryProperty::class);
\$this->entityInterface = \$this->createMock($entityInterface::class);
\$this->entitySearchResultsInterface = \$this->createMock($entitySearchResultsInterface::class);
\$this->model = new $entityRepository(
        \$this->objectFactory,
        \$this->collectionFactory,
        \$this->resultFactory
);
CODE
        );
        return $method;
    }

    /**
     * @return MethodGenerator
     */
    public function getGetByIdMethod()
    {
        $method           = new MethodGenerator();
        $entityRepository = $this->operation->getRepositoryName();
        $method->setName('testGetById');
        $method->setBody(
            <<<CODE
\$entityId = 1;
\$entityMock = \$this->createMock($entityRepository::class);
\$entityMock->method('getById')
     ->with(\$entityId)
     ->willReturn(\$entityId);
\$this->assertEquals(\$entityId, \$entityMock->getById(\$entityId));
CODE
        );
        return $method;
    }

    /**
     * @return MethodGenerator
     */
    public function getGetWithNoSuchEntityExceptionMethod()
    {
        $method           = new MethodGenerator();
        $entityRepository = $this->operation->getRepositoryName();
        $method->setName('testGetWithNoSuchEntityException');
        $method->setDocBlock('@expectedException \Magento\Framework\Exception\NoSuchEntityException 
@expectedExceptionMessage Object with id 1 does not exist.');
        $method->setBody(
            <<<CODE
\$entityId = 1;
\$entityMock = \$this->createMock($entityRepository::class);
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
     * @return MethodGenerator
     */
    public function getGetListMethod()
    {
        $method           = new MethodGenerator();
        $entityRepository = $this->operation->getRepositoryName();
        $method->setName('testGetListWithSearchCriteria');
        $method->setBody(
            <<<CODE
\$searchCriteria = \$this->getMockBuilder(SearchCriteriaInterface::class)->getMock();
\$entityMock = \$this->createMock($entityRepository::class);
\$entityMock->method('getList')
     ->with(\$searchCriteria)
     ->willReturn(\$this->entitySearchResultsInterface);
\$this->assertEquals(\$this->entitySearchResultsInterface, \$entityMock->getList(\$searchCriteria));
CODE
        );
        return $method;
    }

    /**
     * @return MethodGenerator
     */
    public function getSaveMethod()
    {
        $method           = new MethodGenerator();
        $entityRepository = $this->operation->getRepositoryName();
        $method->setName('testSave');
        $method->setBody(
            <<<CODE
\$entityMock = \$this->createMock($entityRepository::class);
\$entityMock->method('save')
     ->with(\$this->entityInterface)
     ->willReturn(\$this->entityInterface);
\$this->assertEquals(\$this->entityInterface, \$entityMock->save(\$this->entityInterface));
CODE
        );
        return $method;
    }
}
