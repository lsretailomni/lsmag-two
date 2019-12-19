<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Model\ReplImageLink;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;

/**
 * Class SyncInventory
 * @package Ls\Replication\Cron
 */
class SyncImages extends ProductCreateTask
{
    /** @var string */
    const CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_item_images_sync';

    /** @var bool */
    public $cronStatus = false;

    public function execute()
    {
        $this->replicationHelper->updateConfigValue(
            $this->replicationHelper->getDateTime(),
            self::CONFIG_PATH_LAST_EXECUTE
        );
        $this->logger->debug('Running SyncImages Task');

        $this->syncItemImages();

        $this->replicationHelper->updateCronStatus($this->cronStatus, LSR::SC_SUCCESS_CRON_ITEM_IMAGES);
        $this->logger->debug('End SyncImages Task');
    }

    /**
     * @return array
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws StateException
     */
    public function executeManually()
    {
        $this->execute();
        $filters  = [
            ['field' => 'main_table.TableName', 'value' => 'Item', 'condition_type' => 'eq'],
            ['field' => 'second.processed', 'value' => 1, 'condition_type' => 'eq']
        ];
        $criteria = $this->replicationHelper->buildCriteriaForArrayWithAlias(
            $filters,
            -1,
            false
        );

        /** @var  $collection */
        $collection = $this->replImageLinkCollectionFactory->create();

        /** we only need unique product Id's which has any images to modify */
        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'KeyValue',
            'ls_replication_repl_item',
            'nav_id',
            true
        );
        return [$collection->getSize()];
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
            ['field' => 'main_table.TableName', 'value' => 'Item', 'condition_type' => 'eq'],
            ['field' => 'second.processed', 'value' => 1, 'condition_type' => 'eq']
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
            'ls_replication_repl_item',
            'nav_id',
            true
        );
        $collection->getSelect()->order('main_table.processed '. "ASC");
        if ($collection->getSize() > 0) {
            // right now the only thing we have to do is flush all the images and do it again.
            /** @var ReplImageLink $itemImage */
            foreach ($collection->getItems() as $itemImage) {
                $productImages = [];
                try {
                    $productData = $this->productRepository->get($itemImage->getKeyValue());
                    // get existing product images.
                    $productImages = $productData->getMediaGalleryEntries();


                    if (!empty($productImages)) {
                        foreach ($productImages as $key => $existingImage) {
                            $this->imageProcessor->removeImage($productData, $existingImage->getFile());
                            unset($productImages[$key]);
                        }
                    }
                    // check for new images.
                    $filtersforallImages  = [
                        ['field' => 'KeyValue', 'value' => $itemImage->getKeyValue(), 'condition_type' => 'eq'],
                        ['field' => 'TableName', 'value' => $itemImage->getTableName(), 'condition_type' => 'eq']
                    ];
                    $criteriaforallImages = $this->replicationHelper->buildCriteriaForDirect(
                        $filtersforallImages,
                        -1,
                        false
                    )->setSortOrders([$sortOrder]);


                    /** @var \Ls\Replication\Model\ReplImageLinkSearchResults $newImagestoProcess */
                    $newImagestoProcess = $this->replImageLinkRepositoryInterface->getList($criteriaforallImages);
                    if ($newImagestoProcess->getTotalCount() > 0) {
                        $productImages = $this->getMediaGalleryEntries($newImagestoProcess->getItems());
                    }
                    $productData->setMediaGalleryEntries($productImages);
                    $this->productRepository->save($productData);

                    // check if any variant for that product has image to process.
                    // check for variant new images.
                    $filtersforvariantImages = [
                        ['field' => 'KeyValue', 'value' => $itemImage->getKeyValue() . ',%', 'condition_type' => 'like'],
                        ['field' => 'TableName', 'value' => 'Item Variant', 'condition_type' => 'eq']
                    ];
                    //if the item is processed, then its variant will deinately be processed, so not neeed to put seprate check on variants.
                    $criteriaforallVariantImages = $this->replicationHelper->buildCriteriaForArray(
                        $filtersforvariantImages,
                        -1,
                        false
                    );
                    /** @var \Ls\Replication\Model\ReplImageLinkSearchResults $newVaraintImagestoProcess */
                    $newVaraintImagestoProcess = $this->replImageLinkRepositoryInterface->getList(
                        $criteriaforallVariantImages
                    );
                    if ($newVaraintImagestoProcess->getTotalCount() > 0) {
                        $processedItems = [];
                        /** @var ReplImageLink $image */
                        foreach ($newVaraintImagestoProcess->getItems() as $image) {
                            if (in_array($image->getKeyValue(), $processedItems, true)) {
                                continue;
                            }
                            $item = $image->getKeyValue();
                            $item = str_replace(',', '-', $item);
                            try {
                                /* @var ProductRepositoryInterface $variantProductData */
                                $variantProductData = $this->productRepository->get($item);
                                // get existing product images.
                                $variantproductImages = $variantProductData->getMediaGalleryEntries();

                                if (!empty($variantproductImages)) {
                                    foreach ($variantproductImages as $key => $existingImage) {
                                        $this->imageProcessor->removeImage(
                                            $variantProductData,
                                            $existingImage->getFile()
                                        );
                                        unset($variantproductImages[$key]);
                                    }
                                }

                                $filtersforvariantImagestoUpdate  = [
                                    ['field' => 'KeyValue', 'value' => $image->getKeyValue(), 'condition_type' => 'eq'],
                                    ['field' => 'TableName', 'value' => 'Item Variant', 'condition_type' => 'eq']
                                ];
                                $criteriaforVariantImagestoUpdate = $this->replicationHelper->buildCriteriaForDirect(
                                    $filtersforvariantImagestoUpdate,
                                    -1,
                                    false
                                )
                                    ->setSortOrders([$sortOrder]);

                                /** @var \Ls\Replication\Model\ReplImageLinkSearchResults $varaintImagestoupdate */
                                $varaintImagestoupdate            = $this->replImageLinkRepositoryInterface->getList(
                                    $criteriaforVariantImagestoUpdate
                                );

                                if ($varaintImagestoupdate->getTotalCount() > 0) {
                                    $variantproductImages = $this->getMediaGalleryEntries(
                                        $varaintImagestoupdate->getItems()
                                    );
                                }
                                $variantProductData->setMediaGalleryEntries($variantproductImages);
                                $this->productRepository->save($variantProductData);
                            } catch (Exception $e) {
                                $this->logger->debug('Problem with SKU : ' . $item . ' in ' . __METHOD__);
                                $this->logger->debug($e->getMessage());
                            }
                            // Adding items into an array whose images are processed.
                            $processedItems[] = $image->getKeyValue();
                        }
                    }
                } catch (Exception $e) {
                    $this->logger->debug(
                        'Problem with Image Synchronization : ' . $itemImage->getKeyValue() . ' in ' . __METHOD__
                    );
                    $this->logger->debug($e->getMessage());
                }
            }
        } else {
            $this->cronStatus = true;
        }
    }

    /** TODO reuse the logic and optimize the solution
     * DO NOT REMOVE THIS CODE
     */
    private function syncItemImagesOld()
    {
        $batchSize = $this->replicationHelper->getProductImagesBatchSize();
        /** Get Images for only those items which are already processed */
        $sortOrder = $this->replicationHelper->getSortOrderObject();
        $filters   = [
            ['field' => 'main_table.TableName', 'value' => 'Item', 'condition_type' => 'eq'],
            ['field' => 'second.processed', 'value' => 1, 'condition_type' => 'eq']
        ];
        $criteria  = $this->replicationHelper->buildCriteriaForArrayWithAlias(
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
            'ls_replication_repl_item',
            'nav_id',
            true
        );
        if ($collection->getSize() > 0) {
            /** @var ReplImageLink $itemImage */
            foreach ($collection->getItems() as $itemImage) {
                $productImages = [];
                $deletedImages = [];
                $newImages     = [];
                try {
                    $productData = $this->productRepository->get($itemImage->getKeyValue());
                    // get existing product images.
                    $productImages = $productData->getMediaGalleryEntries();
                    // check for deleted images.
                    $filtersforDeleted  = [
                        ['field' => 'KeyValue', 'value' => $itemImage->getKeyValue(), 'condition_type' => 'eq'],
                        ['field' => 'TableName', 'value' => $itemImage->getTableName(), 'condition_type' => 'eq'],
                        ['field' => 'IsDeleted', 'value' => 1, 'condition_type' => 'eq']

                    ];
                    $criteriaforDeleted = $this->replicationHelper->buildCriteriaForArray($filtersforDeleted, -1, false);
                    /** @var \Ls\Replication\Model\ReplImageLinkSearchResults $deletedImages */
                    $deletedImages = $this->replImageLinkRepositoryInterface->getList($criteriaforDeleted);
                    if ($deletedImages->getTotalCount() > 0) {
                        /** @var ReplImageLink $image */
                        foreach ($deletedImages->getItems() as $image) {
                            // check if deleted image is already in the loaded media galleries
                            if (!empty($productImages)) {
                                foreach ($productImages as $key => $existingImage) {
                                    /** here we can integrate all the  business logic to identify unique image,
                                     * right now we are doing it based on the image name
                                     **/
                                    $imagename = $this->replicationHelper->parseImageIdfromFile($existingImage->getFile());
                                    if ($imagename == $image->getImageId()) {
                                        $this->imageProcessor->removeImage($productData, $existingImage->getFile());
                                        unset($productImages[$key]);
                                    }
                                }
                            }
                            $image->setData('processed', 1);
                            $image->setData('is_updated', 0);
                            // @codingStandardsIgnoreLine
                            $this->replImageLinkRepositoryInterface->save($image);
                        }
                    }
                    // check for new images.
                    $filtersforNewOrUpdatedImages  = [
                        ['field' => 'KeyValue', 'value' => $itemImage->getKeyValue(), 'condition_type' => 'eq'],
                        ['field' => 'TableName', 'value' => $itemImage->getTableName(), 'condition_type' => 'eq']
                    ];
                    $criteriaforNewOrUpdatedImages = $this->replicationHelper->buildCriteriaForArray($filtersforNewOrUpdatedImages, -1)
                        ->setSortOrders([$sortOrder]);

                    /** @var \Ls\Replication\Model\ReplImageLinkSearchResults $newImagestoProcess */
                    $newImagestoProcess = $this->replImageLinkRepositoryInterface->getList($criteriaforNewOrUpdatedImages);
                    if ($newImagestoProcess->getTotalCount() > 0) {
                        /** @var ReplImageLink $image */
                        $newImages = $this->getMediaGalleryEntries($newImagestoProcess->getItems(), $productImages);
                    }
                    // merge array of new Images into existing product images but
                    //TODO do not pass those new images which already exist in the product Images.
                    $productImages = array_merge($productImages, $newImages);
                    $productData->setMediaGalleryEntries($productImages);
                    $this->productRepository->save($productData);
                } catch (Exception $e) {
                    $this->logger->debug('Problem with Image Synchronization : ' . $itemImage->getKeyValue() . ' in ' . __METHOD__);
                    $this->logger->debug($e->getMessage());
                }
            }
        }
    }

}
