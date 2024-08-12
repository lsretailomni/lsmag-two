<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Fixture;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Fixture\DataFixtureInterface;

class LogFile implements DataFixtureInterface
{
    private const DEFAULT_DATA = [
        'len' => '10'
    ];

    /** @var StoreManagerInterface */
    public $storeManager;

    /**
     * @var Filesystem
     */
    public $filesystem;

    /**
     * @var DirectoryList
     */
    public $directoryList;

    /**
     * @var File
     */
    public $driverFile;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Filesystem $filesystem
     * @param DirectoryList $directoryList
     * @param File $driverFile
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        DirectoryList $directoryList,
        File $driverFile
    ) {
        $this->storeManager  = $storeManager;
        $this->filesystem    = $filesystem;
        $this->directoryList = $directoryList;
        $this->driverFile    = $driverFile;
    }

    /**
     * Apply fixture data
     *
     * @param array $data
     * @return DataObject|null
     * @throws LocalizedException
     */
    public function apply(array $data = []): ?DataObject
    {
        $data = array_merge(self::DEFAULT_DATA, $data);

        if (isset($data['log_file_name'])) {
            $logDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::LOG);
            $fileName     = $logDirectory->getAbsolutePath() . $data['log_file_name'];
            $this->deleteFile($fileName);
            $contents = $this->generateRandomString($data['len']);

            if (!empty($contents)) {
                $logDirectory->writeFile($fileName, $contents);
            }
        }
        return new DataObject();
    }

    public function deleteFile($fileName)
    {
        if ($this->driverFile->isExists($fileName)) {
            $file = $this->driverFile->fileOpen($fileName, 'w+');
            $this->driverFile->deleteFile($fileName);
            $this->driverFile->fileClose($file);
        }
    }

    public function generateRandomString($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}
