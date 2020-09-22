<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Model\ReplImageLink;
use \Ls\Replication\Model\ReplImageLinkSearchResults;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Class SyncInventory
 * @package Ls\Replication\Cron
 */
class SyncImages extends ProductCreateTask
{
    /** @var bool */
    public $cronStatus = false;

    /** @var int */
    public $remainingRecords;

    /**
     * @param null $storeData
     * @throws InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
                    $this->replicationHelper->updateConfigValue($this->replicationHelper->getDateTime(),
                        LSR::SC_ITEM_IMAGES_CONFIG_PATH_LAST_EXECUTE, $this->store->getId());
                    $this->replicationHelper->setEnvVariables();
                    $this->logger->debug('Running SyncImages Task for store ' . $this->store->getName());
                    $this->syncItemImages();
                    $this->replicationHelper->updateCronStatus($this->cronStatus, LSR::SC_SUCCESS_CRON_ITEM_IMAGES,
                        $this->store->getId());
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
        $batchSize = $this->replicationHelper->getProductImagesBatchSize();
        $sortOrder = $this->replicationHelper->getSortOrderObject();
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
        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'KeyValue',
            'catalog_product_entity',
            'sku',
            true,
            true
        );
        $collection->getSelect()->order('main_table.processed ASC');
        if ($collection->getSize() > 0) {
            // Right now the only thing we have to do is flush all the images and do it again.
            /** @var ReplImageLink $itemImage */
            foreach ($collection->getItems() as $itemImage) {
                try {
                    $itemSku  = $itemImage->getKeyValue();
                    $itemSku  = str_replace(',', '-', $itemSku);
                    $sku      = $itemImage->getData('sku');
                    $uomCodes = $this->getUomCodes($sku);
                    if (!empty($uomCodes)) {
                        if (count($uomCodes[$sku]) > 1) {
                            foreach ($uomCodes[$sku] as $uomCode) {
                                $this->processImages($itemImage, $sortOrder, $itemSku, $uomCode);
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
        if (!$this->remainingRecords) {
            $filters  = [
                ['field' => 'main_table.TableName', 'value' => 'Item%', 'condition_type' => 'like'],
                ['field' => 'main_table.TableName', 'value' => 'Item Category', 'condition_type' => 'neq'],
                ['field' => 'main_table.scope_id', 'value' => $storeData->getId(), 'condition_type' => 'eq']
            ];
            $criteria = $this->replicationHelper->buildCriteriaForArrayWithAlias(
                $filters,
                -1,
                false
            );
            /** @var  $collection */
            $collection = $this->replImageLinkCollectionFactory->create();
            /** We only need sku which has any images to modify */
            $this->replicationHelper->setCollectionPropertiesPlusJoin(
                $collection,
                $criteria,
                'KeyValue',
                'catalog_product_entity',
                'sku',
                true,
                true
            );
            $this->remainingRecords = $collection->getSize();
        }
        return $this->remainingRecords;
    }

    /**
     * @param ReplImageLinkSearchResults $imagesToUpdate
     * @param ProductInterface $productData
     * @throws InputException
     * @throws LocalizedException
     * @throws StateException
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
                $this->updateHandler->execute($productData);
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processImages($itemImage, $sortOrder, $itemSku, $uomCode = null)
    {
        if (!empty($uomCode)) {
            $itemSku = $itemSku . '-' . $uomCode;
        }
        $productData = $this->productRepository->get($itemSku, true, $this->store->getId(), true);
        $productData->setData('store_id', 0);
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
        foreach ($mediaGalleryEntries as $entry) {
            $images[] = $this->entryConverterPool
                ->getConverterByMediaType($entry->getMediaType())
                ->convertFrom($entry);
        }
        return $images;
    }
}
