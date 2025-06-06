<?php

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
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $interfaceFolder = ucfirst($this->type->getValue());

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
        $restrictions = array_keys($metadata->getRestrictions());
        // $this->clean($baseDir);
        $odataGenerator = new OdataGenerator();
        $classMap = $odataGenerator->generate($entityDir, $operationDir, $this->getOmniDataHelper(), $output);
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

        $restrictionBlacklist = ['char', 'duration', 'guid', 'StreamBody'];
        foreach ($metadata->getRestrictions() as $restriction) {
            if (array_search($restriction->getName(), $restrictionBlacklist) === false) {
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

            $generator = new OperationGenerator($operation, $metadata);
            $content   = $generator->generate();
            // @codingStandardsIgnoreLine
            file_put_contents($filename, $content);

            $ok = sprintf('generated operation ( %1$s )', $fs->makePathRelative($filename, $cwd));
            $this->output->writeln($ok);
        }

        $filename = AbstractGenerator::path($baseDir, 'ClassMap.php');

        $generator = new ClassMapGenerator($metadata);
        $generator->setCustomClassMap($classMap);
        $content   = $generator->generate();
        // @codingStandardsIgnoreLine
        file_put_contents($filename, $content);

        $ok = sprintf('generated classmap ( %1$s )', $fs->makePathRelative($filename, $cwd));
        $this->output->writeln($ok);
        $this->output->writeln('- - - - - - - - - - ');
        $this->output->writeln('OK');

        return 0;
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
