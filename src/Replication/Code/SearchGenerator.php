<?php
// @codingStandardsIgnoreFile
declare(strict_types=1);

namespace Ls\Replication\Code;

use Exception;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Omni\Service\Soap\ReplicationOperation;
use Magento\Framework\Api\SearchResults;

/**
 * Generates a model class that extends Magento's SearchResults and implements a custom search interface.
 *
 * @package Ls\Replication\Code
 */
class SearchGenerator extends AbstractGenerator
{
    /** @var string $namespace Namespace for the generated search result model */
    public static $namespace = 'Ls\\Replication\\Model\\Central';

    /**
     * @param ReplicationOperation $operation
     * @throws Exception
     */
    public function __construct(public ReplicationOperation $operation)
    {
        parent::__construct();
    }

    /**
     * Generate the class content for the search result model.
     *
     * @return string Generated class PHP code
     */
    public function generate(): string
    {
        $this->class->setNamespaceName(self::$namespace);
        $this->class->setName($this->operation->getSearchName());
        $this->class->addUse($this->operation->getSearchInterfaceFqn());
        $this->class->addUse(SearchResults::class);
        $this->class->setExtendedClass('SearchResults');
        $this->class->setImplementedInterfaces([$this->operation->getSearchInterfaceName()]);

        $interfaceName = $this->operation->getSearchInterfaceName();
        $content       = $this->file->generate();

        // Clean up generated content for proper naming and formatting
        $content = str_replace(
            'extends Magento\\Framework\\Model\\AbstractModel',
            'extends AbstractModel',
            $content
        );
        $content = str_replace(
            ', Magento\\Framework\\DataObject\\IdentityInterface',
            ', IdentityInterface',
            $content
        );
        $content = str_replace('extends \SearchResults', 'extends SearchResults', $content);

        return str_replace("implements \\{$interfaceName}", "implements {$interfaceName}", $content);
    }
}
