<?php

namespace Ls\Replication\Cron;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Ls\Replication\Model\ReplBarcodeRepository;
use Psr\Log\LoggerInterface;
use Ls\Replication\Helper\ReplicationHelper;
use Ls\Core\Model\LSR;

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

    /** @var LSR */
    protected $_lsr;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ReplBarcodeRepository $replBarcodeRepository,
        LoggerInterface $logger,
        ReplicationHelper $replicationHelper,
        LSR $LSR
    )
    {
        $this->productRepository = $productRepository;
        $this->replBarcodeRepository = $replBarcodeRepository;
        $this->logger = $logger;
        $this->replicationHelper = $replicationHelper;
        $this->_lsr=$LSR;
    }

    public function execute()
    {
        $CronProductCheck= $this->_lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_PRODUCT);
        if($CronProductCheck==1) {
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
        else {
            $this->logger->debug("Barcode Replication cron fails because product replication cron not executed successfully.");
        }
    }


    /**
     * @return array
     */
    public function executeManually()
    {
        $this->execute();
        $criteria = $this->replicationHelper->buildCriteriaForNewItems();
        $replBarcodes = $this->replBarcodeRepository->getList($criteria);
        $barcodesLeftToProcess=count($replBarcodes->getItems());
        return array($barcodesLeftToProcess);
    }

}