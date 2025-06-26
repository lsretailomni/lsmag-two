<?php
declare(strict_types=1);

namespace Ls\Omni\Helper;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Session\Config;
use Magento\Framework\Session\SaveHandler;

class SessionHelper extends AbstractHelper
{
    /**
     * @param Context $context
     * @param SaveHandler $sessionHandler
     * @param DeploymentConfig $deploymentConfig
     * @param Filesystem $fileSystem
     */
    public function __construct(
        Context $context,
        public SaveHandler $sessionHandler,
        public DeploymentConfig $deploymentConfig,
        public Filesystem $fileSystem
    ) {
        parent::__construct($context);
    }

    /**
     * Resolving php session files locking problem when use session save method files in ajax requests
     *
     * @param $sessionName
     * @throws FileSystemException
     */
    public function newSessionHandler($sessionName)
    {
        $sessionType = $this->deploymentConfig->get(Config::PARAM_SESSION_SAVE_METHOD);
        //only needed for session save method files as redis and db method dont have this problem
        if ($sessionType && strtolower($sessionType) == "files") {
            $tmpSessionDir = $this->deploymentConfig->get(Config::PARAM_SESSION_SAVE_PATH);
            if (empty($tmpSessionDir)) {
                $tmpSessionDir = ini_get("session.save_path");
                if (empty($tmpSessionDir)) {
                    $sessionDir    = $this->fileSystem->getDirectoryWrite(DirectoryList::SESSION);
                    $tmpSessionDir = $sessionDir->getAbsolutePath();
                }
            }
            $this->sessionHandler->close();
            $this->sessionHandler->open($tmpSessionDir, $sessionName);
        }
    }
}
