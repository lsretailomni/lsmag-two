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
use \Ls\Replication\Model\ReplHierarchyLeafRepository;
use \Ls\Replication\Model\ResourceModel\ReplHierarchyLeaf\Collection;
use \Ls\Replication\Model\ResourceModel\ReplHierarchyLeaf\CollectionFactory;
use \Ls\Replication\Api\ReplHierarchyLeafRepositoryInterface;
use \Ls\Replication\Api\Data\ReplHierarchyLeafInterface;
use \Ls\Replication\Api\Data\ReplHierarchyLeafSearchResultsInterface;
use \Ls\Replication\Model\ReplHierarchyLeafFactory;
use \Ls\Replication\Model\ReplHierarchyLeafSearchResultsFactory;

class ReplHierarchyLeafRepositoryTest extends TestCase
{

    /**
     * @property ReplHierarchyLeafFactory $objectFactory
     */
    protected $objectFactory = null;

    /**
     * @property CollectionFactory $collectionFactory
     */
    protected $collectionFactory = null;

    /**
     * @property ReplHierarchyLeafSearchResultsFactory $resultFactory
     */
    protected $resultFactory = null;

    /**
     * @property ReplHierarchyLeafRepository $model
     */
    private $model = null;

    /**
     * @property ReplHierarchyLeafInterface $entityInterface
     */
    private $entityInterface = null;

    /**
     * @property ReplHierarchyLeafSearchResultsInterface $entitySearchResultsInterface
     */
    private $entitySearchResultsInterface = null;

    public function setUp()
    {
        $this->objectFactory = $this->createPartialMock(ReplHierarchyLeafFactory::class, ['create']);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->resultFactory = $this->createMock(ReplHierarchyLeafSearchResultsFactory::class);
        $this->entityInterface = $this->createMock(ReplHierarchyLeafInterface::class);
        $this->entitySearchResultsInterface = $this->createMock(ReplHierarchyLeafSearchResultsInterface::class);
        $this->model = new ReplHierarchyLeafRepository(
                $this->objectFactory,
                $this->collectionFactory,
                $this->resultFactory
        );
    }

    public function testGet()
    {
        $entityId = 1;
        $entityMock = $this->createMock(ReplHierarchyLeafRepository::class);
        $entityMock->method('getById')->willReturn(
            $entityId
        );
        $this->assertEquals($entityId, $entityMock->getById($entityId));
    }


}

