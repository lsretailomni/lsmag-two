<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Console\Command;

use CaseHelper\CaseHelperFactory;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Service\Soap\Element;
use \Ls\Omni\Service\Soap\ReplicationOperation;
use \Ls\Replication\Code\SchemaUpdateGenerator;
use \Ls\Replication\Console\Command\ReplicationGenerate;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\Console\Cli;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class ReplicationGenerateTest extends TestCase
{
    private const SYSTEM_PROPERTIES = [
        'processed',
        'is_updated',
        'is_failed',
        'created_at',
        'updated_at',
        'identity_value',
        'checksum',
        'processed_at'
    ];

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

    /**
     * @throws \Exception
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default' ),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website' ),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'store', 'default'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'store', 'default'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
    ]
    public function testExecute()
    {
        $schemaUpdatePath     = new SchemaUpdateGenerator([]);
        $name = 'LSC Attribute';
        $request = new Element($name, $name);
        $response = new Element($name, $name);
        $replicationOperation = new ReplicationOperation($name, $request, $response);
        $dbSchemaPath         = $schemaUpdatePath->getPath();
        $paths                = [
            $replicationOperation->getMainEntityPath(true),
            $replicationOperation->getInterfacePath(true),
            $replicationOperation->getResourceModelPath(true),
            $replicationOperation->getRepositoryPath(true),
            $replicationOperation->getRepositoryInterfacePath(true),
            $replicationOperation->getResourceCollectionPath(true),
            $replicationOperation->getJobPath(true),
            $replicationOperation->getSearchInterfacePath(true),
            $replicationOperation->getSearchPath(true),
            $replicationOperation->getRepositoryTestPath(true),
            $dbSchemaPath
        ];

        $this->command       = $this->objectManager->get(ReplicationGenerate::class);
        $this->commandTester = new CommandTester($this->command);
        $this->commandTester->execute([]);
        $commandOutput = $this->commandTester->getDisplay();
        $this->assertEquals(Cli::RETURN_SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString(
            'Finished generating replication task files' . PHP_EOL,
            $commandOutput
        );

        $this->assertPathsExists($paths);
        $this->assertSystemMethods($replicationOperation);
        $this->assertDbSchema($replicationOperation, $dbSchemaPath);
    }

    public function assertDbSchema($replicationOperation, $schemaPath)
    {
        $tableName = "ls_replication_" . $replicationOperation->getTableName();
        $xml       = simplexml_load_file($schemaPath);
        foreach ($xml->children() as $table) {
            if ($table->attributes()['name'] == $tableName) {
                break;
            }
        }

        foreach (self::SYSTEM_PROPERTIES as $property) {
            $found = false;
            foreach ($table->children() as $column) {
                if ($column->attributes()['name'] == $property) {
                    $found = true;
                    break;
                }
            }

            $this->assertTrue($found);
        }
    }

    public function assertSystemMethods($replicationOperation)
    {
        $reflect  = new ReflectionClass($replicationOperation->getMainEntityFqn());
        $props    = $reflect->getMethods();
        $ownProps = [];
        foreach ($props as $prop) {
            if ($prop->class === $replicationOperation->getMainEntityFqn()) {
                $ownProps[] = $prop->getName();
            }
        }

        $caseHelper      = CaseHelperFactory::make(CaseHelperFactory::INPUT_TYPE_SNAKE_CASE);
        $expectedMethods = [];
        foreach (self::SYSTEM_PROPERTIES as $property) {
            $pascalName        = ucfirst($caseHelper->toPascalCase($property));
            $expectedMethods[] = "set$pascalName";
            $expectedMethods[] = "get$pascalName";
        }

        $this->assertTrue(
            !array_diff(
                $expectedMethods,
                $ownProps
            )
        );
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
