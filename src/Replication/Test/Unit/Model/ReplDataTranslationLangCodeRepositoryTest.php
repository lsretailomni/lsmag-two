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
use \Ls\Replication\Model\ReplDataTranslationLangCodeRepository;
use \Ls\Replication\Model\ResourceModel\ReplDataTranslationLangCode\Collection;
use \Ls\Replication\Model\ResourceModel\ReplDataTranslationLangCode\CollectionFactory;
use \Ls\Replication\Api\ReplDataTranslationLangCodeRepositoryInterface;
use \Ls\Replication\Api\Data\ReplDataTranslationLangCodeInterface;
use \Ls\Replication\Api\Data\ReplDataTranslationLangCodeSearchResultsInterface;
use \Ls\Replication\Model\ReplDataTranslationLangCodeFactory;
use \Ls\Replication\Model\ReplDataTranslationLangCodeSearchResultsFactory;

class ReplDataTranslationLangCodeRepositoryTest extends TestCase
{
    /**
     * @property ReplDataTranslationLangCodeFactory $objectFactory
     */
    protected $objectFactory = null;

    /**
     * @property CollectionFactory $collectionFactory
     */
    protected $collectionFactory = null;

    /**
     * @property ReplDataTranslationLangCodeSearchResultsFactory $resultFactory
     */
    protected $resultFactory = null;

    /**
     * @property ReplDataTranslationLangCodeRepository $model
     */
    private $model = null;

    /**
     * @property ReplDataTranslationLangCodeInterface $entityInterface
     */
    private $entityInterface = null;

    /**
     * @property ReplDataTranslationLangCodeSearchResultsInterface
     * $entitySearchResultsInterface
     */
    private $entitySearchResultsInterface = null;

    public function setUp()
    {
        $this->objectFactory = $this->createPartialMock(ReplDataTranslationLangCodeFactory::class, ['create']);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->resultFactory = $this->createMock(ReplDataTranslationLangCodeSearchResultsFactory::class);
        $this->entityInterface = $this->createMock(ReplDataTranslationLangCodeInterface::class);
        $this->entitySearchResultsInterface = $this->createMock(ReplDataTranslationLangCodeSearchResultsInterface::class);
        $this->model = new ReplDataTranslationLangCodeRepository(
                $this->objectFactory,
                $this->collectionFactory,
                $this->resultFactory
        );
    }

    public function testGetById()
    {
        $entityId = 1;
        $entityMock = $this->createMock(ReplDataTranslationLangCodeRepository::class);
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
        $entityMock = $this->createMock(ReplDataTranslationLangCodeRepository::class);
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
        $entityMock = $this->createMock(ReplDataTranslationLangCodeRepository::class);
        $entityMock->method('getList')
             ->with($searchCriteria)
             ->willReturn($this->entitySearchResultsInterface);
        $this->assertEquals($this->entitySearchResultsInterface, $entityMock->getList($searchCriteria));
    }

    public function testSave()
    {
        $entityMock = $this->createMock(ReplDataTranslationLangCodeRepository::class);
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
        $entityMock = $this->createMock(ReplDataTranslationLangCodeRepository::class);
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

