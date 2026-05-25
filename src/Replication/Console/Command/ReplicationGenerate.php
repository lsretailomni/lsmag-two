<?php
// @codingStandardsIgnoreFile
declare(strict_types=1);

namespace Ls\Replication\Console\Command;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Omni\Code\OdataGenerator;
use \Ls\Omni\Console\Command as OmniCommand;
use \Ls\Omni\Exception\InvalidServiceTypeException;
use \Ls\Omni\Service\Service;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Element;
use \Ls\Omni\Service\Soap\ReplicationOperation;
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
 * Command to generate replication-related Magento code (models, repositories, cron jobs, etc.)
 */
class ReplicationGenerate extends OmniCommand
{
    public const COMMAND_NAME = 'replication:generate';
    public const SYSTEM_CONFIG_OPTION = 'system';
    public const CRON_CONFIG_OPTION = 'config';

    /** @var bool */
    public $generateSystemConfig = false;

    /** @var bool */
    public $generateCronConfig = false;

    /**
     * @param Service $service
     * @param Reader $dirReader
     * @param File $fileHelper
     */
    public function __construct(Service $service, Reader $dirReader, public File $fileHelper)
    {
        parent::__construct($service, $dirReader);
    }

    /**
     * Initialize command input and SOAP metadata.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws InvalidServiceTypeException
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->type = ServiceType::ECOMMERCE();
        parent::initialize($input, $output);
    }

    /**
     * Configure command line options.
     */
    public function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Generate replication-related code after running omni:client:generate')
            ->addOption(self::BASE_URL, 'b', InputOption::VALUE_OPTIONAL, 'Omni service base URL')
            ->addOption(self::CRON_CONFIG_OPTION, 'c', InputOption::VALUE_OPTIONAL, 'Display XML crontab configurations', false)
            ->addOption(self::SYSTEM_CONFIG_OPTION, 's', InputOption::VALUE_OPTIONAL, 'Display XML system configurations', false);
    }

    /**
     * Execute the code generation for replication.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception|GuzzleException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->createPathIfNotExist();
        $omniDataHelper = $this->getOmniDataHelper();
        $odataGenerator = new OdataGenerator();
        $odataGenerator->segregateWebservices($omniDataHelper);
        $operations = $odataGenerator->getReplicationServices();
        $storeCode = $omniDataHelper->lsr->getActiveWebStore();
        $replicationOperations = [];

        foreach ($operations as $operation) {
            $name = null;
            $hasStoreNo       = $operation['hasStoreNo'] ?? null;
            $requestClassName = $operation['requestClassName'] ?? null;

            if ($hasStoreNo !== null && $requestClassName) {
                list($recRef, $recordFields) = $odataGenerator->fetchGivenReplicationWebserviceFieldsAndRecords(
                    $omniDataHelper,
                    $requestClassName,
                    $hasStoreNo,
                    $storeCode
                );
                if (!empty($recRef) && !empty($recordFields)) {
                    if (isset($recRef['TableName'])) {
                        $name = $recRef['TableName'];
                    } elseif (isset($recRef['DataSetName'])) {
                        $name = $recRef['DataSetName'];
                    }

                    if ($name) {
                        $request = new Element($name, $name);
                        $response = new Element($name, $name);
                        $replication = new ReplicationOperation($name, $request, $response);
                        $replicationOperations[$replication->getName()] = $replication;
                    }
                }
            }
        }

        foreach ($replicationOperations as $replicationOperation) {
            $this->processOperation($replicationOperation);
        }

        try {
            $schemaGenerator = new SchemaUpdateGenerator($replicationOperations);
            $schemaGenerator->generate();
        } catch (Exception $e) {
            $this->output->writeln("\t- - Error Start - -");
            $this->output->writeln("\tSomething went wrong while creating db_schema.xml");
            $this->output->writeln($e->getMessage());
            $this->output->writeln("\t- - Error End - -");
        }

        $this->output->writeln('Finished generating replication task files');
        return 0;
    }

    /**
     * Create required directories if they do not exist.
     */
    private function createPathIfNotExist()
    {
        $basePath = $this->dirReader->getModuleDir('', 'Ls_Replication');

        $apiDataPath = AbstractGenerator::path($basePath, AbstractGenerator::fqn('Api/Data'));
        if (!is_dir($apiDataPath)) {
            $this->fileHelper->mkdir($apiDataPath, 0755);
        }

        $cronPath = AbstractGenerator::path($basePath, AbstractGenerator::fqn('Cron'));
        if (!is_dir($cronPath)) {
            $this->fileHelper->mkdir($cronPath, 0755);
        }

        $resourceModelPath = AbstractGenerator::path($basePath, AbstractGenerator::fqn('Model/ResourceModel'));
        if (!is_dir($resourceModelPath)) {
            $this->fileHelper->mkdir($resourceModelPath, 0755);
        }
    }

    /**
     * Process and generate files for a single replication operation.
     *
     * @param ReplicationOperation $replication
     */
    private function processOperation(ReplicationOperation $replication)
    {
        try {
            if ($this->generateSystemConfig) {
                $systemGen = new SystemConfigGenerator($replication);
                $this->output->writeln($systemGen->generate());
            } elseif ($this->generateCronConfig) {
                $cronGen = new CronJobConfigGenerator($replication);
                $this->output->writeln($cronGen->generate());
            } else {
                $interfaceGen = new ModelInterfaceGenerator($replication);
                file_put_contents($replication->getInterfacePath(true), $interfaceGen->generate());

                $modelGen = new ModelGenerator($replication);
                file_put_contents($replication->getMainEntityPath(true), $modelGen->generate());

                $resourceModelGen = new ResourceModelGenerator($replication);
                file_put_contents($replication->getResourceModelPath(true), $resourceModelGen->generate());

                $repoInterfaceGen = new RepositoryInterfaceGenerator($replication);
                file_put_contents($replication->getRepositoryInterfacePath(true), $repoInterfaceGen->generate());

                $repoGen = new RepositoryGenerator($replication);
                file_put_contents($replication->getRepositoryPath(true), $repoGen->generate());

                $collectionGen = new ResourceCollectionGenerator($replication);
                $collectionGen->createPath();
                file_put_contents($replication->getResourceCollectionPath(true), $collectionGen->generate());

                $searchInterfaceGen = new SearchInterfaceGenerator($replication);
                file_put_contents($replication->getSearchInterfacePath(true), $searchInterfaceGen->generate());

                $searchGen = new SearchGenerator($replication);
                file_put_contents($replication->getSearchPath(true), $searchGen->generate());

                $repoTestGen = new RepositoryTestGenerator($replication);
                file_put_contents($replication->getRepositoryTestPath(true), $repoTestGen->generate());

                $cronGen = new CronJobGenerator($replication);
                file_put_contents($replication->getJobPath(true), $cronGen->generate());

                $this->output->writeln('- - - - ' . $replication->getName() . ' - - - -');
            }
        } catch (Exception $e) {
            $this->output->writeln("\t- - Error Start - -");
            $this->output->writeln("\tSomething went wrong, please check log directory");
            $this->output->writeln($e->getMessage());
            $this->output->writeln('- FAILED - ' . $replication->getName() . ' - FAILED -');
            $this->output->writeln("\t- - Error End - -");
        }
    }
}
