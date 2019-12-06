<?php
// @codingStandardsIgnoreFile

namespace Ls\Replication\Code;

use Exception;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Omni\Service\Soap\ReplicationOperation;
use \Ls\Replication\Model\Anchor;
use Magento\Framework\Api\SearchResults;

/**
 * Class SearchGenerator
 * @package Ls\Replication\Code
 */
class SearchGenerator extends AbstractGenerator
{
    /** @var string */
    static public $namespace = 'Ls\\Replication\\Model';

    /** @var ReplicationOperation */
    protected $operation;

    /**
     * SearchGenerator constructor.
     * @param ReplicationOperation $operation
     * @throws Exception
     */

    public function __construct(ReplicationOperation $operation)
    {
        parent::__construct();
        $this->operation = $operation;
    }

    /**
     * @return string
     */
    public function generate()
    {
        $this->class->setNamespaceName(self::$namespace);
        $this->class->setName($this->operation->getSearchName());
        $this->class->addUse($this->operation->getSearchInterfaceFqn());
        $this->class->addUse(SearchResults::class);
        $this->class->setExtendedClass('SearchResults');
        $this->class->setImplementedInterfaces([$this->operation->getSearchInterfaceName()]);
        $interface_name = $this->operation->getSearchInterfaceName();
        $content        = $this->file->generate();
        $content        = str_replace('extends Magento\\Framework\\Model\\AbstractModel', 'extends AbstractModel',
            $content);
        $content        = str_replace(', Magento\\Framework\\DataObject\\IdentityInterface', ', IdentityInterface',
            $content);
        $content        = str_replace('extends \SearchResults', 'extends SearchResults', $content);
        $content        = str_replace("implements \\{$interface_name}", "implements {$interface_name}", $content);
        return $content;
    }
}
