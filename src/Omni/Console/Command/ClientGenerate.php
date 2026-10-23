<?php
declare(strict_types=1);

namespace Ls\Omni\Console\Command;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Omni\Code\ClassMapGenerator;
use \Ls\Omni\Code\EntityGenerator;
use \Ls\Omni\Code\OdataGenerator;
use \Ls\Omni\Code\OperationGenerator;
use \Ls\Omni\Code\RestrictionGenerator;
use \Ls\Omni\Console\Command;
use \Ls\Omni\Service\Metadata;
use \Ls\Omni\Service\Service;
use \Ls\Omni\Service\Soap\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class ClientGenerate extends Command
{
    public const COMMAND_NAME = 'omni:client:generate';

    /**
     * Configure options for the command
     *
     * @return void
     */
    public function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Generate class based on OMNI endpoints. Run this one first before replication generate')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'omni service type', 'ecommerce')
            ->addOption('base', 'b', InputOption::VALUE_OPTIONAL, 'omni service base url');
    }

    /**
     * Entry point for the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception|GuzzleException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $interfaceFolder = 'CentralEcommerce';

        $modulePath   = $this->dirReader->getModuleDir('', 'Ls_Omni');
        $baseDir      = AbstractGenerator::path($modulePath, 'Client', $interfaceFolder);
        $operationDir = AbstractGenerator::path($baseDir, 'Operation');
        $entityDir    = AbstractGenerator::path($baseDir, 'Entity');
        $fs     = new Filesystem();
        $cwd    = getcwd();
        $wsdl   = Service::getUrl($this->baseUrl, true);
        $client = new Client($wsdl);
        try {
            $metadata     = $client->getMetadata();
        } catch (\Exception $e) {
            $output->writeln("ERROR: Unable to establish connection with the endpoint");
            return 0;
        }

        // The codeunits published as their own web service are read before anything is written, because the
        // class map below is rewritten completely: a codeunit that could not be read would silently lose its entries.
        $additionalMetadata = [];
        foreach (Service::ADDITIONAL_CODE_UNITS as $codeUnit) {
            try {
                $additionalClient = new Client(Service::getUrl($this->baseUrl, true, $codeUnit));
                $additionalMetadata[$codeUnit] = $additionalClient->getMetadata();
            } catch (\Exception $e) {
                $output->writeln(
                    "ERROR: Unable to read the WSDL of codeunit {$codeUnit}. Nothing was generated."
                );
                return 1;
            }
        }

        // $this->clean($baseDir);
        $odataGenerator = new OdataGenerator();
        $classMap = $odataGenerator->generate($entityDir, $operationDir, $this->getOmniDataHelper(), $output);

        $collisions = $this->findCollisions($metadata, $additionalMetadata, $classMap);
        if ($collisions) {
            $output->writeln(
                'ERROR: The following generated names exist in more than one web service, nothing was generated '
                . 'for the codeunits: ' . implode(', ', $collisions)
            );
            return 1;
        }

        $this->generateFromMetadata($metadata, $entityDir, $operationDir, $fs, $cwd);
        foreach ($additionalMetadata as $codeUnit => $codeUnitMetadata) {
            $this->output->writeln("- - - codeunit {$codeUnit} - - -");
            $this->generateFromMetadata($codeUnitMetadata, $entityDir, $operationDir, $fs, $cwd, $codeUnit);
        }

        $filename = AbstractGenerator::path($baseDir, 'ClassMap.php');

        $generator = new ClassMapGenerator($metadata);
        $generator->setCustomClassMap($classMap);
        foreach ($additionalMetadata as $codeUnitMetadata) {
            $generator->addEntries($generator->buildEntries($codeUnitMetadata));
        }
        $content   = $generator->generate();
        // @codingStandardsIgnoreLine
        file_put_contents($filename, $content);

        $ok = sprintf('generated classmap ( %1$s )', $fs->makePathRelative($filename, $cwd));
        $this->output->writeln($ok);
        $this->output->writeln('- - - - - - - - - - ');
        $this->output->writeln('OK');

        return 0;
    }

    /**
     * Generate the entities, restrictions and operations of one WSDL
     *
     * @param Metadata $metadata
     * @param string $entityDir
     * @param string $operationDir
     * @param Filesystem $fs
     * @param string|bool $cwd
     * @param string|null $codeUnit Name of the separately published codeunit, null for OmniWrapper
     * @return void
     */
    private function generateFromMetadata(
        Metadata $metadata,
        string $entityDir,
        string $operationDir,
        Filesystem $fs,
        $cwd,
        ?string $codeUnit = null
    ) {
        $restrictions = array_keys($metadata->getRestrictions());
        foreach ($metadata->getEntities() as $entity) {
            if (array_search($entity->getName(), $restrictions) === false) {
                $entityName = preg_replace('/[-._]/', '', $entity->getName());
                $filename   = AbstractGenerator::path($entityDir, "{$entityName}.php");

                $generator = new EntityGenerator($entity, $metadata);
                $content   = $generator->generate();
                // @codingStandardsIgnoreLine
                file_put_contents($filename, $content);

                $ok = sprintf('generated entity ( %1$s )', $fs->makePathRelative($filename, $cwd));
                $this->output->writeln($ok);
            }
        }

        foreach ($metadata->getRestrictions() as $restriction) {
            if (array_search($restriction->getName(), ClassMapGenerator::RESTRICTION_BLACKLIST) === false) {
                $filename = AbstractGenerator::path($entityDir, 'Enum', "{$restriction->getName()}.php");

                $generator = new RestrictionGenerator($restriction, $metadata);
                $content   = $generator->generate();
                // @codingStandardsIgnoreLine
                file_put_contents($filename, $content);

                $ok = sprintf('generated restriction ( %1$s )', $fs->makePathRelative($filename, $cwd));
                $this->output->writeln($ok);
            }
        }

        foreach ($metadata->getOperations() as $operation) {
            $filename = AbstractGenerator::path($operationDir, "{$operation->getName()}.php");

            $generator = new OperationGenerator($operation, $metadata, $codeUnit);
            $content   = $generator->generate();
            // @codingStandardsIgnoreLine
            file_put_contents($filename, $content);

            $ok = sprintf('generated operation ( %1$s )', $fs->makePathRelative($filename, $cwd));
            $this->output->writeln($ok);
        }
    }

    /**
     * Find the names that the separately published codeunits share with OmniWrapper (or with each other)
     *
     * A shared name would make one web service overwrite the class of another one.
     *
     * @param Metadata $metadata OmniWrapper metadata
     * @param Metadata[] $additionalMetadata Metadata of the other codeunits, keyed by codeunit name
     * @param array $classMap Custom class map of the OData generator, WSDL name => class name
     * @return string[]
     */
    private function findCollisions(Metadata $metadata, array $additionalMetadata, array $classMap): array
    {
        $taken = array_fill_keys($this->getGeneratedNames($metadata), 'OmniWrapper');
        foreach ($classMap as $sanitizedName) {
            $taken['Entity\\' . $sanitizedName] = 'OData';
        }

        $collisions = [];
        foreach ($additionalMetadata as $codeUnit => $codeUnitMetadata) {
            foreach ($this->getGeneratedNames($codeUnitMetadata) as $name) {
                if (isset($taken[$name])) {
                    $collisions[] = "{$name} ({$codeUnit} / {$taken[$name]})";
                    continue;
                }
                $taken[$name] = $codeUnit;
            }
        }

        return $collisions;
    }

    /**
     * Get the class names that generating the given metadata writes, grouped by folder
     *
     * @param Metadata $metadata
     * @return string[]
     */
    private function getGeneratedNames(Metadata $metadata): array
    {
        $names        = [];
        $restrictions = array_keys($metadata->getRestrictions());
        foreach ($metadata->getEntities() as $entity) {
            if (array_search($entity->getName(), $restrictions) === false) {
                $names[] = 'Entity\\' . preg_replace('/[-._]/', '', $entity->getName());
            }
        }
        foreach ($restrictions as $restrictionName) {
            if (array_search($restrictionName, ClassMapGenerator::RESTRICTION_BLACKLIST) === false) {
                $names[] = 'Entity\\Enum\\' . $restrictionName;
            }
        }
        foreach ($metadata->getOperations() as $operation) {
            $names[] = 'Operation\\' . $operation->getName();
        }

        return $names;
    }

    private function clean($folder)
    {
        $fs = new Filesystem();

        if ($fs->exists($folder)) {
            $fs->remove($folder);
        }
        $fs->mkdir(AbstractGenerator::path($folder, 'Operation'));
        $fs->mkdir(AbstractGenerator::path($folder, 'Entity', 'Enum'));

        $ok = sprintf('done cleaning folder ( %1$s )', $fs->makePathRelative($folder, getcwd()));
        $this->output->writeln($ok);
    }
}
