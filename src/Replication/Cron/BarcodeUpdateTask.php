<?php

namespace Ls\Replication\Cron;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Ls\Replication\Model\ReplBarcodeRepository;
use Psr\Log\LoggerInterface;
use Ls\Replication\Helper\ReplicationHelper;

class BarcodeUpdateTask
{
    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var ReplBarcodeRepository */
    protected $replBarcodeRepository;

    /** @var ReplicationHelper */
    protected $replicationHelper;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ReplBarcodeRepository $replBarcodeRepository,
        LoggerInterface $logger,
        ReplicationHelper $replicationHelper
    )
    {
        $this->productRepository = $productRepository;
        $this->replBarcodeRepository = $replBarcodeRepository;
        $this->logger = $logger;
        $this->replicationHelper = $replicationHelper;
    }

    public function execute()
    {
        $criteria = $this->replicationHelper->buildCriteriaForNewItems();

        /** @var \Ls\Replication\Model\ReplBarcodeSearchResults $replAttributes */
        $replBarcodes = $this->replBarcodeRepository->getList($criteria);

        /** @var \Ls\Replication\Model\ReplBarcode $replBarcode */
        foreach ($replBarcodes->getItems() as $replBarcode) {
            if ($replBarcode->getIsUpdated() == 1) {
                try {
                    if (!$replBarcode->getVariantId())
                        $sku = $replBarcode->getItemId();
                    else
                        $sku = $replBarcode->getItemId() . '-' . $replBarcode->getVariantId();
                    $productData = $this->productRepository->get($sku);
                    if (isset($productData)) {
                        $productData->setCustomAttribute("barcode", $replBarcode->getNavId());
                        $productData->save();
                        $replBarcode->setData('is_updated', '0');
                        $replBarcode->save();
                    }
                } catch (\Exception $e) {
                    $this->logger->debug($e->getMessage());
                }
            }
        }

    }
}