<?php
// @codingStandardsIgnoreFile
declare(strict_types=1);

namespace Ls\Replication\Code;

use Exception;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Omni\Service\Soap\ReplicationOperation;
use Magento\Framework\Api\SearchResultsInterface;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\InterfaceGenerator;

/**
 * Generates an API Data Search Interface extending Magento's SearchResultsInterface.
 */
class SearchInterfaceGenerator extends AbstractGenerator
{
    /** @var string $namespace Namespace for generated API Data interfaces */
    public static $namespace = "Ls\\Replication\\Api\\Data";

    /** @var ReplicationOperation $operation Holds the replication operation details */
    public $operation;

    /**
     * @param ReplicationOperation $operation
     * @throws Exception
     */
    public function __construct(ReplicationOperation $operation)
    {
        parent::__construct();
        $this->class = new InterfaceGenerator();
        $this->file->setClass($this->class);
        $this->operation = $operation;
    }

    /**
     * Generates the PHP interface code as string.
     *
     * @return string Generated interface PHP code
     */
    public function generate(): string
    {
        $this->class->setNamespaceName(self::$namespace);
        $this->class->setName($this->operation->getSearchInterfaceName());
        $this->class->addUse(SearchResultsInterface::class);

        // Add getItems method without parameters
        $this->class->addMethod('getItems');

        // Add setItems method with array parameter named 'items'
        $this->class->addMethod(
            'setItems',
            [ParameterGenerator::fromArray(['name' => 'items', 'type' => 'array'])]
        );

        $content = $this->file->generate();

        $interfaceName = "interface {$this->operation->getSearchInterfaceName()}";
        // Remove empty method bodies and replace with semicolon (interface method declarations)
        $content = preg_replace('/\s+{\s+}+/', ";", $content);

        // Add "extends SearchResultsInterface" to interface declaration
        return str_replace(
            $interfaceName,
            "$interfaceName extends SearchResultsInterface",
            $content
        );
    }
}
