<?php
namespace Ls\Omni\Console\Command;

use Exception;
use \Ls\Core\Code\AbstractGenerator;
use \Ls\Omni\Code\ClassMapGenerator;
use \Ls\Omni\Code\EntityGenerator;
use \Ls\Omni\Code\OperationGenerator;
use \Ls\Omni\Code\RestrictionGenerator;
use \Ls\Omni\Console\Command;
use \Ls\Omni\Service\Service;
use \Ls\Omni\Service\Soap\Client;
use Magento\Framework\Module\Dir\Reader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ClientGenerate
 * @package Ls\Omni\Console\Command
 */
class ClientGenerate extends Command
{
    const COMMAND_NAME = 'omni:client:generate';

    /**
     * ClientGenerate constructor.
     * @param Service $service
     * @param Reader $dirReader
     */
    public function __construct(Service $service, Reader $dirReader)
    {
        parent::__construct($service, $dirReader);
    }

    public function configure()
    {

        $this->setName(self::COMMAND_NAME)
             ->setDescription('Generate class based on OMNI endpoints. Run this one first before replication generate')
             ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'omni service type', 'ecommerce')
             ->addOption('base', 'b', InputOption::VALUE_OPTIONAL, 'omni service base url');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        // @codingStandardsIgnoreLine
        $fs = new Filesystem();
        $cwd = getcwd();

        $wsdl = Service::getUrl($this->type, $this->base_url);
        // @codingStandardsIgnoreLine
        $client = new Client($wsdl, $this->type);
        $metadata = $client->getMetadata();
        $restrictions = array_keys($metadata->getRestrictions());

        $interface_folder = ucfirst($this->type->getValue());

        $modulePath    =    $this->dirReader->getModuleDir('', 'Ls_Omni');
        $base_dir      = AbstractGenerator::path($modulePath, 'Client', $interface_folder);
        $operation_dir = AbstractGenerator::path($base_dir, 'Operation');
        $entity_dir    = AbstractGenerator::path($base_dir, 'Entity');
        $this->clean($base_dir);

        foreach ($metadata->getEntities() as $entity) {
            // RESTRICTIONS ARE CREATED IN ANOTHER LOOP SO WE FILTER THEM OUT
            if (array_search($entity->getName(), $restrictions) === false) {
                $filename = AbstractGenerator::path($entity_dir, "{$entity->getName()}.php");
                // @codingStandardsIgnoreStart
                $generator = new EntityGenerator($entity, $metadata);
                $content = $generator->generate();
                file_put_contents($filename, $content);
                // @codingStandardsIgnoreEnd

                $ok = sprintf('generated entity ( %1$s )', $fs->makePathRelative($filename, $cwd));
                $this->output->writeln($ok);
            }
        }

        $restriction_blacklist = [ 'char', 'duration', 'guid', 'StreamBody' ];
        foreach ($metadata->getRestrictions() as $restriction) {
            if (array_search($restriction->getName(), $restriction_blacklist) === false) {
                $filename = AbstractGenerator::path($entity_dir, 'Enum', "{$restriction->getName()}.php");
                // @codingStandardsIgnoreStart
                $generator = new RestrictionGenerator($restriction, $metadata);
                $content = $generator->generate();
                file_put_contents($filename, $content);
                // @codingStandardsIgnoreEnd

                $ok = sprintf('generated restriction ( %1$s )', $fs->makePathRelative($filename, $cwd));
                $this->output->writeln($ok);
            }
        }

        foreach ($metadata->getOperations() as $operation) {
            $filename = AbstractGenerator::path($operation_dir, "{$operation->getName()}.php");
            // @codingStandardsIgnoreStart
            $generator = new OperationGenerator($operation, $metadata);
            $content = $generator->generate();
            file_put_contents($filename, $content);
            // @codingStandardsIgnoreEnd

            $ok = sprintf('generated operation ( %1$s )', $fs->makePathRelative($filename, $cwd));
            $this->output->writeln($ok);
        }

        $filename = AbstractGenerator::path($base_dir, 'ClassMap.php');
        // @codingStandardsIgnoreStart
        $generator = new ClassMapGenerator($metadata);
        $content = $generator->generate();
        file_put_contents($filename, $content);
        // @codingStandardsIgnoreEnd

        $ok = sprintf('generated classmap ( %1$s )', $fs->makePathRelative($filename, $cwd));
        $this->output->writeln($ok);
        $this->output->writeln('- - - - - - - - - - ');
        $this->output->writeln('OK');
    }

    /**
     * @param string $folder
     */
    private function clean($folder)
    {
        // @codingStandardsIgnoreStart
        $fs = new Filesystem();
        // @codingStandardsIgnoreEnd

        if ($fs->exists($folder)) {
            $fs->remove($folder);
        }
        $fs->mkdir(AbstractGenerator::path($folder, 'Operation'));
        $fs->mkdir(AbstractGenerator::path($folder, 'Entity', 'Enum'));

        $ok = sprintf('done cleaning folder ( %1$s )', $fs->makePathRelative($folder, getcwd()));
        $this->output->writeln($ok);
    }
}
