<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Model\ReplImageLink;
use \Ls\Replication\Model\ReplImageLinkSearchResults;
use \Ls\Replication\Model\ResourceModel\ReplImageLink\Collection;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Cron responsible to update images for item and variants
 */
class SyncImages extends ProductCreateTask
{
    /** @var bool */
    public $cronStatus = false;

    /** @var int */
    public $remainingRecords;

    /**
     * Entry point for cron
     *
     * @param mixed $storeData
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute($storeData = null)
    {
        if (!empty($storeData) && $storeData instanceof StoreInterface) {
            $stores = [$storeData];
        } else {
            /** @var StoreInterface[] $stores */
            $stores = $this->lsr->getAllStores();
        }
        if (!empty($stores)) {
            foreach ($stores as $store) {
                $this->lsr->setStoreId($store->getId());
                $this->store = $store;
                if ($this->lsr->isLSR($this->store->getId())) {
                    $this->replicationHelper->updateConfigValue(
                        $this->replicationHelper->getDateTime(),
                        LSR::SC_ITEM_IMAGES_CONFIG_PATH_LAST_EXECUTE,
                        $this->store->getId()
                    );
                    $this->replicationHelper->setEnvVariables();
                    $this->logger->debug(sprintf('Running SyncImages Task for store %s', $this->store->getName()));
                    $this->syncItemImages();
                    $this->replicationHelper->updateCronStatus(
                        $this->cronStatus,
                        LSR::SC_SUCCESS_CRON_ITEM_IMAGES,
                        $this->store->getId()
                    );
                    $this->logger->debug(
                        sprintf('End SyncImages Task with remaining : %s', $this->getRemainingRecords($this->store))
                    );
                }
                $this->lsr->setStoreId(null);
            }
        }
    }

    /**
     * Execute manually
     *
     * @param mixed $storeData
     * @return int[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function executeManually($storeData = null)
    {
        $this->execute($storeData);
        $remainingRecords = (int)$this->getRemainingRecords($storeData);
        return [$remainingRecords];
    }

    /**
     * Sync item images
     *
     * @return void
     * @throws InputException|LocalizedException
     */
    public function syncItemImages()
    {
        $sortOrder  = $this->replicationHelper->getSortOrderObject();
        $collection = $this->getRecordsForImagesToProcess();
        // Right now the only thing we have to do is flush all the images and do it again.
        /** @var ReplImageLink $itemImage */
        foreach ($collection->getItems() as $itemImage) {
            try {
                $variantId         = '';
                $keyValue          = $itemImage->getKeyValue();
                $explodeSku        = explode(",", $keyValue);
                if (count($explodeSku) > 1) {
                    $variantId         = $explodeSku[1];
                }
                $itemId        = $explodeSku[0];
                $uomCodesTotal = $this->replicationHelper->getUomCodes($itemId, $this->store->getId());
                if (!empty($uomCodesTotal)) {
                    if (count($uomCodesTotal[$itemId]) > 1) {
                        $uomCodesNotProcessed = $this->getNewOrUpdatedProductUoms(-1, $itemId);
                        if (count($uomCodesNotProcessed) == 0) {
                            $this->processImages($itemImage, $sortOrder, $itemId, $variantId);
                            foreach ($uomCodesTotal[$itemId] as $uomCode) {
                                $this->processImages($itemImage, $sortOrder, $itemId, $variantId, $uomCode);
                            }
                        }
                    } else {
                        $this->processImages($itemImage, $sortOrder, $itemId, $variantId);
                    }
                } else {
                    $this->processImages($itemImage, $sortOrder, $itemId, $variantId);
                }
            } catch (Exception $e) {
                $this->logger->debug(
                    sprintf('Problem with Image Synchronization : %s in %s', $itemImage->getKeyValue(), __METHOD__)
                );
                $this->logger->debug($e->getMessage());
                $itemImage->setData('processed_at', $this->replicationHelper->getDateTime());
                $itemImage->setData('is_failed', 1);
                $itemImage->setData('processed', 1);
                $itemImage->setData('is_updated', 0);
                // @codingStandardsIgnoreLine
                $this->replImageLinkRepositoryInterface->save($itemImage);
            }
        }
        $remainingItems = (int)$this->getRemainingRecords($this->store);
        if ($remainingItems == 0) {
            $this->cronStatus = true;
        }
        $this->replicationHelper->flushByTypeCode('full_page');
    }

    /**
     * Get remaining records
     *
     * @param mixed $storeData
     * @return int
     * @throws LocalizedException
     */
    public function getRemainingRecords($storeData)
    {
        if ($this->remainingRecords === null) {
            $collection             = $this->getRecordsForImagesToProcess(true);
            $this->remainingRecords = $collection->getSize();
        }
        return $this->remainingRecords;
    }

    /**
     * Process Media Gallery Images
     *
     * @param mixed $imagesToUpdate
     * @param mixed $productData
     * @return void
     */
    public function processMediaGalleryImages($imagesToUpdate, $productData)
    {
        $encodedImages = [];
        try {
            $encodedImages = $this->getMediaGalleryEntries(
                $imagesToUpdate->getItems()
            );
        } catch (Exception $e) {
            $this->logger->debug(
                sprintf('Problem getting encoded Images in : %s', __METHOD__)
            );
            $this->logger->debug($e->getMessage());
        }

        if (!empty($encodedImages)) {
            try {
                $encodedImages = $this->convertToRequiredFormat($encodedImages);
                $this->mediaGalleryProcessor->processMediaGallery(
                    $productData,
                    $encodedImages
                );
                $this->updateHandlerFactory->create()->execute($productData);

                $this->removeNoSelection($productData);
            } catch (Exception $e) {
                $this->logger->debug(
                    sprintf('Problem while converting the images or Gallery CreateHandler in : %s', __METHOD__)
                );
                $this->logger->debug($e->getMessage());
            }
        }
    }

    /**
     * Process images
     *
     * @param mixed $itemImage
     * @param mixed $sortOrder
     * @param mixed $itemId
     * @param mixed $variantId
     * @param mixed $uomCode
     * @return void
     * @throws LocalizedException
     */
    public function processImages($itemImage, $sortOrder, $itemId, $variantId = null, $uomCode = null)
    {
        try {
            $product     = $this->replicationHelper->getProductDataByIdentificationAttributes(
                $itemId,
                $variantId,
                $uomCode,
                $this->store->getId()
            );
            $productData = $this->productRepository->get($product->getSku(), true, 0, true);
        } catch (NoSuchEntityException $e) {
            return;
        }

        // Check for all images.
        $filtersForAllImages  = [
            ['field' => 'KeyValue', 'value' => $itemImage->getKeyValue(), 'condition_type' => 'eq'],
            ['field' => 'TableName', 'value' => $itemImage->getTableName(), 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $this->store->getId(), 'condition_type' => 'eq']
        ];
        $criteriaForAllImages = $this->replicationHelper->buildCriteriaForDirect(
            $filtersForAllImages,
            -1,
            false
        )->setSortOrders([$sortOrder]);
        /** @var ReplImageLinkSearchResults $newImagestoProcess */
        $newImagesToProcess = $this->replImageLinkRepositoryInterface->getList($criteriaForAllImages);
        if ($newImagesToProcess->getTotalCount() > 0) {
            $this->processMediaGalleryImages($newImagesToProcess, $productData);
        }
    }

    /**
     * Convert to required format
     *
     * @param array $mediaGalleryEntries
     * @return array
     * @throws LocalizedException
     */
    public function convertToRequiredFormat(array $mediaGalleryEntries)
    {
        $images = [];
        foreach ($mediaGalleryEntries as $entry) {
            $images[] = $this->entryConverterPool
                ->getConverterByMediaType($entry->getMediaType())
                ->convertFrom($entry);
        }
        return $images;
    }

    /**
     * Remove no selection
     *
     * @param mixed $productData
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function removeNoSelection($productData)
    {
        $customProduct   = $this->productRepository->get($productData->getSku(), true, $this->store->getId(), true);
        $mediaAttributes = $this->mediaConfig->getMediaAttributeCodes();
        $saveMe          = false;

        foreach ($mediaAttributes as $mediaAttrCode) {
            if ($customProduct->getData($mediaAttrCode) == 'no_selection' && $mediaAttrCode != 'swatch_image') {
                $saveMe = true;
                $customProduct->unsetData($mediaAttrCode);
            }
        }

        if ($saveMe) {
            $this->logger->debug(
                sprintf('Fixed no_selection issue for images of product : %s', $customProduct->getSku())
            );
            $this->updateHandlerFactory->create()->execute($customProduct);
        }
    }

    /**
     * This function is overriding in hospitality module
     *
     *  Get records for images to process
     *
     * @param bool $totalCount
     * @return Collection
     * @throws LocalizedException
     */
    public function getRecordsForImagesToProcess($totalCount = false)
    {
        if (!$totalCount) {
            $batchSize = $this->replicationHelper->getProductImagesBatchSize();
        } else {
            $batchSize = -1;
        }
        /** Get Images for only those items which are already processed */
        $filters  = [
            ['field' => 'main_table.TableName', 'value' => 'Item%', 'condition_type' => 'like'],
            ['field' => 'main_table.TableName', 'value' => 'Item Category', 'condition_type' => 'neq'],
            ['field' => 'main_table.scope_id', 'value' => $this->store->getId(), 'condition_type' => 'eq']
        ];
        $criteria = $this->replicationHelper->buildCriteriaForArrayWithAlias(
            $filters,
            $batchSize,
            false
        );
        /** @var  $collection */
        $collection = $this->replImageLinkCollectionFactory->create();

        /** we only need unique product Id's which has any images to modify */
        $this->replicationHelper->setCollectionPropertiesPlusJoinsForImages(
            $collection,
            $criteria,
            'Item'
        );
        $collection->getSelect()->order('main_table.processed ASC');

        return $collection;
    }
}
