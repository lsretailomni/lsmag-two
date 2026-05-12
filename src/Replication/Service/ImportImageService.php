<?php
declare(strict_types=1);

namespace Ls\Replication\Service;

use Exception;
use \Ls\Replication\Api\ReplImageLinkRepositoryInterface;
use \Ls\Replication\Logger\Logger;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Io\File;

/**
 * Assign images to products by image URL
 */
class ImportImageService
{
    /**
     * @param DirectoryList $directoryList
     * @param File $file
     * @param ReplImageLinkRepositoryInterface $replImageLinkRepositoryInterface
     * @param Logger $logger
     */
    public function __construct(
        public DirectoryList $directoryList,
        public File $file,
        public ReplImageLinkRepositoryInterface $replImageLinkRepositoryInterface,
        public Logger $logger
    ) {
    }

    /**
     * Main service executor
     *
     * @param $product
     * @param $imageUrl
     * @param $repl_image_link_id
     * @param $visible
     * @param $imageType
     * @return bool|string
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function execute($product, $imageUrl, $repl_image_link_id, $visible = false, $imageType = [])
    {
        $tmpDir = $this->getMediaDirTmpDir();
        /** create folder if it is not exists */
        $this->file->checkAndCreateFolder($tmpDir);
        $replImageLink = $this->replImageLinkRepositoryInterface->getById($repl_image_link_id);
        $imageName     = $replImageLink->getImageId();
        $result        = false;
        try {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $ext = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);

            if (empty($ext)) {
                // phpcs:ignore Magento2.Exceptions.DirectThrow
                throw new Exception(
                    sprintf(
                        'Unable to find image extension with repl_image_link_id %s for product %s',
                        $repl_image_link_id,
                        $product->getSku()
                    )
                );
            }
            $newFileName = $tmpDir . DIRECTORY_SEPARATOR . $imageName . '.' . $ext;
            /** read file from URL and copy it to the new destination */
            $result = $this->file->read($imageUrl, $newFileName);

            if ($result) {
                /** add saved file to the $product gallery */
                $product->addImageToMediaGallery($newFileName, $imageType, true, $visible);
            } else {
                // phpcs:ignore Magento2.Exceptions.DirectThrow
                throw new Exception(
                    sprintf(
                        'Unable to fetch image with repl_image_link_id %s for product %s',
                        $repl_image_link_id,
                        $product->getSku()
                    )
                );
            }
        } catch (Exception $exception) {
            $this->logger->debug($exception->getMessage());
            $this->replImageLinkRepositoryInterface->save($replImageLink->setIsFailed(1));
        }

        return $result;
    }

    /**
     * Media directory name for the temporary file storage
     * pub/media/tmp
     *
     * @return string
     * @throws FileSystemException
     */
    protected function getMediaDirTmpDir()
    {
        return $this->directoryList->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . 'tmp';
    }
}
