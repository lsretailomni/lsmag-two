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
use \Ls\Replication\Model\ReplHierarchyHospDealLineRepository;
use \Ls\Replication\Model\ResourceModel\ReplHierarchyHospDealLine\Collection;
use \Ls\Replication\Model\ResourceModel\ReplHierarchyHospDealLine\CollectionFactory;
use \Ls\Replication\Api\ReplHierarchyHospDealLineRepositoryInterface;
use \Ls\Replication\Api\Data\ReplHierarchyHospDealLineInterface;
use \Ls\Replication\Api\Data\ReplHierarchyHospDealLineSearchResultsInterface;
use \Ls\Replication\Model\ReplHierarchyHospDealLineFactory;
use \Ls\Replication\Model\ReplHierarchyHospDealLineSearchResultsFactory;

class ReplHierarchyHospDealLineRepositoryTest extends TestCase
{
    /**
     * @property ReplHierarchyHospDealLineFactory $objectFactory
     */
    protected $objectFactory = null;

    /**
     * @property CollectionFactory $collectionFactory
     */
    protected $collectionFactory = null;

    /**
     * @property ReplHierarchyHospDealLineSearchResultsFactory $resultFactory
     */
    protected $resultFactory = null;

    /**
     * @property ReplHierarchyHospDealLineRepository $model
     */
    private $model = null;

    /**
     * @property ReplHierarchyHospDealLineInterface $entityInterface
     */
    private $entityInterface = null;

    /**
     * @property ReplHierarchyHospDealLineSearchResultsInterface
     * $entitySearchResultsInterface
     */
    private $entitySearchResultsInterface = null;

    public function setUp()
    {
        $this->objectFactory = $this->createPartialMock(ReplHierarchyHospDealLineFactory::class, ['create']);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->resultFactory = $this->createMock(ReplHierarchyHospDealLineSearchResultsFactory::class);
        $this->entityInterface = $this->createMock(ReplHierarchyHospDealLineInterface::class);
        $this->entitySearchResultsInterface = $this->createMock(ReplHierarchyHospDealLineSearchResultsInterface::class);
        $this->model = new ReplHierarchyHospDealLineRepository(
                $this->objectFactory,
                $this->collectionFactory,
                $this->resultFactory
        );
    }

    public function testGetById()
    {
        $entityId = 1;
        $entityMock = $this->createMock(ReplHierarchyHospDealLineRepository::class);
        $entityMock->method('getById')
             ->with($entityId)
             ->willReturn($entityId);
        $this->assertEquals($entityId, $entityMock->getById($entityId));
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @expectedExceptionMessage Object with id 1 does not exist.
     */
    public function testGetWithNoSuchEntityException()
    {
        $entityId = 1;
        $entityMock = $this->createMock(ReplHierarchyHospDealLineRepository::class);
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
        $entityMock = $this->createMock(ReplHierarchyHospDealLineRepository::class);
        $entityMock->method('getList')
             ->with($searchCriteria)
             ->willReturn($this->entitySearchResultsInterface);
        $this->assertEquals($this->entitySearchResultsInterface, $entityMock->getList($searchCriteria));
    }

    public function testSave()
    {
        $entityMock = $this->createMock(ReplHierarchyHospDealLineRepository::class);
        $entityMock->method('save')
             ->with($this->entityInterface)
             ->willReturn($this->entityInterface);
        $this->assertEquals($this->entityInterface, $entityMock->save($this->entityInterface));
    }

    /**
     * @expectedException \Magento\Framework\Exception\CouldNotSaveException
     * @expectedExceptionMessage Could not save entity
     */
    public function testSaveWithCouldNotSaveException()
    {
        $entityMock = $this->createMock(ReplHierarchyHospDealLineRepository::class);
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

