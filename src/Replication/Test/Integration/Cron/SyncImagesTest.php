<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Cron\ProductCreateTask;
use \Ls\Replication\Cron\SyncImages;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Store\Model\ScopeInterface;

class SyncImagesTest extends AbstractTaskTest
{
    public $imageSyncCron;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->imageSyncCron = $this->objectManager->get(SyncImages::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function actualExecute()
    {
        $storeId                    = $this->storeManager->getStore()->getId();
        $this->imageSyncCron->store = $this->storeManager->getStore();
        $this->updateAllRelevantItemRecords(
            1,
            [
                AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
                AbstractIntegrationTest::SAMPLE_SIMPLE_ITEM_ID
            ]
        );

        $this->executeUntilReady(ProductCreateTask::class, [
            LSR::SC_SUCCESS_CRON_PRODUCT
        ]);

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_PRODUCT,
            ],
            $storeId
        );

        $this->executeUntilReady(SyncImages::class, [
            LSR::SC_SUCCESS_CRON_ITEM_IMAGES
        ]);

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_ITEM_IMAGES,
            ],
            $storeId
        );
        $this->assertProductMediaGallery(AbstractIntegrationTest::SAMPLE_SIMPLE_ITEM_ID);
        $this->assertProductMediaGallery(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID
        );
        $this->assertProductMediaGallery(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_VARIANT_ID,
            AbstractIntegrationTest::SAMPLE_UOM,
            'Item Variant'
        );
        $this->addImage();
        $this->deleteImages();
    }

    public function addDummyData()
    {
        parent::addDummyData();
        $this->addNewImageLink([
            'ImageDescription' => 'Dummy Image',
            'DisplayOrder' => 1,
            'ImageId' => AbstractIntegrationTest::SAMPLE_SIMPLE_ITEM_ID,
            'IsDeleted' => 0,
            'KeyValue' => AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            'TableName' => 'Item',
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'scope_id' => $this->storeManager->getWebsite()->getId()
        ]);
    }

    public function addNewImageLink($parameters)
    {
        $replImageLink = $this->replImageLinkInterfaceFactory->create();
        $replImageLink->addData($parameters);

        $this->replImageLinkRepository->save($replImageLink);
    }

    public function assertProductMediaGallery($itemId, $variantId = '', $uomCode = '', $tableName = 'Item')
    {
        $storeId              = $this->storeManager->getStore()->getId();
        $scopeId              = $this->storeManager->getWebsite()->getId();
        $sortOrder            = $this->replicationHelper->getSortOrderObject();
        $simpleProduct        = $this->replicationHelper->getProductDataByIdentificationAttributes(
            $itemId,
            $variantId,
            $uomCode,
            $storeId
        );
        $filtersForAllImages  = [
            [
                'field' => 'KeyValue',
                'value' => $variantId != '' ? $itemId . ',' . $variantId : $itemId,
                'condition_type' => 'eq'
            ],
            ['field' => 'TableName', 'value' => $tableName, 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $scopeId, 'condition_type' => 'eq']
        ];
        $criteriaForAllImages = $this->replicationHelper->buildCriteriaForDirect(
            $filtersForAllImages,
            -1,
            true
        )->setSortOrders([$sortOrder]);
        $newImagesToProcess   = $this->replImageLinkRepository->getList($criteriaForAllImages);
        if ($newImagesToProcess->getTotalCount() > 0) {
            $productData   = $this->productRepository->get($simpleProduct->getSku(), true, 0, true);
            $productImages = $productData->getMediaGalleryImages();
            $this->assertEquals($newImagesToProcess->getTotalCount(), count($productImages));

            $valueSaved = false;

            foreach ($productImages as $image) {
                $this->assertTrue($this->imageSyncCron->imageExists($image->getFile()));

                $valueSaved = $image->getFile() == $productData->getData('image') &&
                    $image->getFile() == $productData->getData('small_image') &&
                    $image->getFile() == $productData->getData('thumbnail');

                if ($valueSaved) {
                    break;
                }
            }

            $this->assertTrue($valueSaved);
        }
    }

    public function addImage()
    {
        $storeId                    = $this->storeManager->getStore()->getId();
        $this->addNewImageLink([
            'ImageDescription' => 'Dummy Image',
            'DisplayOrder' => 1,
            'ImageId' => AbstractIntegrationTest::SAMPLE_DISCOUNTED_CONFIGURABLE_ITEM_ID,
            'IsDeleted' => 0,
            'KeyValue' => AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
            'TableName' => 'Item',
            'scope' => ScopeInterface::SCOPE_WEBSITES,
            'scope_id' => $this->storeManager->getWebsite()->getId()
        ]);
        $this->executeUntilReady(SyncImages::class, [
            LSR::SC_SUCCESS_CRON_ITEM_IMAGES
        ]);

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_ITEM_IMAGES,
            ],
            $storeId
        );

        $this->assertProductMediaGallery(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID
        );
    }

    public function deleteImages()
    {
        $storeId                    = $this->storeManager->getStore()->getId();
        $scopeId                    = $this->storeManager->getWebsite()->getId();
        $filtersForAllImages  = [
            [
                'field' => 'KeyValue',
                'value' => AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID,
                'condition_type' => 'eq'
            ],
            ['field' => 'TableName', 'value' => 'Item', 'condition_type' => 'eq'],
            ['field' => 'ImageId', 'value' => AbstractIntegrationTest::SAMPLE_SIMPLE_ITEM_ID, 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $scopeId, 'condition_type' => 'eq']
        ];
        $criteriaForAllImages = $this->replicationHelper->buildCriteriaForDirect(
            $filtersForAllImages,
            -1,
            false
        );
        $replImage   = current($this->replImageLinkRepository->getList($criteriaForAllImages)->getItems());
        $replImage->setData('IsDeleted', 1);
        $replImage->setData('is_updated', 1);
        $this->replImageLinkRepository->save($replImage);
        $this->executeUntilReady(SyncImages::class, [
            LSR::SC_SUCCESS_CRON_ITEM_IMAGES
        ]);

        $this->assertCronSuccess(
            [
                LSR::SC_SUCCESS_CRON_ITEM_IMAGES,
            ],
            $storeId
        );

        $this->assertProductMediaGallery(
            AbstractIntegrationTest::SAMPLE_CONFIGURABLE_ITEM_ID
        );
    }
}
