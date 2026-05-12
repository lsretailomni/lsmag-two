<?php
// @codingStandardsIgnoreFile
declare(strict_types=1);

namespace Ls\Replication\Code;

use Exception;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Omni\Service\Soap\ReplicationOperation;
use Magento\Framework\Api\SearchCriteriaInterface;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\ParameterGenerator;

/**
 * Responsible for generating the repository interface for a replication entity.
 */
class RepositoryInterfaceGenerator extends AbstractGenerator
{
    /** @var string */
    public static string $namespace = "Ls\\Replication\\Api\\Central";

    /**
     * @param ReplicationOperation $operation
     * @throws Exception
     */
    public function __construct(public ReplicationOperation $operation)
    {
        parent::__construct();
        $this->class = new InterfaceGenerator();
        $this->file->setClass($this->class);
    }

    /**
     * Generate the repository interface class.
     *
     * @return string
     */
    public function generate(): string
    {
        $entityInterfaceName = $this->operation->getInterfaceName();

        $docBlock = DocBlockGenerator::fromArray([
            'shortDescription' => $this->disclaimer,
        ]);

        $this->class->setDocblock($docBlock);
        $this->class->setName($this->getName());
        $this->class->setNamespaceName(self::$namespace);
        $this->class->addUse($this->operation->getInterfaceFqn());
        $this->class->addUse(SearchCriteriaInterface::class);

        $this->class->addMethod('getList', [
            new ParameterGenerator('criteria', SearchCriteriaInterface::class)
        ]);

        $this->class->addMethod('save', [
            new ParameterGenerator('page', $entityInterfaceName)
        ]);

        $this->class->addMethod('delete', [
            new ParameterGenerator('page', $entityInterfaceName)
        ]);

        $this->class->addMethod('getById', [
            new ParameterGenerator('id')
        ]);

        $this->class->addMethod('deleteById', [
            new ParameterGenerator('id')
        ]);

        $content = $this->file->generate();

        $content = str_replace("\\$entityInterfaceName \$page", "$entityInterfaceName \$page", $content);
        $content = str_replace(
            "\Magento\\Framework\\Api\\SearchCriteriaInterface \$criteria",
            "SearchCriteriaInterface \$criteria",
            $content
        );

        // Remove empty method bodies
        $content = preg_replace('/\s+{\s+}+/', ';', $content);

        return $content;
    }

    /**
     * Get the repository interface name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->operation->getRepositoryInterfaceName();
    }
}
