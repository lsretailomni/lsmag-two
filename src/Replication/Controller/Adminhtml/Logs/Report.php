<?php
declare(strict_types=1);

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

class Report extends Action
{
    public $allowedFiles = [
        'omniclient.log',
        'debug.log',
        'exception.log',
        'replication.log',
        'flat_replication.log',
        'webhookstatus.log',
        'system.log'
    ];

    /**
     * @var Object
     */
    public $logDirectory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ObjectManagerInterface $objectManager
     * @param LoggerInterface $logger
     * @param FileFactory $downloader
     * @param File $driverFile
     * @param Filesystem $fileSystem
     */
    public function __construct(
        Context $context,
        public PageFactory $resultPageFactory,
        public ObjectManagerInterface $objectManager,
        public LoggerInterface $logger,
        public FileFactory $downloader,
        public File $driverFile,
        public Filesystem $fileSystem
    ) {
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
