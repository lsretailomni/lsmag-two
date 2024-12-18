<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Model\ReplImageLink;
use \Ls\Replication\Model\ReplImageLinkSearchResults;
use \Ls\Replication\Model\ResourceModel\ReplImageLink\Collection;
use Magento\Catalog\Model\Product\Gallery\Entry;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Cron responsible to update images for item and variants
 */
class SyncImages extends ProductCreateTask
{
    /** @var bool */
    public $cronStatus = false;

    /** @var int */
    public $remainingRecords;

    private const HASH_ALGORITHM = 'sha256';

    /** @var array  */
    public array $imageHashes = [];

    /** @var array  */
    public array $productIds = [];

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
        if (!$this->lsr->isSSM()) {
            if (!empty($storeData) && $storeData instanceof StoreInterface) {
                $stores = [$storeData];
            } else {
                $stores = $this->lsr->getAllStores();
            }
        } else {
            $stores = [$this->lsr->getAdminStore()];
        }

        if (!empty($stores)) {
            foreach ($stores as $store) {
                $this->lsr->setStoreId($store->getId());
                $this->store = $store;
                if ($this->lsr->isLSR($this->store->getId())) {
                    $this->replicationHelper->updateConfigValue(
                        $this->replicationHelper->getDateTime(),
                        LSR::SC_ITEM_IMAGES_CONFIG_PATH_LAST_EXECUTE,
                        $this->store->getId(),
                        ScopeInterface::SCOPE_STORES
                    );
                    $this->replicationHelper->setEnvVariables();
                    $this->logger->debug(sprintf('Running SyncImages Task for store %s', $this->store->getName()));
                    $this->syncItemImages();
                    $this->replicationHelper->updateCronStatus(
                        $this->cronStatus,
                        LSR::SC_SUCCESS_CRON_ITEM_IMAGES,
                        $this->store->getId(),
                        false,
                        ScopeInterface::SCOPE_STORES
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
        $sortOrder = $this->replicationHelper->getSortOrderObject();
        $collection = $this->getRecordsForImagesToProcess();
        $this->imagesFetched = [];
        $this->productIds = [];
        if ($collection->getSize() > 0) {
            // Right now the only thing we have to do is flush all the images and do it again.
            /** @var ReplImageLink $itemImage */
            foreach ($collection->getItems() as $itemImage) {
                try {
                    $variantId  = '';
                    $keyValue   = $itemImage->getKeyValue();
                    $explodeSku = explode(",", $keyValue);
                    if (count($explodeSku) > 1) {
                        $variantId = $explodeSku[1];
                    }
                    $itemId        = $explodeSku[0];
                    $this->getExistingImageswithHash($itemId);
                    $uomCodesTotal = $this->replicationHelper->getUomCodes($itemId, $this->getScopeId());
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
                        sprintf(
                            'Exception happened in %s for store: %s, item id: %s',
                            __METHOD__,
                            $this->store->getName(),
                            $itemImage->getKeyValue()
                        )
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
            $this->replicationHelper->flushFpcCacheAgainstIds($this->productIds);
        }

        $remainingItems = (int)$this->getRemainingRecords($this->store);
        if ($remainingItems == 0) {
            $this->cronStatus = true;
        }
    }

    /**
     * Fetch existing images based on sku and add image hashes.
     *
     * @param string $itemId
     * @return void
     * @throws FileSystemException
     */
    public function getExistingImageswithHash($itemId)
    {
        $filterArr = [];
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(LSR::LS_ITEM_ID_ATTRIBUTE_CODE, "%".$itemId."%", 'like')
            ->create();

        $productObjs = $this->productRepository->getList($searchCriteria);

        foreach ($productObjs->getItems() as $productObj) {
            $filterArr[]['sku'] = $productObj->getSku();
        }

        if (count($filterArr) >0) {
            $existingImages = $this->mediaProcessor->getExistingImages($filterArr);
            $this->addImageHashes($existingImages);
        }
    }

    /**
     * Add image hashes.
     *
     * @param array $existingImages
     * @return void
     * @throws FileSystemException
     */
    public function addImageHashes($existingImages)
    {
        $productMediaPath = $this->getProductMediaPath();
        foreach ($existingImages as $storeId => $skus) {
            foreach ($skus as $sku => $files) {
                foreach ($files as $path => $file) {
                    $hash = $this->getFileHash($this->joinFilePaths($productMediaPath, $file['value']));
                    if ($hash) {
                        $this->imageHashes[$file['value']] = $hash;
                    }
                }
            }
        }
    }

    /**
     * To fetch remaining records of Images to process
     *
     * @param object $storeData
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
                $imagesToUpdate->getItems(),
                $productData
            );
        } catch (Exception $e) {
            $this->logger->debug(
                sprintf(
                    'Exception happened in %s for store: %s',
                    __METHOD__,
                    $this->store->getName()
                )
            );
            $this->logger->debug($e->getMessage());
        }

        try {
            $base64Images = [];
            foreach ($encodedImages as $encodedImage) {
                if (!($encodedImage instanceof Entry)) {
                    try {
                        $this->imageService->execute(
                            $productData,
                            $encodedImage['location'],
                            $encodedImage['repl_image_link_id'],
                            false,
                            $encodedImage['types']
                        );
                    } catch (Exception $e) {
                        $this->logger->debug(
                            sprintf(
                                'Exception happened in %s for store: %s',
                                __METHOD__,
                                $this->store->getName()
                            )
                        );
                        $this->logger->debug($e->getMessage());
                        continue;
                    }
                } else {
                    $base64Images[] = $encodedImage;
                }
            }

            $updatedMediaGallery['images'] = [];
            $formattedImages = $this->convertToRequiredFormat($base64Images);
            foreach ($productData->getMediaGallery()['images'] as $i => $image) {
                if (!isset($image['value_id'])) {
                    $formattedImages[] = $image;
                } else {
                    $updatedMediaGallery['images'][$i] = $image;
                }
            }

            $productData->setMediaGallery($updatedMediaGallery);

            $this->mediaGalleryProcessor->processMediaGallery(
                $productData,
                $formattedImages
            );
            $this->updateHandlerFactory->create()->execute($productData);

            $this->removeNoSelection($productData);
        } catch (Exception $e) {
            $this->logger->debug(
                sprintf(
                    'Exception happened in %s for store: %s',
                    __METHOD__,
                    $this->store->getName()
                )
            );
            $this->logger->debug($e->getMessage());
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
            $this->productIds[] = $productData->getId();
            $this->productIds = array_unique($this->productIds);
        } catch (NoSuchEntityException $e) {
            return;
        }

        // Check for all images.
        $filtersForAllImages  = [
            ['field' => 'KeyValue', 'value' => $itemImage->getKeyValue(), 'condition_type' => 'eq'],
            ['field' => 'TableName', 'value' => $itemImage->getTableName(), 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq']
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

        //To remove duplicated images
        $this->removeDuplicatedImages($productData);
    }

    /**
     * Custom function to remove duplicated images for the sku and its variants.
     *
     * @param object $productData
     * @return void
     * @throws FileSystemException
     */
    public function removeDuplicatedImages($productData)
    {
        $mediaGalleryImages = $productData->getMediaGalleryImages();

        $productMediaPath = $this->getProductMediaPath();
        $finalMediaGalleryIds = [];
        foreach ($mediaGalleryImages as $galleryImage) {
            $finalMediaGalleryIds[] = $galleryImage->getId();
            $filePath = $galleryImage->getFile();
            $hash     = $this->getFileHash($this->joinFilePaths($productMediaPath, $filePath));
            if ($hash && !in_array($hash, $this->imageHashes)) {
                $this->imageHashes[$filePath] = $hash;
            } else {
                $existingFilePath    = array_search($hash, $this->imageHashes);

                if ($filePath != $existingFilePath && $this->imageExists($existingFilePath)) {
                    $this->updateMediaPaths('catalog_product_entity_varchar', $existingFilePath, $filePath);
                    $this->updateMediaPaths('catalog_product_entity_media_gallery', $existingFilePath, $filePath);

                    $this->deleteDuplicateCatalogImage($filePath);
                }
            }
        }

        if (!empty($finalMediaGalleryIds)) {
            $this->deleteRequiredMediaEntries(
                'catalog_product_entity_media_gallery_value_to_entity',
                $finalMediaGalleryIds,
                $productData->getId()
            );

            $this->deleteRequiredMediaEntries(
                'catalog_product_entity_media_gallery_value',
                $finalMediaGalleryIds,
                $productData->getId()
            );
        }
    }

    /**
     * Update duplicated catalog image paths with already existing image file
     *
     * @param string $tableName
     * @param string $existingFilePath
     * @param string $newFilePath
     * @return void
     */
    public function updateMediaPaths($tableName, $existingFilePath, $newFilePath): void
    {
        try {
            $connection          = $this->resourceConnection->getConnection(
                ResourceConnection::DEFAULT_CONNECTION
            );
            $catalogEntityVarcharTable = $this->resourceConnection
                ->getTableName($tableName);

            $connection->startSetup();

            $updateData = [
                'value' => $existingFilePath
            ];
            $whereCondition = [
                'value = ?' => (string)$newFilePath
            ];

            $connection->update(
                $catalogEntityVarcharTable,
                $updateData,
                $whereCondition
            );
            $connection->endSetup();
        } catch (Exception $e) {
            $this->logger->debug(
                'Problem with Media path update in : ' . $catalogEntityVarcharTable .
                ' for ' . $newFilePath . ' with '.$existingFilePath
            );
        }
    }

    /**
     * Delete old media entry records
     *
     * @param $tableName
     * @param $mediaEntries
     * @param $productId
     * @return void
     */
    public function deleteRequiredMediaEntries($tableName, $mediaEntries, $productId): void
    {
        $connection                     = $this->resourceConnection->getConnection();
        $catalogEntityMediaGalleryValue = $this->resourceConnection
            ->getTableName($tableName);
        try {
            $connection->startSetup();

            $connection->delete(
                $catalogEntityMediaGalleryValue,
                [
                    'value_id NOT IN (?)' => $mediaEntries,
                    'entity_id = (?)' => $productId
                ]
            );
            $connection->endSetup();
        } catch (Exception $e) {
            $this->logger->debug(
                'Problem with Media path delete in : ' . $catalogEntityMediaGalleryValue .
                ' for product_id:' . $productId
            );
        }
    }

    /**
     * Check if image file exists
     *
     * @param string $fileName
     * @return bool
     * @throws FileSystemException
     */
    public function imageExists($fileName): bool
    {
        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $mediaRootDir = $this->joinFilePaths($mediaDirectory->getAbsolutePath(), 'catalog', 'product');

        return $this->file->isExists($this->joinFilePaths($mediaRootDir, $fileName));
    }

    /**
     * Delete duplicated image files
     *
     * @param string $fileName
     * @return void
     */
    public function deleteDuplicateCatalogImage($fileName): void
    {
        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $mediaRootDir = $this->joinFilePaths($mediaDirectory->getAbsolutePath(), 'catalog', 'product');

        try {
            if ($this->file->isExists($this->joinFilePaths($mediaRootDir, $fileName))) {
                $this->file->deleteFile($mediaRootDir . $fileName);
            }
        } catch (Exception $e) {
            $this->logger->debug(
                'Problem with deleting file : ' . $fileName
            );
        }
    }

    /**
     * Returns image hash by path
     *
     * @param string $path
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function getFileHash(string $path): string
    {
        $content = '';
        if ($this->mediaDirectory->isFile($path)
            && $this->mediaDirectory->isReadable($path)
        ) {
            $content = $this->mediaDirectory->readFile($path);
        }
        return $content ? hash(self::HASH_ALGORITHM, $content) : '';
    }

    /**
     * Returns product media
     *
     * @return string relative path to root folder
     */
    private function getProductMediaPath(): string
    {
        return $this->joinFilePaths($this->getMediaBasePath(), 'catalog', 'product');
    }

    /**
     * Returns media base path
     *
     * @return string relative path to root folder
     */
    private function getMediaBasePath(): string
    {
        $mediaDir = !is_a($this->mediaDirectory->getDriver(), File::class)
            // make media folder a primary folder for media in external storages
            ? $this->filesystem->getDirectoryReadByPath(DirectoryList::MEDIA)
            : $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

        return $this->mediaDirectory->getRelativePath($mediaDir->getAbsolutePath());
    }

    /**
     * Joins two paths and remove redundant directory separator
     *
     * @param array $paths
     * @return string
     */
    private function joinFilePaths(...$paths): string
    {
        $result = '';
        if ($paths) {
            $result = rtrim(array_shift($paths), DIRECTORY_SEPARATOR);
            foreach ($paths as $path) {
                $result .= DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
            }
        }
        return $result;
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
            ['field' => 'main_table.scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq']
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
        $websiteId = $this->store->getWebsiteId();
        $this->replicationHelper->applyProductWebsiteJoin($collection, $websiteId);
        $collection->getSelect()->order('main_table.processed ASC');

        $query = $collection->getSelect()->__toString();
        return $collection;
    }
}
