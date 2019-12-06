<?php

namespace Ls\Replication\Controller\Adminhtml\Logs;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

/**
 * Class Report
 * @package Ls\Replication\Controller\Adminhtml\Logs
 */
class Report extends Action
{
    /** @var PageFactory */
    public $resultPageFactory;

    /** @var ObjectManagerInterface */
    public $objectManager;

    /** @var LoggerInterface */
    public $logger;

    /**
     * @var DirectoryList
     */
    public $directoryList;

    /**
     * @var Registry
     */
    public $coreRegistry;

    /**
     * @var Magento\Framework\App\Response\Http\FileFactory
     */
    public $downloader;

    /**
     * Report constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ObjectManagerInterface $objectManager
     * @param LoggerInterface $logger
     * @param DirectoryList $directoryList
     * @param Registry $coreRegistry
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ObjectManagerInterface $objectManager,
        LoggerInterface $logger,
        DirectoryList $directoryList,
        Registry $coreRegistry,
        FileFactory $fileFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->objectManager     = $objectManager;
        $this->logger            = $logger;
        $this->directoryList     = $directoryList;
        $this->coreRegistry      = $coreRegistry;
        $this->downloader        = $fileFactory;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        try {
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->prepend(__('Logs '));
            $logFileName = $this->_request->getParam('log_filename');
            $submitType  = $this->_request->getParam('submit');
            if (!empty($logFileName)) {
                $path     = $this->directoryList->getPath('var');
                $fileName = $path . "/log/" . $logFileName;
                if (file_exists($fileName)) {
                    if ($submitType == "Download") {
                        return $this->downloader->create(
                            $fileName,
                            @file_get_contents($fileName)
                        );
                    }
                    if ($submitType == "Clear") {
                        $myfile = fopen($fileName, 'w+');
                    } else {
                        $myfile = fopen($fileName, "r") or die("Unable to open file!");
                    }
                    if ($submitType == "Clear") {
                        ftruncate($myfile, 0);
                        $this->coreRegistry->register('display_log', "");
                    } else {
                        if (filesize($fileName) > 0) {
                            $info = fread($myfile, filesize($fileName));
                            $this->coreRegistry->register('display_log', $info);
                        } else {
                            $this->coreRegistry->register('display_log', "No Data Found File is Empty.");
                        }
                    }
                    fclose($myfile);
                } else {
                    $this->coreRegistry->register('display_log', "File Not Found.");
                }
            }
            return $resultPage;
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }
}
