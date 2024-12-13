<?php

namespace Ls\Replication\Controller\Adminhtml\Logs;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

/**
 * Class Report
 * @package Ls\Replication\Controller\Adminhtml\Logs
 */
class Report extends Action
{
    public $allowedFiles = [
        'omniclient.log',
        'debug.log',
        'exception.log',
        'replication.log',
        'webhookstatus.log',
        'system.log'
    ];

    /** @var PageFactory */
    public $resultPageFactory;

    /** @var ObjectManagerInterface */
    public $objectManager;

    /** @var LoggerInterface */
    public $logger;

    /**
     * @var Magento\Framework\App\Response\Http\FileFactory
     */
    public $downloader;

    /**
     * @var File
     */
    public $driverFile;

    /**
     * @var Filesystem
     */
    public $fileSystem;

    /**
     * @var Object
     */
    public $logDirectory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ObjectManagerInterface $objectManager
     * @param LoggerInterface $logger
     * @param FileFactory $fileFactory
     * @param File $driverFile
     * @param Filesystem $filesystem
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ObjectManagerInterface $objectManager,
        LoggerInterface $logger,
        FileFactory $fileFactory,
        File $driverFile,
        Filesystem $filesystem
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->objectManager     = $objectManager;
        $this->logger            = $logger;
        $this->downloader        = $fileFactory;
        $this->driverFile        = $driverFile;
        $this->fileSystem        = $filesystem;
        parent::__construct($context);
    }

    /**
     * Get the log file information
     *
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        try {
            $message    = '';
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->prepend(__('Logs '));
            $logFileName = $this->_request->getParam('log_filename');
            $submitType  = $this->_request->getParam('submission');
            if (!empty($logFileName) && in_array($logFileName, $this->allowedFiles)) {
                $this->logDirectory = $this->fileSystem->getDirectoryWrite(DirectoryList::LOG);
                $fileName           = $this->logDirectory->getAbsolutePath() . $logFileName;
                if ($this->driverFile->isExists($fileName)) {
                    if ($submitType == "Download") {
                        return $this->downloader->create(
                            $fileName,
                            $this->driverFile->fileGetContents($fileName)
                        );
                    }
                    if ($submitType == "Clear") {
                        $file = $this->driverFile->fileOpen($fileName, 'w+');
                    } else {
                        $file = $this->driverFile->fileOpen($fileName, "r");
                    }
                    if ($submitType == "Clear") {
                        $this->driverFile->deleteFile($fileName);
                    } else {
                        // restrict file to 5MB to open
                        $size = $this->logDirectory->stat($fileName)['size'];
                        if ($size >= 5000000) {
                            $message = __("File size is too large to render. Please download the file");
                        } elseif ($size > 0) {
                            $message = $this->driverFile->fileRead($file, $size);
                        } else {
                            $message = __("No Data Found File is Empty.");
                        }
                    }
                    $this->driverFile->fileClose($file);
                } else {
                    $message = __("File Not Found.");
                }
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
        }
        $block = $resultPage->getLayout()->getBlock('ls.replication.report');
        $block->setData('message', $message);
        return $resultPage;
    }
}
