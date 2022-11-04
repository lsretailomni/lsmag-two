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
use Magento\Framework\Exception\StateException;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Creates images
 * for items and variants
 */
class SyncImages extends ProductCreateTask
{
    /** @var bool */
    public $cronStatus = false;

    /** @var int */
    public $remainingRecords;

    /**
     * @var array
     */
    public array $imagesFetched;

    /**
     * @param null $storeData
     * @throws InputException
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
                    $this->logger->debug('Running SyncImages Task for store ' . $this->store->getName());
                    $this->syncItemImages();
                    $this->replicationHelper->updateCronStatus(
                        $this->cronStatus,
                        LSR::SC_SUCCESS_CRON_ITEM_IMAGES,
                        $this->store->getId()
                    );
                    $this->logger->debug('End SyncImages Task with remaining : ' . $this->getRemainingRecords($this->store));
                }
                $this->lsr->setStoreId(null);
            }
        }
    }

    /**
     * @param null $storeData
     * @return array
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function executeManually($storeData = null)
    {
        $this->execute($storeData);
        $remainingRecords = (int)$this->getRemainingRecords($storeData);
        return [$remainingRecords];
    }

    /**
     * @throws InputException
     */
    public function syncItemImages()
    {
        $sortOrder  = $this->replicationHelper->getSortOrderObject();
        $collection = $this->getRecordsForImagesToProcess();
        $this->imagesFetched = [];
        if ($collection->getSize() > 0) {
            // Right now the only thing we have to do is flush all the images and do it again.
            /** @var ReplImageLink $itemImage */
            foreach ($collection->getItems() as $itemImage) {
                try {
                    $checkIsNotVariant = true;
                    $itemSku           = $itemImage->getKeyValue();
                    $itemSku           = str_replace(',', '-', $itemSku);

                    $explodeSku = explode("-", $itemSku);
                    if (count($explodeSku) > 1) {
                        $checkIsNotVariant = false;
                    }
                    $sku           = $explodeSku[0];
                    $uomCodesTotal = $this->replicationHelper->getUomCodes($sku, $this->store->getId());
                    if (!empty($uomCodesTotal)) {
                        if (count($uomCodesTotal[$sku]) > 1) {
                            $uomCodesNotProcessed = $this->getNewOrUpdatedProductUoms(-1, $sku);
                            if (count($uomCodesNotProcessed) == 0) {
                                $this->processImages($itemImage, $sortOrder, $itemSku);
                                $baseUnitOfMeasure = $this->replicationHelper->getBaseUnitOfMeasure($sku);
                                foreach ($uomCodesTotal[$sku] as $uomCode) {
                                    if ($checkIsNotVariant || $baseUnitOfMeasure != $uomCode) {
                                        $this->processImages($itemImage, $sortOrder, $itemSku, $uomCode);
                                    }
                                }
                            }
                        } else {
                            $this->processImages($itemImage, $sortOrder, $itemSku);
                        }
                    } else {
                        $this->processImages($itemImage, $sortOrder, $itemSku);
                    }
                } catch (Exception $e) {
                    $this->logger->debug(
                        'Problem with Image Synchronization : ' . $itemImage->getKeyValue() . ' in ' . __METHOD__
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
        } else {
            $this->cronStatus = true;
        }
    }

    /**
     * @param $storeData
     * @return int
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
     * @param $imagesToUpdate
     * @param $productData
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
                'Problem getting encoded Images in : ' . __METHOD__
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
                    'Problem while converting the images or Gallery CreateHandler in : ' . __METHOD__
                );
                $this->logger->debug($e->getMessage());
            }
        }
    }

    /**
     * @param $itemImage
     * @param $sortOrder
     * @param $itemSku
     * @param null $uomCode
     * @throws InputException
     * @throws LocalizedException
     * @throws StateException
     * @throws NoSuchEntityException
     */
    public function processImages($itemImage, $sortOrder, $itemSku, $uomCode = null)
    {
        if (!empty($uomCode)) {
            $itemSku = $itemSku . '-' . $uomCode;
        }
        try {
            $productData = $this->productRepository->get($itemSku, true, 0, true);
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
     * @param array $mediaGalleryEntries
     * @return array
     * @throws LocalizedException
     */
    public function convertToRequiredFormat(array $mediaGalleryEntries)
    {
        $images = [];
        foreach ($mediaGalleryEntries as $key => $entry) {
            $images[$key] = $this->entryConverterPool
                ->getConverterByMediaType($entry->getMediaType())
                ->convertFrom($entry);
            $images[$key]['image_id'] = $entry->getData('image_id');
        }
        return $images;
    }

    /**
     * Remove no selection
     *
     * @param $productData
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
                'Fixed no_selection issue for images of product : '. $customProduct->getSku()
            );
            $this->updateHandlerFactory->create()->execute($customProduct);
        }
    }

    /**
     * This function is overriding in hospitality module
     * @param false $totalCount
     * @return Collection
     */
    public function getRecordsForImagesToProcess($totalCount = false)
    {
        if (!$totalCount) {
            $batchSize = $this->replicationHelper->getProductImagesBatchSize();
        } else {
            $batchSize = -1;
        }
        /** Get Images for only those items which are already processed */
        $filters = [
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
