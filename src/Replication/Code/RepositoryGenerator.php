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
use Magento\Framework\Phrase;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlock\Tag\PropertyTag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

/**
 * Class RepositoryGenerator
 * @package Ls\Replication\Code
 */
class RepositoryGenerator extends AbstractGenerator
{
    /** @var string */
    static public $namespace = 'Ls\\Replication\\Model';

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
        $interface_name = $this->operation->getRepositoryInterfaceName();
        $simple_name    = $this->operation->getEntityName();
        $entity_name    = $this->operation->getEntityName();
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
        $this->class->addUse($this->operation->getRepositoryInterfaceFqn());
        $this->class->addUse($this->operation->getInterfaceFqn());
        $this->class->addUse($this->operation->getFactoryFqn());
        $this->class->addUse($this->operation->getSearchFactoryFqn());

        $this->class->setName($this->operation->getRepositoryName());
        $this->class->setImplementedInterfaces([$interface_name]);

        $object_factory_property = new PropertyGenerator();
        $object_factory_property->setName('object_factory');
        $object_factory_property->setDefaultValue(null);
        $object_factory_property->setVisibility(PropertyGenerator::VISIBILITY_PROTECTED);
        $object_factory_property->setDocBlock(DocBlockGenerator::fromArray(
            ['tags' => [new PropertyTag('object_factory', $simple_name . 'Factory')]]
        ));

        $collection_factory_property = new PropertyGenerator();
        $collection_factory_property->setName('collection_factory');
        $collection_factory_property->setDefaultValue(null);
        $collection_factory_property->setVisibility(PropertyGenerator::VISIBILITY_PROTECTED);
        $collection_factory_property->setDocBlock(DocBlockGenerator::fromArray(
            ['tags' => [new PropertyTag('collection_factory', 'CollectionFactory')]]
        ));

        $result_factory_property = new PropertyGenerator();
        $result_factory_property->setName('result_factory');
        $result_factory_property->setDefaultValue(null);
        $result_factory_property->setVisibility(PropertyGenerator::VISIBILITY_PROTECTED);
        $result_factory_property->setDocBlock(DocBlockGenerator::fromArray(
            ['tags' => [new PropertyTag('result_factory', $this->operation->getSearchFactory())]]
        ));

        $this->class->addPropertyFromGenerator($object_factory_property);
        $this->class->addPropertyFromGenerator($collection_factory_property);
        $this->class->addPropertyFromGenerator($result_factory_property);

        $this->class->addMethodFromGenerator($this->getConstructorMethod());
        $this->class->addMethodFromGenerator($this->getGetListMethod());
        $this->class->addMethodFromGenerator($this->getSaveMethod());
        $this->class->addMethodFromGenerator($this->getGetByIdMethod());
        $this->class->addMethodFromGenerator($this->getDeleteMethod());
        $this->class->addMethodFromGenerator($this->getDeleteByIdMethod());

        $content = $this->file->generate();
        $content = str_replace(
            'Magento\\Framework\\Data\\SearchResultInterfaceFactory $result_factory',
            'SearchResultInterfaceFactory $result_factory',
            $content
        );
        $content = str_replace("implements \\$interface_name", "implements $interface_name", $content);
        $content = str_replace(
            "\\{$simple_name}Factory \$object_factory",
            "{$simple_name}Factory \$object_factory",
            $content
        );

        $content = str_replace(
            "\\{$simple_name}SearchResultsFactory \$result_factory",
            "{$simple_name}SearchResultsFactory \$result_factory",
            $content
        );

        $content = str_replace("\\{$entity_name}Interface \$object", "{$entity_name}Interface \$object", $content);
        $content = str_replace(
            '\\CollectionFactory $collection_factory',
            'CollectionFactory $collection_factory',
            $content
        );

        $content = str_replace(
            "\Magento\\Framework\\Api\\SearchCriteriaInterface \$criteria",
            "SearchCriteriaInterface \$criteria",
            $content
        );

        return $content;
    }

    /**
     * @return MethodGenerator
     */
    public function getConstructorMethod()
    {
        $method = new MethodGenerator();
        $method->setName('__construct');

        $method->setParameters([
            new ParameterGenerator(
                'object_factory',
                $this->operation->getEntityName() . 'Factory'
            ),
            new ParameterGenerator('collection_factory', 'CollectionFactory'),
            new ParameterGenerator('result_factory', $this->operation->getSearchFactory())
        ]);

        $method->setBody(<<<CODE
\$this->object_factory = \$object_factory;
\$this->collection_factory = \$collection_factory;
\$this->result_factory = \$result_factory;
CODE
        );

        return $method;
    }

    /**
     * @return MethodGenerator
     */
    public function getGetListMethod()
    {
        $method = new MethodGenerator();
        $method->setName('getList');
        $method->setParameters([new ParameterGenerator('criteria', SearchCriteriaInterface::class)]);
        $method->setBody(<<<CODE
/** @var SearchResultInterface \$results */
/** @noinspection PhpUndefinedMethodInspection */
\$results = \$this->result_factory->create();
\$results->setSearchCriteria( \$criteria );
/** @var Collection \$collection */
/** @noinspection PhpUndefinedMethodInspection */
\$collection = \$this->collection_factory->create();
foreach ( \$criteria->getFilterGroups() as \$filter_group ) {
    \$fields = [ ];
    \$conditions = [ ];
    foreach ( \$filter_group->getFilters() as \$filter ) {
        \$condition = \$filter->getConditionType() ? \$filter->getConditionType() : 'eq';
        \$fields[] = \$filter->getField();
        \$conditions[] = [ \$condition => \$filter->getValue() ];
    }
    if ( \$fields ) {
        \$collection->addFieldToFilter( \$fields, \$conditions );
    }
}
\$results->setTotalCount( \$collection->getSize() );
\$sort_orders = \$criteria->getSortOrders();
if ( \$sort_orders ) {
    /** @var SortOrder \$sort_order */
    foreach ( \$sort_orders as \$sort_order ) {
        \$collection->addOrder( \$sort_order->getField(),
                               ( \$sort_order->getDirection() == SortOrder::SORT_ASC ) ? 'ASC' : 'DESC'
        );
    }
}
\$collection->setCurPage( \$criteria->getCurrentPage() );
\$collection->setPageSize( \$criteria->getPageSize() );
\$objects = [ ];
foreach ( \$collection as \$object_model ) {
    \$objects[] = \$object_model;
}
\$results->setItems( \$objects );
\$results->setItems( \$objects );

return \$results;
CODE
        );


        return $method;
    }

    /**
     * @return MethodGenerator
     */
    public function getSaveMethod()
    {
        $method = new MethodGenerator();
        $method->setName('save');
        $method->setParameters([new ParameterGenerator('object', $this->operation->getInterfaceName())]);
        $method->setBody(<<<CODE
try {
    \$object->save();
} catch ( Exception \$e ) {
    throw new CouldNotSaveException( new Phrase( \$e->getMessage() ) );
}

return \$object;
CODE
        );

        return $method;
    }

    /**
     * @return MethodGenerator
     */
    public function getGetByIdMethod()
    {
        $method = new MethodGenerator();
        $method->setName('getById');
        $method->setParameters([new ParameterGenerator('id')]);
        $method->setBody(<<<CODE
\$object = \$this->object_factory->create();
\$object->load( \$id );
if ( ! \$object->getId() ) {
    throw new NoSuchEntityException( new Phrase( "Object with id '\$id' does not exist." ) );
}

return \$object;
CODE
        );

        return $method;
    }

    /**
     * @return MethodGenerator
     */
    public function getDeleteMethod()
    {
        $method = new MethodGenerator();
        $method->setName('delete');
        $method->setParameters([new ParameterGenerator('object', $this->operation->getInterfaceName())]);
        $method->setBody(<<<CODE
try {
    \$object->delete();
} catch ( Exception \$e) {
    throw new CouldNotDeleteException( new Phrase( \$e->getMessage() ) );
}

return TRUE;
CODE
        );

        return $method;
    }

    /**
     * @return MethodGenerator
     */
    public function getDeleteByIdMethod()
    {
        $method = new MethodGenerator();
        $method->setName('deleteById');
        $method->setParameters([new ParameterGenerator('id')]);
        $method->setBody('return $this->delete( $this->getById( $id ) );');

        return $method;
    }
}
