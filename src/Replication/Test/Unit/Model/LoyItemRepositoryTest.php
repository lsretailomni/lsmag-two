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
use \Ls\Replication\Model\LoyItemRepository;
use \Ls\Replication\Model\ResourceModel\LoyItem\Collection;
use \Ls\Replication\Model\ResourceModel\LoyItem\CollectionFactory;
use \Ls\Replication\Api\LoyItemRepositoryInterface;
use \Ls\Replication\Api\Data\LoyItemInterface;
use \Ls\Replication\Api\Data\LoyItemSearchResultsInterface;
use \Ls\Replication\Model\LoyItemFactory;
use \Ls\Replication\Model\LoyItemSearchResultsFactory;

class LoyItemRepositoryTest extends TestCase
{

    /**
     * @property LoyItemFactory $objectFactory
     */
    protected $objectFactory = null;

    /**
     * @property CollectionFactory $collectionFactory
     */
    protected $collectionFactory = null;

    /**
     * @property LoyItemSearchResultsFactory $resultFactory
     */
    protected $resultFactory = null;

    /**
     * @property LoyItemRepository $model
     */
    private $model = null;

    /**
     * @property LoyItemInterface $entityInterface
     */
    private $entityInterface = null;

    /**
     * @property LoyItemSearchResultsInterface $entitySearchResultsInterface
     */
    private $entitySearchResultsInterface = null;

    public function setUp()
    {
        $this->objectFactory = $this->createPartialMock(LoyItemFactory::class, ['create']);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->resultFactory = $this->createMock(LoyItemSearchResultsFactory::class);
        $this->entityInterface = $this->createMock(LoyItemInterface::class);
        $this->entitySearchResultsInterface = $this->createMock(LoyItemSearchResultsInterface::class);
        $this->model = new LoyItemRepository(
                $this->objectFactory,
                $this->collectionFactory,
                $this->resultFactory
        );
    }

    public function testGet()
    {
        $entityId = 1;
        $entityMock = $this->createMock(LoyItemRepository::class);
        $entityMock->method('getById')->willReturn(
            $entityId
        );
        $this->assertEquals($entityId, $entityMock->getById($entityId));
    }


}

