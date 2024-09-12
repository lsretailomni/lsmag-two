<?php
// @codingStandardsIgnoreFile

namespace Ls\Replication\Console\Command;

use Exception;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Omni\Console\Command as OmniCommand;
use \Ls\Omni\Exception\InvalidServiceTypeException;
use \Ls\Omni\Service\Metadata as ServiceMetadata;
use \Ls\Omni\Service\Service;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client;
use \Ls\Omni\Service\Soap\Operation;
use \Ls\Replication\Code\CronJobConfigGenerator;
use \Ls\Replication\Code\CronJobGenerator;
use \Ls\Replication\Code\ModelGenerator;
use \Ls\Replication\Code\ModelInterfaceGenerator;
use \Ls\Replication\Code\RepositoryGenerator;
use \Ls\Replication\Code\RepositoryInterfaceGenerator;
use \Ls\Replication\Code\RepositoryTestGenerator;
use \Ls\Replication\Code\ResourceCollectionGenerator;
use \Ls\Replication\Code\ResourceModelGenerator;
use \Ls\Replication\Code\SchemaUpdateGenerator;
use \Ls\Replication\Code\SearchGenerator;
use \Ls\Replication\Code\SearchInterfaceGenerator;
use \Ls\Replication\Code\SystemConfigGenerator;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir\Reader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ReplicationGenerate
 * @package Ls\Replication\Console\Command
 */
class ReplicationGenerate extends OmniCommand
{
    const COMMAND_NAME = 'replication:generate';
    const SYSTEM = 'system';
    const CRON = 'config';

    /** @var boolean */
    public $system = false;

    /** @var boolean */
    public $cron = false;

    /** @var File */
    public $fileHelper;

    /** @var ServiceMetadata */
    private $metadata;

    /**
     * ReplicationGenerate constructor.
     * @param Service $service
     * @param Reader $dirReader
     * @param File $file
     */
    public function __construct(
        Service $service,
        Reader $dirReader,
        File $file
    ) {
        parent::__construct($service, $dirReader);
        $this->fileHelper = $file;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws InvalidServiceTypeException
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->type = ServiceType::ECOMMERCE();
        parent::initialize($input, $output);

        $client         = new Client(Service::getUrl($this->type, $this->base_url), $this->type);
        $this->metadata = $client->getMetadata(true);
        $this->system   = !!$this->input->getOption(self::SYSTEM);
        $this->cron     = !!$this->input->getOption(self::CRON);
    }

    public function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Generate replication related after running omni:client:generate')
            ->addOption(self::BASE_URL, 'b', InputOption::VALUE_OPTIONAL, 'omni service base url')
            ->addOption(self::CRON, 'c', InputOption::VALUE_OPTIONAL, 'display XML crontab configurations', false)
            ->addOption(self::SYSTEM, 's', InputOption::VALUE_OPTIONAL, 'display XML system configurations', false);
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int null or 0 if everything went fine, or an error code
     *
     * @see setCode()
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * Create Necessary paths if not exists
         */
        $this->createPathIfNotExist();

        /** @var Operation $operation */
        foreach ($this->metadata->getOperations() as $operation_name => $operation) {
            if (strpos($operation_name, 'ReplEcomm') !== false) {
                $this->processOperation($operation);
            }
        }
        try {
            // DECLARATIVE SCHEMA UPDATE Ls\Replication\etc\db_schema.xml
            $schemaUpdateGenerator = new SchemaUpdateGenerator($this->metadata);
            $schemaUpdateGenerator->generate();
        } catch (Exception $e) {
            $this->output->writeln("\t- - Error Start - -");
            $this->output->writeln("\tSomething went wrong, while creating the db_schema.xml");
            $this->output->writeln($e->getMessage());
            $this->output->writeln("\t- - Error End - -");
        }
        $this->output->writeln('Finish Generating Replication Task Files');

        return 0;
    }

    /**
     * Create required directories if not exists.
     */
    private function createPathIfNotExist()
    {
        $replicationBasePath = $this->dirReader->getModuleDir('', 'Ls_Replication');

        // For Replication API Data,
        if (!is_dir(AbstractGenerator::path($replicationBasePath, AbstractGenerator::fqn('Api/Data')))) {
            $this->fileHelper->mkdir(
                AbstractGenerator::path($replicationBasePath, AbstractGenerator::fqn('Api/Data')),
                0755
            );
        }

        // For Replication Cron
        if (!is_dir(AbstractGenerator::path($replicationBasePath, AbstractGenerator::fqn('Cron')))) {
            $this->fileHelper->mkdir(
                AbstractGenerator::path($replicationBasePath, AbstractGenerator::fqn('Cron')),
                0755
            );
        }

        // For Replication ResourceModel
        if (!is_dir(AbstractGenerator::path($replicationBasePath, AbstractGenerator::fqn('Model/ResourceModel')))) {
            $this->fileHelper->mkdir(
                AbstractGenerator::path($replicationBasePath, AbstractGenerator::fqn('Model/ResourceModel')),
                0755
            );
        }
    }

    /**
     * @param Operation $operation
     */
    private function processOperation(Operation $operation)
    {
        $replication_operation = $this->metadata->getReplicationOperationByName($operation->getName());

        try {
            if ($this->system) {
                $system_config_generator = new SystemConfigGenerator($replication_operation);
                $this->output->writeln($system_config_generator->generate());
            } elseif ($this->cron) {
                $cron_job_config_generator = new CronJobConfigGenerator($replication_operation);
                $this->output->writeln($cron_job_config_generator->generate());
            } else {
                // MODEL INTERFACE Ls\\Replication\\Api\\Data\$classname." ".Interface.php but not the search part
                $model_interface_generator = new ModelInterfaceGenerator($replication_operation);
                file_put_contents(
                    $replication_operation->getInterfacePath(true),
                    $model_interface_generator->generate()
                );

                // MODEL Pure Model classes Ls\Replication\Model\@classname.php
                $model_generator = new ModelGenerator($replication_operation);
                file_put_contents($replication_operation->getMainEntityPath(true), $model_generator->generate());

                // REPOSITORY INTERFACE  /Ls/Replication/API/$classname.RepositoryInterface.php
                $repository_interface_generator = new RepositoryInterfaceGenerator($replication_operation);
                file_put_contents(
                    $replication_operation->getRepositoryInterfacePath(true),
                    $repository_interface_generator->generate()
                );

                // REPOSITORY Ls\Replication\Model\$classname.Repository.php
                $repository_generator = new RepositoryGenerator($replication_operation);
                file_put_contents(
                    $replication_operation->getRepositoryPath(true),
                    $repository_generator->generate()
                );

                // RESOURCE MODEL Ls\Replication\Model\ResourceModel\$classname.php
                $resource_model_generator = new ResourceModelGenerator($replication_operation);
                file_put_contents(
                    $replication_operation->getResourceModelPath(true),
                    $resource_model_generator->generate()
                );

                // RESOURCE COLLECTION Ls\Replication\Model\ResourceModel\$classname\Collection.php
                $resource_collection_generator = new ResourceCollectionGenerator($replication_operation);
                $resource_collection_generator->createPath();
                file_put_contents(
                    $replication_operation->getResourceCollectionPath(true),
                    $resource_collection_generator->generate()
                );

                // CRON JOB  \LS\Replication\Cron\$classname.php
                $cron_job_generator = new CronJobGenerator($replication_operation);
                file_put_contents(
                    $replication_operation->getJobPath(true),
                    $cron_job_generator->generate()
                );

                // SEARCH INTERFACE \Ls\Replication\API\Data\$classname.SearchResultInterface.php
                $search_interface_generator = new SearchInterfaceGenerator($replication_operation);
                file_put_contents(
                    $replication_operation->getSearchInterfacePath(true),
                    $search_interface_generator->generate()
                );

                // SEARCH MODEL
                $search_generator = new SearchGenerator($replication_operation);
                file_put_contents(
                    $replication_operation->getSearchPath(true),
                    $search_generator->generate()
                );

                // REPOSITORY TEST Ls\Replication\Model\Test\Unit\$classname.RepositoryTest.php
                $repositoryTestGenerator = new RepositoryTestGenerator($replication_operation);
                file_put_contents(
                    $replication_operation->getRepositoryTestPath(true),
                    $repositoryTestGenerator->generate()
                );

                $this->output->writeln('- - - - ' . $operation->getName() . ' - - - -');
            }
        } catch (Exception $e) {
            $this->output->writeln("\t- - Error Start - -");
            $this->output->writeln("\tSomething went wrong, please check log directory");
            $this->output->writeln($e->getMessage());
            $this->output->writeln('- FAILED - ' . $operation->getName() . ' - FAILED -');
            $this->output->writeln("\t- - Error End - -");
        }
    }
}
