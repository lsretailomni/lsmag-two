<?php
// @codingStandardsIgnoreFile

namespace Ls\Replication\Code;

use Ls\Core\Code\AbstractGenerator;
use Ls\Omni\Service\Soap\ReplicationOperation;
use Magento\Framework\Api\SearchResultsInterface;
use Zend\Code\Generator\ParameterGenerator;

/**
 * Class SearchInterfaceGenerator
 * @package Ls\Replication\Code
 */
class SearchInterfaceGenerator extends AbstractGenerator
{
    /** @var string */
    static public $namespace = "Ls\\Replication\\Api\\Data";

    /** @var ReplicationOperation */
    protected $operation;

    /**
     * SearchInterfaceGenerator constructor.
     * @param ReplicationOperation $operation
     * @throws \Exception
     */
    public function __construct(ReplicationOperation $operation)
    {

        parent::__construct();
        $this->class = new InterfaceGenerator();
        $this->file->setClass($this->class);
        $this->operation = $operation;
    }

    /**
     * @return string
     */
    public function generate()
    {
        $this->class->setNamespaceName(self::$namespace);
        $this->class->setName($this->operation->getSearchInterfaceName());
        $this->class->addUse(SearchResultsInterface::class);
        $this->class->addMethod('getItems');
        $this->class->addMethod('setItems', [ParameterGenerator::fromArray(['name' => 'items', 'type' => 'array'])]);
        $content = $this->file->generate();
        $interface_name = "interface {$this->operation->getSearchInterfaceName()}";
        $not_abstract = <<<CODE

    {
    }

CODE;

        $content = preg_replace('/\s+{\s+}+/', ";", $content);
        $content = str_replace(
            $interface_name,
            "$interface_name extends SearchResultsInterface",
            $content
        );

        return $content;
    }
}
