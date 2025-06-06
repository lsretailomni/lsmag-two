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
use Laminas\Code\Generator\DocBlock\Tag\PropertyTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\PropertyGenerator;

/**
 * Generates a repository class for a given replication operation.
 */
class RepositoryGenerator extends AbstractGenerator
{
    /**
     * Namespace of the generated repository class.
     *
     * @var string
     */
    public static string $namespace = 'Ls\\Replication\\Model';

    /**
     * Replication operation instance.
     *
     * @var ReplicationOperation
     */
    public ReplicationOperation $operation;

    /**
     * @param ReplicationOperation $operation
     * @throws Exception
     */
    public function __construct(ReplicationOperation $operation)
    {
        parent::__construct();
        $this->operation = $operation;
    }

    /**
     * Generate the repository class content.
     *
     * @return string
     * @throws Exception
     */
    public function generate(): string
    {
        $interfaceName = $this->operation->getRepositoryInterfaceName();
        $simpleName    = $this->operation->getModelName();
        $entityName    = $this->operation->getModelName();

        $this->class->setNamespaceName(self::$namespace);
        $this->class->addUse(CouldNotDeleteException::class);
        $this->class->addUse(CouldNotSaveException::class);
        $this->class->addUse(NoSuchEntityException::class);
        $this->class->addUse(SearchCriteriaInterface::class);
        $this->class->addUse(Exception::class);
        $this->class->addUse(Phrase::class);
        $this->class->addUse(SortOrder::class);
        $this->class->addUse($this->operation->getRepositoryInterfaceFqn());
        $this->class->addUse($this->operation->getResourceCollectionFqn());
        $this->class->addUse($this->operation->getResourceCollectionFactoryFqn());
        $this->class->addUse($this->operation->getInterfaceFqn());
        $this->class->addUse($this->operation->getFactoryFqn());
        $this->class->addUse($this->operation->getSearchFactoryFqn());

        $this->class->setName($this->operation->getRepositoryName());
        $this->class->setImplementedInterfaces([$interfaceName]);

        $objectFactory = new PropertyGenerator('objectFactory', null);
        $objectFactory->setDocBlock(DocBlockGenerator::fromArray([
            'tags' => [new PropertyTag('objectFactory', $simpleName . 'Factory')]
        ]));

        $collectionFactory = new PropertyGenerator('collectionFactory', null);
        $collectionFactory->setDocBlock(DocBlockGenerator::fromArray([
            'tags' => [new PropertyTag('collectionFactory', 'CollectionFactory')]
        ]));

        $resultFactory = new PropertyGenerator('resultFactory', null);
        $resultFactory->setDocBlock(DocBlockGenerator::fromArray([
            'tags' => [new PropertyTag('resultFactory', $this->operation->getSearchFactory())]
        ]));

        $this->class->addPropertyFromGenerator($objectFactory);
        $this->class->addPropertyFromGenerator($collectionFactory);
        $this->class->addPropertyFromGenerator($resultFactory);

        $this->class->addMethodFromGenerator($this->getConstructorMethod());
        $this->class->addMethodFromGenerator($this->getGetListMethod());
        $this->class->addMethodFromGenerator($this->getSaveMethod());
        $this->class->addMethodFromGenerator($this->getGetByIdMethod());
        $this->class->addMethodFromGenerator($this->getDeleteMethod());
        $this->class->addMethodFromGenerator($this->getDeleteByIdMethod());

        $content = $this->file->generate();

        $content = str_replace(
            'Magento\\Framework\\Data\\SearchResultInterfaceFactory $result_factory',
            'SearchResultInterfaceFactory $resultFactory',
            $content
        );
        $content = str_replace("implements \\$interfaceName", "implements $interfaceName", $content);
        $content = str_replace("\\{$simpleName}Factory \$objectFactory", "{$simpleName}Factory \$objectFactory", $content);
        $content = str_replace("\\{$simpleName}SearchResultsFactory \$resultFactory", "{$simpleName}SearchResultsFactory \$resultFactory", $content);
        $content = str_replace("\\{$entityName}Interface \$object", "{$entityName}Interface \$object", $content);
        $content = str_replace('\\CollectionFactory $collectionFactory', 'CollectionFactory $collectionFactory', $content);
        $content = str_replace('\Magento\Framework\Api\SearchCriteriaInterface $criteria', 'SearchCriteriaInterface $criteria', $content);

        return $content;
    }

    /**
     * Generate constructor method.
     *
     * @return MethodGenerator
     */
    public function getConstructorMethod(): MethodGenerator
    {
        $method = new MethodGenerator('__construct');
        $method->setParameters([
            new ParameterGenerator('objectFactory', $this->operation->getModelName() . 'Factory'),
            new ParameterGenerator('collectionFactory', 'CollectionFactory'),
            new ParameterGenerator('resultFactory', $this->operation->getSearchFactory())
        ]);

        $method->setBody(
            <<<CODE
\$this->objectFactory = \$objectFactory;
\$this->collectionFactory = \$collectionFactory;
\$this->resultFactory = \$resultFactory;
CODE
        );

        return $method;
    }

    /**
     * Generate getList method.
     *
     * @return MethodGenerator
     */
    public function getGetListMethod(): MethodGenerator
    {
        $method = new MethodGenerator('getList');
        $method->setParameters([new ParameterGenerator('criteria', SearchCriteriaInterface::class)]);

        $method->setBody(
            <<<CODE
/** @var SearchResultInterface \$results */
\$results = \$this->resultFactory->create();
\$results->setSearchCriteria(\$criteria);

/** @var Collection \$collection */
\$collection = \$this->collectionFactory->create();
foreach (\$criteria->getFilterGroups() as \$filterGroup) {
    \$fields = [];
    \$conditions = [];
    foreach (\$filterGroup->getFilters() as \$filter) {
        \$condition = \$filter->getConditionType() ?: 'eq';
        \$fields[] = \$filter->getField();
        \$conditions[] = [\$condition => \$filter->getValue()];
    }
    if (\$fields) {
        \$collection->addFieldToFilter(\$fields, \$conditions);
    }
}
\$results->setTotalCount(\$collection->getSize());

\$sortOrders = \$criteria->getSortOrders();
if (\$sortOrders) {
    foreach (\$sortOrders as \$sortOrder) {
        \$collection->addOrder(
            \$sortOrder->getField(),
            (\$sortOrder->getDirection() === SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
        );
    }
}

\$collection->setCurPage(\$criteria->getCurrentPage());
\$collection->setPageSize(\$criteria->getPageSize());

\$objects = [];
foreach (\$collection as \$objectModel) {
    \$objects[] = \$objectModel;
}
\$results->setItems(\$objects);

return \$results;
CODE
        );

        return $method;
    }

    /**
     * Generate save method.
     *
     * @return MethodGenerator
     */
    public function getSaveMethod(): MethodGenerator
    {
        $method = new MethodGenerator('save');
        $method->setParameters([new ParameterGenerator('object', $this->operation->getInterfaceName())]);

        $method->setBody(
            <<<CODE
try {
    \$object->save();
} catch (Exception \$e) {
    throw new CouldNotSaveException(new Phrase(\$e->getMessage()));
}

return \$object;
CODE
        );

        return $method;
    }

    /**
     * Generate getById method.
     *
     * @return MethodGenerator
     */
    public function getGetByIdMethod(): MethodGenerator
    {
        $method = new MethodGenerator('getById');
        $method->setParameters([new ParameterGenerator('id')]);

        $method->setBody(
            <<<CODE
\$object = \$this->objectFactory->create();
\$object->load(\$id);
if (!\$object->getId()) {
    throw new NoSuchEntityException(new Phrase("Object with id '\$id' does not exist."));
}

return \$object;
CODE
        );

        return $method;
    }

    /**
     * Generate delete method.
     *
     * @return MethodGenerator
     */
    public function getDeleteMethod(): MethodGenerator
    {
        $method = new MethodGenerator('delete');
        $method->setParameters([new ParameterGenerator('object', $this->operation->getInterfaceName())]);

        $method->setBody(
            <<<CODE
try {
    \$object->delete();
} catch (Exception \$e) {
    throw new CouldNotDeleteException(new Phrase(\$e->getMessage()));
}

return true;
CODE
        );

        return $method;
    }

    /**
     * Generate deleteById method.
     *
     * @return MethodGenerator
     */
    public function getDeleteByIdMethod(): MethodGenerator
    {
        $method = new MethodGenerator('deleteById');
        $method->setParameters([new ParameterGenerator('id')]);

        $method->setBody('return $this->delete($this->getById($id));');

        return $method;
    }
}
