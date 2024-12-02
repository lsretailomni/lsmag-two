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
use \Ls\Replication\Model\ReplProductGroupRepository;
use \Ls\Replication\Model\ResourceModel\ReplProductGroup\Collection;
use \Ls\Replication\Model\ResourceModel\ReplProductGroup\CollectionFactory;
use \Ls\Replication\Api\ReplProductGroupRepositoryInterface;
use \Ls\Replication\Api\Data\ReplProductGroupInterface;
use \Ls\Replication\Api\Data\ReplProductGroupSearchResultsInterface;
use \Ls\Replication\Model\ReplProductGroupFactory;
use \Ls\Replication\Model\ReplProductGroupSearchResultsFactory;

class ReplProductGroupRepositoryTest extends TestCase
{
    /**
     * @property ReplProductGroupFactory $objectFactory
     */
    protected $objectFactory = null;

    /**
     * @property CollectionFactory $collectionFactory
     */
    protected $collectionFactory = null;

    /**
     * @property ReplProductGroupSearchResultsFactory $resultFactory
     */
    protected $resultFactory = null;

    /**
     * @property ReplProductGroupRepository $model
     */
    private $model = null;

    /**
     * @property ReplProductGroupInterface $entityInterface
     */
    private $entityInterface = null;

    /**
     * @property ReplProductGroupSearchResultsInterface $entitySearchResultsInterface
     */
    private $entitySearchResultsInterface = null;

    public function setUp() : void
    {
        $this->objectFactory = $this->createPartialMock(ReplProductGroupFactory::class, ['create']);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->resultFactory = $this->createMock(ReplProductGroupSearchResultsFactory::class);
        $this->entityInterface = $this->createMock(ReplProductGroupInterface::class);
        $this->entitySearchResultsInterface = $this->createMock(ReplProductGroupSearchResultsInterface::class);
        $this->model = new ReplProductGroupRepository(
                $this->objectFactory,
                $this->collectionFactory,
                $this->resultFactory
        );
    }

    public function testGetById()
    {
        $entityId = 1;
        $entityMock = $this->createMock(ReplProductGroupRepository::class);
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
        $entityMock = $this->createMock(ReplProductGroupRepository::class);
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
        $entityMock = $this->createMock(ReplProductGroupRepository::class);
        $entityMock->method('getList')
             ->with($searchCriteria)
             ->willReturn($this->entitySearchResultsInterface);
        $this->assertEquals($this->entitySearchResultsInterface, $entityMock->getList($searchCriteria));
    }

    public function testSave()
    {
        $entityMock = $this->createMock(ReplProductGroupRepository::class);
        $entityMock->method('save')
             ->with($this->entityInterface)
             ->willReturn($this->entityInterface);
        $this->assertEquals($this->entityInterface, $entityMock->save($this->entityInterface));
    }

    public function testSaveWithCouldNotSaveException()
    {
        $this->expectExceptionMessage("Could not save entity");
        $this->expectException(\Magento\Framework\Exception\CouldNotSaveException::class);
        $entityMock = $this->createMock(ReplProductGroupRepository::class);
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

