<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Model\ReplImageLink;
use \Ls\Replication\Model\ReplImageLinkSearchResults;
use \Ls\Replication\Model\ResourceModel\ReplImageLink\Collection;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Filesystem\Driver\File;
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

    private const HASH_ALGORITHM = 'sha256';

    /** @var array  */
    public array $imageHashes = [];

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
                    $this->logger->debug('End SyncImages Task with remaining : '
                        . $this->getRemainingRecords($this->store));
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
                    $this->getExistingImageswithHash($sku);
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
     * Fetch existing images based on sku and add image hashes.
     *
     * @param $itemSku
     * @return void
     * @throws FileSystemException
     */
    public function getExistingImageswithHash($itemSku)
    {
        $filterArr = [];
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('sku', "%".$itemSku."%", 'like')
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
     * @param $existingImages
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

        //To remove duplicated images
        $this->removeDuplicatedImages($productData);
    }

    /**
     * Custom function to remove duplicated images for the sku and its variants.
     *
     * @throws FileSystemException
     * @throws FileSystemException
     */
    public function removeDuplicatedImages($productData)
    {
        $mediaGalleryImages = $productData->getMediaGalleryImages();

        $productMediaPath = $this->getProductMediaPath();

        foreach ($mediaGalleryImages as $galleryImage) {
            $filePath = $galleryImage->getFile();
            $hash     = $this->getFileHash($this->joinFilePaths($productMediaPath, $filePath));
            if ($hash && !in_array($hash, $this->imageHashes)) {
                $this->imageHashes[$filePath] = $hash;
            } else {
                $existingFilePath    = array_search($hash, $this->imageHashes);

                $this->updateMediaPaths('catalog_product_entity_varchar', $existingFilePath, $filePath);
                $this->updateMediaPaths('catalog_product_entity_media_gallery', $existingFilePath, $filePath);

                $this->deleteDuplicateCatalogImage($filePath);
            }
        }
    }

    /**
     * Update duplicated catalog image paths with already existing image file
     *
     * @param $tableName
     * @param $existingFilePath
     * @param $newFilePath
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
                $tableName,
                $updateData,
                $whereCondition
            );
            $connection->endSetup();
        } catch (Exception $e) {
            $this->logger->debug(
                'Problem with Media path update in : ' . $tableName .
                ' for ' . $newFilePath . ' with '.$existingFilePath
            );
        }
    }

    /**
     * Delete duplicated image files
     *
     * @param $fileName
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
        if ($this->_mediaDirectory->isFile($path)
            && $this->_mediaDirectory->isReadable($path)
        ) {
            $content = $this->_mediaDirectory->readFile($path);
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
        $mediaDir = !is_a($this->_mediaDirectory->getDriver(), File::class)
            // make media folder a primary folder for media in external storages
            ? $this->filesystem->getDirectoryReadByPath(DirectoryList::MEDIA)
            : $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

        return $this->_mediaDirectory->getRelativePath($mediaDir->getAbsolutePath());
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
