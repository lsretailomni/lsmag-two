<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Console\Command;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Operation\ReplEcommItems;
use \Ls\Omni\Service\Service as OmniService;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use \Ls\Replication\Console\Command\ReplicationGenerate;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\Console\Cli;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class ReplicationGenerateTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;
    private $commandTester;
    private $command;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
    ]
    public function testExecute()
    {
        $service_type = new ServiceType(ReplEcommItems::SERVICE_TYPE);
        $url          = OmniService::getUrl($service_type);
        $client       = new OmniClient($url, $service_type);
        $metadata = $client->getMetadata(true);
        $replication_operation = $metadata->getReplicationOperationByName('ReplEcommItems');
        $paths = [
            $replication_operation->getMainEntityPath(true),
            $replication_operation->getInterfacePath(true),
            $replication_operation->getResourceModelPath(true),
            $replication_operation->getRepositoryPath(true),
            $replication_operation->getRepositoryInterfacePath(true),
            $replication_operation->getResourceCollectionPath(true)
        ];

        $this->removeFiles($paths);
        $this->command = $this->objectManager->get(ReplicationGenerate::class);
        $this->commandTester = new CommandTester($this->command);
        $this->commandTester->execute([]);
        $commandOutput = $this->commandTester->getDisplay();
        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString(
            'Finish Generating Replication Task Files' . PHP_EOL,
            $commandOutput
        );

        $this->assertPathsExists($paths);
    }

    public function removeFiles($pathsRemoved)
    {
        foreach ($pathsRemoved as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function assertPathsExists($pathsRemoved)
    {
        foreach ($pathsRemoved as $path) {
            $this->assertTrue(file_exists($path));
        }
    }
}
