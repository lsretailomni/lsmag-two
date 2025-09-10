<?php
// @codingStandardsIgnoreFile
declare(strict_types=1);

namespace Ls\Replication\Code;

use Composer\Autoload\ClassLoader;
use Exception;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Omni\Service\Soap\ReplicationOperation;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Symfony\Component\Filesystem\Filesystem;
use Laminas\Code\Generator\MethodGenerator;

/**
 * Generates a Magento Resource Collection class for replication entities.
 *
 * @package Ls\Replication\Code
 */
class ResourceCollectionGenerator extends AbstractGenerator
{
    /** @var string Namespace for the generated ResourceCollection class */
    public static $namespace = 'Ls\\Replication\\Model\\Central\\ResourceModel';

    /** @var ReplicationOperation $operation Holds the replication operation details */
    public $operation;

    /** @var Filesystem $filesystem Symfony Filesystem instance for file operations */
    public $filesystem;

    /**
     * @param ReplicationOperation $operation
     * @throws Exception
     */
    public function __construct(ReplicationOperation $operation)
    {
        parent::__construct();
        $this->operation = $operation;
        $this->filesystem = new Filesystem();
    }

    /**
     * Creates the directory path for the Resource Collection if it does not exist.
     *
     * @return void
     */
    public function createPath(): void
    {
        /** @var ClassLoader $loader */
        $path = $this->operation->getResourceCollectionPath(true);
        $folderPath = str_replace(DIRECTORY_SEPARATOR . 'Collection.php', '', $path);

        if (!$this->filesystem->exists($folderPath)) {
            $this->filesystem->mkdir($folderPath);
        }
    }

    /**
     * Generates the PHP code content for the Resource Collection class.
     *
     * @return string Generated PHP class content as string
     */
    public function generate(): string
    {
        $modelClass = $this->operation->getMainEntityFqn();
        $resourceModelClass = $this->operation->getResourceModelFqn();

        $constructorMethod = new MethodGenerator();
        $constructorMethod->setName('_construct');
        $constructorMethod->setBody(sprintf(
            '$this->_init(\'%s\', \'%s\');',
            $modelClass,
            $resourceModelClass
        ));

        $this->class->setNamespaceName($this->operation->getResourceCollectionNamespace());
        $this->class->addUse(AbstractCollection::class);

        $this->class->setName('Collection');
        $this->class->setExtendedClass(AbstractCollection::class);

        $this->class->addMethodFromGenerator($constructorMethod);

        $content = $this->file->generate();

        $content = str_replace(
            'extends Magento\\Framework\\Model\\ResourceModel\\Db\\Collection\\AbstractCollection',
            'extends AbstractCollection',
            $content
        );

        return $content;
    }
}
