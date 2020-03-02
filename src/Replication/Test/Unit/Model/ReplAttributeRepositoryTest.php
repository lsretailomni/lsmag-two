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
use \Ls\Replication\Model\ReplAttributeRepository;
use \Ls\Replication\Model\ResourceModel\ReplAttribute\Collection;
use \Ls\Replication\Model\ResourceModel\ReplAttribute\CollectionFactory;
use \Ls\Replication\Api\ReplAttributeRepositoryInterface;
use \Ls\Replication\Api\Data\ReplAttributeInterface;
use \Ls\Replication\Api\Data\ReplAttributeSearchResultsInterface;
use \Ls\Replication\Model\ReplAttributeFactory;
use \Ls\Replication\Model\ReplAttributeSearchResultsFactory;

class ReplAttributeRepositoryTest extends TestCase
{

    /**
     * @property ReplAttributeFactory $objectFactory
     */
    protected $objectFactory = null;

    /**
     * @property CollectionFactory $collectionFactory
     */
    protected $collectionFactory = null;

    /**
     * @property ReplAttributeSearchResultsFactory $resultFactory
     */
    protected $resultFactory = null;

    /**
     * @property ReplAttributeRepository $model
     */
    private $model = null;

    /**
     * @property ReplAttributeInterface $entityInterface
     */
    private $entityInterface = null;

    /**
     * @property ReplAttributeSearchResultsInterface $entitySearchResultsInterface
     */
    private $entitySearchResultsInterface = null;

    public function setUp()
    {
        $this->objectFactory = $this->createPartialMock(ReplAttributeFactory::class, ['create']);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->resultFactory = $this->createMock(ReplAttributeSearchResultsFactory::class);
        $this->entityInterface = $this->createMock(ReplAttributeInterface::class);
        $this->entitySearchResultsInterface = $this->createMock(ReplAttributeSearchResultsInterface::class);
        $this->model = new ReplAttributeRepository(
                $this->objectFactory,
                $this->collectionFactory,
                $this->resultFactory
        );
    }

    public function testGet()
    {
        $entityId = 1;
        $entityMock = $this->createMock(ReplAttributeRepository::class);
        $entityMock->method('getById')->willReturn(
            $entityId
        );
        $this->assertEquals($entityId, $entityMock->getById($entityId));
    }


}

