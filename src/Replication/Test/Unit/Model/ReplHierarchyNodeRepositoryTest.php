<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Replication\Test\Unit\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Api\SearchCriteriaInterface;
use Exception;
use Magento\Framework\Phrase;
use Magento\Framework\Api\SortOrder;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use \Ls\Replication\Model\ReplHierarchyNodeRepository;
use \Ls\Replication\Model\ResourceModel\ReplHierarchyNode\Collection;
use \Ls\Replication\Model\ResourceModel\ReplHierarchyNode\CollectionFactory;
use \Ls\Replication\Api\ReplHierarchyNodeRepositoryInterface;
use \Ls\Replication\Api\Data\ReplHierarchyNodeInterface;
use \Ls\Replication\Api\Data\ReplHierarchyNodeSearchResultsInterface;
use \Ls\Replication\Model\ReplHierarchyNodeFactory;
use \Ls\Replication\Model\ReplHierarchyNodeSearchResultsFactory;

class ReplHierarchyNodeRepositoryTest extends TestCase
{
    /**
     * @property ReplHierarchyNodeFactory $objectFactory
     */
    protected $objectFactory = null;

    /**
     * @property CollectionFactory $collectionFactory
     */
    protected $collectionFactory = null;

    /**
     * @property ReplHierarchyNodeSearchResultsFactory $resultFactory
     */
    protected $resultFactory = null;

    /**
     * @property ReplHierarchyNodeRepository $model
     */
    private $model = null;

    /**
     * @property ReplHierarchyNodeInterface $entityInterface
     */
    private $entityInterface = null;

    /**
     * @property ReplHierarchyNodeSearchResultsInterface $entitySearchResultsInterface
     */
    private $entitySearchResultsInterface = null;

    public function setUp() : void
    {
        $this->objectFactory = $this->createPartialMock(ReplHierarchyNodeFactory::class, ['create']);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->resultFactory = $this->createMock(ReplHierarchyNodeSearchResultsFactory::class);
        $this->entityInterface = $this->createMock(ReplHierarchyNodeInterface::class);
        $this->entitySearchResultsInterface = $this->createMock(ReplHierarchyNodeSearchResultsInterface::class);
        $this->model = new ReplHierarchyNodeRepository(
                $this->objectFactory,
                $this->collectionFactory,
                $this->resultFactory
        );
    }

    public function testGetById()
    {
        $entityId = 1;
        $entityMock = $this->createMock(ReplHierarchyNodeRepository::class);
        $entityMock->method('getById')
             ->with($entityId)
             ->willReturn($entityId);
        $this->assertEquals($entityId, $entityMock->getById($entityId));
    }

    public function testGetWithNoSuchEntityException()
    {
        $this->expectExceptionMessage("Object with id 1 does not exist.");
        $this->expectException(\Magento\Framework\Exception\NoSuchEntityException::class);
        $entityId = 1;
        $entityMock = $this->createMock(ReplHierarchyNodeRepository::class);
        $entityMock->method('getById')
             ->with($entityId)
             ->willThrowException(
                 new NoSuchEntityException(
                     new Phrase('Object with id ' . $entityId . ' does not exist.')
                 )
             );
        $entityMock->getById($entityId);
    }

    public function testGetListWithSearchCriteria()
    {
        $searchCriteria = $this->getMockBuilder(SearchCriteriaInterface::class)->getMock();
        $entityMock = $this->createMock(ReplHierarchyNodeRepository::class);
        $entityMock->method('getList')
             ->with($searchCriteria)
             ->willReturn($this->entitySearchResultsInterface);
        $this->assertEquals($this->entitySearchResultsInterface, $entityMock->getList($searchCriteria));
    }

    public function testSave()
    {
        $entityMock = $this->createMock(ReplHierarchyNodeRepository::class);
        $entityMock->method('save')
             ->with($this->entityInterface)
             ->willReturn($this->entityInterface);
        $this->assertEquals($this->entityInterface, $entityMock->save($this->entityInterface));
    }

    public function testSaveWithCouldNotSaveException()
    {
        $this->expectExceptionMessage("Could not save entity");
        $this->expectException(\Magento\Framework\Exception\CouldNotSaveException::class);
        $entityMock = $this->createMock(ReplHierarchyNodeRepository::class);
        $entityMock->method('save')
             ->with($this->entityInterface)
             ->willThrowException(
                 new CouldNotSaveException(
                     __('Could not save entity')
                 )
             );
        $entityMock->save($this->entityInterface);
    }
}

