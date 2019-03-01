<?php
// @codingStandardsIgnoreFile

namespace Ls\Replication\Code;

use Composer\Autoload\ClassLoader;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Omni\Service\Soap\ReplicationOperation;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Symfony\Component\Filesystem\Filesystem;
use Zend\Code\Generator\MethodGenerator;

/**
 * Class ResourceCollectionGenerator
 * @package Ls\Replication\Code
 */
class ResourceCollectionGenerator extends AbstractGenerator
{
    /** @var string */
    static public $namespace = 'Ls\\Replication\\Model\\ResourceModel';

    /** @var ReplicationOperation */
    protected $operation;

    /** @var Filesystem */
    protected $fs;

    /**
     * ResourceCollectionGenerator constructor.
     * @param ReplicationOperation $operation
     * @throws \Exception
     */
    public function __construct(ReplicationOperation $operation)
    {
        parent::__construct();
        $this->operation = $operation;
        $this->fs = new Filesystem();
    }


    public function createPath()
    {
        /** @var ClassLoader $loader */
        $path = $this->operation->getResourceCollectionPath(true);
        $folder_path = str_replace(DIRECTORY_SEPARATOR . 'Collection.php', '', $path);
        if (!$this->fs->exists($folder_path)) {
            $this->fs->mkdir($folder_path);
        }
    }

    /**
     * @return string
     */
    public function generate()
    {

        $model_class = $this->operation->getMainEntityFqn();
        $resource_model_class = $this->operation->getResourceModelFqn();

        $contructor_method = new MethodGenerator();
        $contructor_method->setName('_construct');
        $contructor_method->setBody("\$this->_init( '$model_class', '$resource_model_class' );");

        $this->class->setNamespaceName($this->operation->getResourceCollectionNamespace());
        $this->class->addUse(AbstractCollection::class);

        $this->class->setName('Collection');
        $this->class->setExtendedClass(AbstractCollection::class);

        $this->class->addMethodFromGenerator($contructor_method);

        $content = $this->file->generate();
        $content =
            str_replace(
                'extends Magento\\Framework\\Model\\ResourceModel\\Db\\Collection\\AbstractCollection',
                'extends AbstractCollection',
                $content
            );

        return $content;
    }
}
